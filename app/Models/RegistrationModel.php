<?php
/**
 * app/Models/RegistrationModel.php
 * Quản lý đăng ký tham gia ca trực
 */

declare(strict_types=1);

namespace App\Models;

class RegistrationModel extends BaseModel
{
    protected string $table = 'registrations';

    public function register(int $userId, int $shiftId, string $note = ''): array
    {
        try {
            $this->db->beginTransaction();

            $shiftStmt = $this->db->prepare(
                "SELECT s.*, a.status as activity_status, a.registration_deadline
                 FROM shifts s
                 JOIN activities a ON a.id = s.activity_id
                 WHERE s.id = ?
                 FOR UPDATE"
            );
            $shiftStmt->execute([$shiftId]);
            $shift = $shiftStmt->fetch();
            if (!$shift) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Ca trực không tồn tại.'];
            }

            if ($shift['status'] !== 'open' || !in_array($shift['activity_status'], ['open', 'ongoing'], true)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Ca trực hiện không mở đăng ký.'];
            }

            if (!empty($shift['registration_deadline']) && time() > strtotime($shift['registration_deadline'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Đã hết hạn đăng ký cho hoạt động này.'];
            }

            $existingStmt = $this->db->prepare(
                "SELECT * FROM registrations WHERE user_id = ? AND shift_id = ? LIMIT 1"
            );
            $existingStmt->execute([$userId, $shiftId]);
            $existing = $existingStmt->fetch();
            if ($existing && in_array($existing['status'], ['pending', 'approved'], true)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Bạn đã đăng ký ca này rồi.'];
            }

            $countStmt = $this->db->prepare(
                "SELECT COUNT(*) FROM registrations WHERE shift_id = ? AND status IN ('pending', 'approved')"
            );
            $countStmt->execute([$shiftId]);
            if ((int)$countStmt->fetchColumn() >= (int)$shift['max_volunteers']) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Ca trực đã nhận đủ số lượng đăng ký.'];
            }

            if ($existing) {
                $this->db->prepare(
                    "UPDATE registrations SET status = 'pending', note = ?, registered_at = NOW(), updated_at = NOW() WHERE id = ?"
                )->execute([$note, $existing['id']]);
            } else {
                $this->db->prepare(
                    "INSERT INTO registrations (user_id, shift_id, status, note, registered_at) VALUES (?, ?, 'pending', ?, NOW())"
                )->execute([$userId, $shiftId, $note]);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Đã gửi đăng ký. Vui lòng chờ điều phối viên duyệt.'];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function updateStatusForCoordinator(int $registrationId, int $coordinatorId, string $status, bool $isAdmin = false): bool
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $registrationStmt = $this->db->prepare(
                "SELECT r.user_id, s.name AS shift_name, a.title AS activity_title
                 FROM registrations r
                 JOIN shifts s ON s.id = r.shift_id
                 JOIN activities a ON a.id = s.activity_id
                 WHERE r.id = ? AND (? = 1 OR a.coordinator_id = ?) AND r.status = 'pending'
                 FOR UPDATE"
            );
            $registrationStmt->execute([$registrationId, (int)$isAdmin, $coordinatorId]);
            $registration = $registrationStmt->fetch();
            if (!$registration) {
                $this->db->rollBack();
                return false;
            }

            $updateStmt = $this->db->prepare(
                "UPDATE registrations SET status = ?, updated_at = NOW()
                 WHERE id = ? AND status = 'pending'"
            );
            $updateStmt->execute([$status, $registrationId]);
            if ($updateStmt->rowCount() !== 1) {
                $this->db->rollBack();
                return false;
            }

            if ($status === 'rejected') {
                $notificationStmt = $this->db->prepare(
                    "INSERT INTO notifications (user_id, type, title, message, link)
                     VALUES (?, 'registration', ?, ?, '/volunteer/my-registrations')"
                );
                $notificationStmt->execute([
                    (int)$registration['user_id'],
                    'Đơn đăng ký ca trực chưa được chấp nhận',
                    "Rất tiếc, đơn đăng ký ca '{$registration['shift_name']}' thuộc hoạt động '{$registration['activity_title']}' đã bị từ chối. Bạn có thể xem các ca trực khác đang mở để đăng ký."
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function withdraw(int $userId, int $shiftId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM registrations WHERE user_id = ? AND shift_id = ? LIMIT 1");
        $stmt->execute([$userId, $shiftId]);
        $reg = $stmt->fetch();

        if (!$reg || in_array($reg['status'], ['cancelled', 'withdrawn', 'rejected'], true)) {
            return ['success' => false, 'message' => 'Bạn chưa đăng ký hoặc đăng ký đã bị hủy.'];
        }

        // Kiểm tra xem đã bị gán phân công chưa
        $asgnStmt = $this->db->prepare(
            "SELECT * FROM assignments WHERE user_id = ? AND shift_id = ? AND status IN ('assigned', 'confirmed') LIMIT 1"
        );
        $asgnStmt->execute([$userId, $shiftId]);
        if ($asgnStmt->fetch()) {
            return ['success' => false, 'message' => 'Bạn đã được phân công vào ca này, không thể tự rút đăng ký. Hãy gửi yêu cầu báo vắng/thay người!'];
        }

        $this->db->prepare("UPDATE registrations SET status = 'withdrawn', updated_at = NOW() WHERE id = ?")
                 ->execute([$reg['id']]);

        return ['success' => true, 'message' => 'Đã rút đăng ký thành công.'];
    }

    public function getByShift(int $shiftId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, u.full_name, u.email, u.phone, vp.student_id, vp.faculty,
                    GROUP_CONCAT(CONCAT(sk.name, ' (', vs.level, ')') SEPARATOR ', ') as user_skills
             FROM registrations r
             JOIN users u ON r.user_id = u.id
             LEFT JOIN volunteer_profiles vp ON u.id = vp.user_id
             LEFT JOIN volunteer_skills vs ON u.id = vs.user_id
             LEFT JOIN skills sk ON vs.skill_id = sk.id
             WHERE r.shift_id = ?
             GROUP BY r.id
             ORDER BY r.registered_at ASC"
        );
        $stmt->execute([$shiftId]);
        return $stmt->fetchAll();
    }

    public function getByCoordinator(int $coordinatorId, string $status = '', int $page = 1, int $perPage = 15): array
    {
        $where = "a.coordinator_id = ?";
        $params = [$coordinatorId];
        if ($status !== '') {
            $where .= " AND r.status = ?";
            $params[] = $status;
        }

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM registrations r
             JOIN shifts s ON r.shift_id = s.id
             JOIN activities a ON s.activity_id = a.id
             WHERE $where"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $stmt = $this->db->prepare(
            "SELECT r.*, u.full_name as volunteer_name, u.email as volunteer_email, u.phone,
                    s.name as shift_name, s.date as shift_date, s.time_start, s.time_end,
                    a.title as activity_title, a.id as activity_id
             FROM registrations r
             JOIN users u ON r.user_id = u.id
             JOIN shifts s ON r.shift_id = s.id
             JOIN activities a ON s.activity_id = a.id
             WHERE $where
             ORDER BY r.registered_at DESC $limitSql"
        );
        $stmt->execute($params);
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
        ];
    }

    public function countPendingForCoordinator(int $coordinatorId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM registrations r
             JOIN shifts s ON r.shift_id = s.id
             JOIN activities a ON s.activity_id = a.id
             WHERE a.coordinator_id = ? AND r.status = 'pending'"
        );
        $stmt->execute([$coordinatorId]);
        return (int)$stmt->fetchColumn();
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, s.name as shift_name, s.date as shift_date, s.time_start, s.time_end,
                    a.title as activity_title, a.location, a.id as activity_id,
                    asgn.status as assignment_status, asgn.id as assignment_id
             FROM registrations r
             JOIN shifts s ON r.shift_id = s.id
             JOIN activities a ON s.activity_id = a.id
             LEFT JOIN assignments asgn ON asgn.shift_id = s.id AND asgn.user_id = r.user_id AND asgn.status NOT IN ('removed','replaced')
             WHERE r.user_id = ?
             ORDER BY s.date DESC, s.time_start DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
