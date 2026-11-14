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
        if ($userId <= 0 || $shiftId <= 0 || mb_strlen($note) > 500) {
            return ['success' => false, 'message' => 'Thông tin đăng ký không hợp lệ.'];
        }

        try {
            $this->db->beginTransaction();

            $shiftStmt = $this->db->prepare(
                "SELECT s.*, a.status as activity_status, a.registration_deadline,
                        a.coordinator_id, a.title as activity_title
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
            // Hoạt động đã qua ngày kết thúc => khóa đăng ký ngay cả khi cột status vẫn là 'open'.
            if (!empty($shift['date_end']) && time() > strtotime($shift['date_end'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Hoạt động này đã kết thúc, không thể đăng ký.'];
            }

            // Ca trực đã qua thời gian bắt đầu => không cho đăng ký.
            $shiftStartsAt = strtotime($shift['date'] . ' ' . $shift['time_start']);
            if ($shiftStartsAt !== false && $shiftStartsAt < time()) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Ca trực đã bắt đầu, không thể đăng ký.'];
            }

            $volunteerStmt = $this->db->prepare(
                "SELECT u.full_name
                 FROM users u
                 INNER JOIN user_roles ur ON ur.user_id = u.id
                 INNER JOIN roles r ON r.id = ur.role_id AND r.name = 'volunteer'
                 WHERE u.id = ? AND u.status = 'active'
                 LIMIT 1"
                 . " FOR UPDATE"
            );
            $volunteerStmt->execute([$userId]);
            $volunteerName = $volunteerStmt->fetchColumn();
            if ($volunteerName === false) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Chỉ tình nguyện viên đang hoạt động mới có thể đăng ký ca.'];
            }

            $existingStmt = $this->db->prepare(
                "SELECT * FROM registrations WHERE user_id = ? AND shift_id = ? LIMIT 1 FOR UPDATE"
            );
            $existingStmt->execute([$userId, $shiftId]);
            $existing = $existingStmt->fetch();
            if ($existing && in_array($existing['status'], ['pending', 'approved'], true)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Bạn đã đăng ký ca này rồi.'];
            }

            $assignmentStmt = $this->db->prepare(
                "SELECT id FROM assignments
                 WHERE user_id = ? AND shift_id = ? AND status IN ('assigned', 'confirmed')
                 LIMIT 1"
            );
            $assignmentStmt->execute([$userId, $shiftId]);
            if ($assignmentStmt->fetchColumn()) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Bạn đã được phân công vào ca này.'];
            }

            $conflictStmt = $this->db->prepare(
                "SELECT s.name
                 FROM shifts target
                 JOIN shifts s ON s.id <> target.id
                   AND TIMESTAMP(s.date, s.time_start) <
                       CASE
                           WHEN target.time_end <= target.time_start
                               THEN DATE_ADD(TIMESTAMP(target.date, target.time_end), INTERVAL 1 DAY)
                           ELSE TIMESTAMP(target.date, target.time_end)
                       END
                   AND CASE
                           WHEN s.time_end <= s.time_start
                               THEN DATE_ADD(TIMESTAMP(s.date, s.time_end), INTERVAL 1 DAY)
                           ELSE TIMESTAMP(s.date, s.time_end)
                       END > TIMESTAMP(target.date, target.time_start)
                 WHERE target.id = ?
                   AND (
                       EXISTS (
                           SELECT 1
                           FROM assignments asgn
                           WHERE asgn.user_id = ?
                             AND asgn.shift_id = s.id
                             AND asgn.status NOT IN ('removed', 'replaced', 'absent')
                       )
                       OR EXISTS (
                           SELECT 1
                           FROM registrations reg
                           WHERE reg.user_id = ?
                             AND reg.shift_id = s.id
                             AND reg.status IN ('pending', 'approved')
                       )
                   )
                 LIMIT 1"
            );
            $conflictStmt->execute([$shiftId, $userId, $userId]);
            $conflictingShiftName = $conflictStmt->fetchColumn();
            if ($conflictingShiftName !== false) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => "Bạn đã đăng ký hoặc được phân công ca '{$conflictingShiftName}' bị trùng giờ trong ngày này.",
                ];
            }

            $countStmt = $this->db->prepare(
                "SELECT COUNT(*) FROM registrations WHERE shift_id = ? AND status IN ('pending', 'approved')"
            );
            $countStmt->execute([$shiftId]);
            $registrationCount = (int)$countStmt->fetchColumn();
            $assignedStmt = $this->db->prepare(
                "SELECT COUNT(*) FROM assignments
                 WHERE shift_id = ? AND status NOT IN ('removed', 'replaced', 'absent')"
            );
            $assignedStmt->execute([$shiftId]);
            $assignedCount = (int)$assignedStmt->fetchColumn();
            if (max($registrationCount, $assignedCount) >= (int)$shift['max_volunteers']) {
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

            $this->db->prepare(
                "INSERT INTO notifications (user_id, type, title, message, link)
                 VALUES (?, 'registration', ?, ?, '/coordinator/registrations?status=pending')"
            )->execute([
                (int)$shift['coordinator_id'],
                'Có đơn đăng ký ca trực mới',
                "{$volunteerName} đã đăng ký ca '{$shift['name']}' thuộc hoạt động '{$shift['activity_title']}'."
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Đã gửi đăng ký. Vui lòng chờ điều phối viên duyệt.'];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function updateStatusForCoordinator(int $registrationId, int $coordinatorId, string $status, bool $isAdmin = false): array
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return ['success' => false, 'message' => 'Trạng thái đơn đăng ký không hợp lệ.'];
        }

        try {
            $this->db->beginTransaction();

            $registrationStmt = $this->db->prepare(
                "SELECT r.shift_id
                 FROM registrations r
                 WHERE r.id = ?
                 LIMIT 1"
            );
            $registrationStmt->execute([$registrationId]);
            $shiftId = $registrationStmt->fetchColumn();
            if ($shiftId === false) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Đơn đăng ký không tồn tại.'];
            }

            $this->db->prepare("SELECT id FROM shifts WHERE id = ? FOR UPDATE")->execute([(int)$shiftId]);

            $registrationStmt = $this->db->prepare(
                "SELECT r.user_id, s.name AS shift_name, a.title AS activity_title, s.id AS shift_id
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
                return ['success' => false, 'message' => 'Đơn đã được xử lý hoặc không thuộc hoạt động của bạn.'];
            }

            $updateStmt = $this->db->prepare(
                "UPDATE registrations SET status = ?, updated_at = NOW()
                 WHERE id = ? AND status = 'pending'"
            );
            $updateStmt->execute([$status, $registrationId]);
            if ($updateStmt->rowCount() !== 1) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Đơn đã được xử lý hoặc không thể cập nhật.'];
            }

            $isApproved = $status === 'approved';
            if (!$isApproved) {
                $notificationStmt = $this->db->prepare(
                    "INSERT INTO notifications (user_id, type, title, message, link)
                     VALUES (?, 'registration', ?, ?, '/volunteer/my-registrations')"
                );
                $notificationStmt->execute([
                    (int)$registration['user_id'],
                    'Đơn đăng ký ca trực chưa được chấp nhận',
                    "Đơn đăng ký ca '{$registration['shift_name']}' thuộc hoạt động '{$registration['activity_title']}' đã bị từ chối. Bạn có thể đăng ký ca khác đang mở."
                ]);
            }

            if ($isApproved) {
                $assignmentResult = (new AssignmentModel())->manualAdd(
                    (int)$registration['shift_id'],
                    (int)$registration['user_id'],
                    $coordinatorId
                );
                if (!$assignmentResult['success']) {
                    $this->db->rollBack();
                    return [
                        'success' => false,
                        'message' => 'Không thể duyệt đơn; giao dịch đã được hoàn tác và đơn vẫn ở trạng thái chờ duyệt. ' . $assignmentResult['message'],
                    ];
                }
            }

            $this->db->commit();
            return [
                'success' => true,
                'message' => $isApproved
                    ? 'Đã duyệt đơn và phân công tình nguyện viên vào ca. Tình nguyện viên cần xác nhận tham gia.'
                    : 'Đã từ chối đơn đăng ký.',
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function withdraw(int $userId, int $shiftId): array
    {
        if ($userId <= 0 || $shiftId <= 0) {
            return ['success' => false, 'message' => 'Thông tin đăng ký không hợp lệ.'];
        }

        try {
            $this->db->beginTransaction();
            $this->db->prepare("SELECT id FROM shifts WHERE id = ? FOR UPDATE")->execute([$shiftId]);

            $stmt = $this->db->prepare(
                "SELECT r.id, r.status, s.name AS shift_name, a.title AS activity_title,
                        a.coordinator_id, u.full_name
                 FROM registrations r
                 JOIN shifts s ON s.id = r.shift_id
                 JOIN activities a ON a.id = s.activity_id
                 JOIN users u ON u.id = r.user_id
                 WHERE r.user_id = ? AND r.shift_id = ?
                 LIMIT 1 FOR UPDATE"
            );
            $stmt->execute([$userId, $shiftId]);
            $reg = $stmt->fetch();

            if (!$reg || $reg['status'] !== 'pending') {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => $reg && $reg['status'] === 'approved'
                        ? 'Đơn đăng ký đã được duyệt nên không thể hủy. Vui lòng liên hệ điều phối viên nếu cần hỗ trợ.'
                        : 'Không có đơn đăng ký đang chờ để rút.',
                ];
            }

            $asgnStmt = $this->db->prepare(
                "SELECT id FROM assignments WHERE user_id = ? AND shift_id = ? AND status NOT IN ('removed', 'replaced') LIMIT 1"
            );
            $asgnStmt->execute([$userId, $shiftId]);
            if ($asgnStmt->fetchColumn()) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Ca này đã có lịch phân công cho bạn, không thể tự rút đăng ký. Hãy liên hệ điều phối viên để được hỗ trợ.'];
            }

            $updateStmt = $this->db->prepare(
                "UPDATE registrations SET status = 'withdrawn', updated_at = NOW()
                 WHERE id = ? AND status = 'pending'"
            );
            $updateStmt->execute([(int)$reg['id']]);
            if ($updateStmt->rowCount() !== 1) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Đơn đăng ký đã được xử lý, không thể rút.'];
            }
            $this->db->prepare(
                "INSERT INTO notifications (user_id, type, title, message, link)
                 VALUES (?, 'registration', ?, ?, '/coordinator/registrations')"
            )->execute([
                (int)$reg['coordinator_id'],
                'Tình nguyện viên đã rút đơn đăng ký',
                "{$reg['full_name']} đã rút đơn đăng ký ca '{$reg['shift_name']}' thuộc hoạt động '{$reg['activity_title']}'."
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Đã rút đăng ký thành công.'];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
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

    public function getByCoordinator(
        int $coordinatorId,
        string $status = '',
        int $page = 1,
        int $perPage = 15,
        string $keyword = '',
        string $sort = 'recent'
    ): array
    {
        $where = ["a.coordinator_id = ?"];
        $params = [$coordinatorId];
        if ($status !== '') {
            $where[] = "r.status = ?";
            $params[] = $status;
        }
        if ($keyword !== '') {
            $where[] = '(u.full_name LIKE ? OR u.email LIKE ? OR s.name LIKE ? OR a.title LIKE ?)';
            $searchTerm = '%' . $keyword . '%';
            array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM registrations r
             JOIN users u ON r.user_id = u.id
             JOIN shifts s ON r.shift_id = s.id
             JOIN activities a ON s.activity_id = a.id
             $whereSql"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $statusCountStmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS all_count,
                COALESCE(SUM(r.status = 'pending'), 0) AS pending_count,
                COALESCE(SUM(r.status = 'approved'), 0) AS approved_count,
                COALESCE(SUM(r.status = 'rejected'), 0) AS rejected_count
             FROM registrations r
             JOIN shifts s ON r.shift_id = s.id
             JOIN activities a ON s.activity_id = a.id
             WHERE a.coordinator_id = ?"
        );
        $statusCountStmt->execute([$coordinatorId]);
        $statusCounts = $statusCountStmt->fetch();

        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $orderBy = match ($sort) {
            'oldest' => 'r.registered_at ASC, r.id ASC',
            'shift_date' => 's.date ASC, s.time_start ASC, r.id DESC',
            default => 'r.registered_at DESC, r.id DESC',
        };
        $stmt = $this->db->prepare(
            "SELECT r.*, u.full_name as volunteer_name, u.email as volunteer_email, u.phone,
                    s.name as shift_name, s.date as shift_date, s.time_start, s.time_end,
                    a.title as activity_title, a.image_url, a.id as activity_id
             FROM registrations r
             JOIN users u ON r.user_id = u.id
             JOIN shifts s ON r.shift_id = s.id
             JOIN activities a ON s.activity_id = a.id
             $whereSql
             ORDER BY $orderBy $limitSql"
        );
        $stmt->execute($params);
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'status_counts' => $statusCounts,
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

    public function getByUserPage(
        int $userId,
        int $page = 1,
        int $perPage = 15,
        string $status = '',
        string $keyword = '',
        string $sort = 'recent'
    ): array
    {
        $where = ['r.user_id = ?'];
        $params = [$userId];
        if ($status !== '') {
            $where[] = 'r.status = ?';
            $params[] = $status;
        }
        if ($keyword !== '') {
            $where[] = '(a.title LIKE ? OR s.name LIKE ? OR a.location LIKE ?)';
            $searchTerm = '%' . $keyword . '%';
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM registrations r
             JOIN shifts s ON r.shift_id = s.id
             JOIN activities a ON s.activity_id = a.id
             $whereSql"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $statusCountStmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS all_count,
                COALESCE(SUM(r.status = 'pending'), 0) AS pending_count,
                COALESCE(SUM(r.status = 'approved'), 0) AS approved_count,
                COALESCE(SUM(r.status = 'rejected'), 0) AS rejected_count
             FROM registrations r
             WHERE r.user_id = ?"
        );
        $statusCountStmt->execute([$userId]);
        $statusCounts = $statusCountStmt->fetch();

        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $orderBy = match ($sort) {
            'oldest' => 'r.registered_at ASC, r.id ASC',
            'event' => 's.date ASC, s.time_start ASC, r.id DESC',
            default => 'r.registered_at DESC, r.id DESC',
        };
        $stmt = $this->db->prepare(
            "SELECT r.*, s.name as shift_name, s.date as shift_date, s.time_start, s.time_end,
                    a.title as activity_title, a.location, a.image_url, a.id as activity_id,
                    asgn.status as assignment_status, asgn.id as assignment_id
             FROM registrations r
             JOIN shifts s ON r.shift_id = s.id
             JOIN activities a ON s.activity_id = a.id
             LEFT JOIN assignments asgn ON asgn.shift_id = s.id AND asgn.user_id = r.user_id AND asgn.status NOT IN ('removed','replaced')
             $whereSql
             ORDER BY $orderBy $limitSql"
        );
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'visible_count' => count($data),
            'status_counts' => $statusCounts,
        ];
    }
}
