<?php
/**
 * app/Models/AssignmentModel.php
 * Quản lý phân công tình nguyện viên, phiên chạy thuật toán, và lịch sử
 */

declare(strict_types=1);

namespace App\Models;

use PDO;
use Exception;

class AssignmentModel extends BaseModel
{
    protected string $table = 'assignments';

    public function getByShift(int $shiftId): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name as volunteer_name, u.email as volunteer_email, u.phone,
                    vp.student_id, vp.faculty, vp.class_name,
                    att.status as attendance_status, att.id as attendance_id,
                    rr.id as replacement_request_id, rr.status as replacement_status
             FROM assignments a
             JOIN users u ON a.user_id = u.id
             LEFT JOIN volunteer_profiles vp ON u.id = vp.user_id
             LEFT JOIN attendance att ON att.assignment_id = a.id
             LEFT JOIN replacement_requests rr ON rr.assignment_id = a.id AND rr.status = 'pending'
             WHERE a.shift_id = ? AND a.status NOT IN ('removed', 'replaced')
             ORDER BY a.assigned_at ASC"
        );
        $stmt->execute([$shiftId]);
        return $stmt->fetchAll();
    }

    public function getApprovedRegisteredVolunteersForShift(int $shiftId): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.full_name, u.email, u.phone, vp.student_id,
                    (SELECT COUNT(*) FROM assignments asgn
                     WHERE asgn.user_id = u.id AND asgn.status IN ('assigned','confirmed')) as current_shifts,
                    GROUP_CONCAT(DISTINCT sk.name ORDER BY sk.name SEPARATOR ', ') as skills
             FROM registrations reg
             INNER JOIN users u ON u.id = reg.user_id AND u.status = 'active'
             INNER JOIN user_roles ur ON ur.user_id = u.id
             INNER JOIN roles r ON r.id = ur.role_id AND r.name = 'volunteer'
             LEFT JOIN volunteer_profiles vp ON vp.user_id = u.id
             LEFT JOIN volunteer_skills vs ON vs.user_id = u.id
             LEFT JOIN skills sk ON sk.id = vs.skill_id
             WHERE reg.shift_id = ? AND reg.status = 'approved'
               AND NOT EXISTS (
                   SELECT 1 FROM assignments existing
                   WHERE existing.shift_id = reg.shift_id
                     AND existing.user_id = reg.user_id
                     AND existing.status NOT IN ('removed', 'replaced')
               )
             GROUP BY u.id
             ORDER BY u.full_name ASC"
        );
        $stmt->execute([$shiftId]);
        return $stmt->fetchAll();
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, s.name as shift_name, s.date as shift_date, s.time_start, s.time_end,
                    act.title as activity_title, act.location as activity_location, act.id as activity_id,
                    att.status as attendance_status, att.id as attendance_id,
                    rr.id as replacement_request_id, rr.status as replacement_status
             FROM assignments a
             JOIN shifts s ON a.shift_id = s.id
             JOIN activities act ON s.activity_id = act.id
             LEFT JOIN attendance att ON att.assignment_id = a.id
             LEFT JOIN replacement_requests rr ON rr.assignment_id = a.id AND rr.status = 'pending'
             WHERE a.user_id = ? AND a.status NOT IN ('removed', 'replaced')
             ORDER BY s.date ASC, s.time_start ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function countActiveByUser(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM assignments
             WHERE user_id = ? AND status NOT IN ('removed', 'replaced')"
        );
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getRunsByActivity(int $activityId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ar.*, u.full_name as run_by_name
             FROM assignment_runs ar
             JOIN users u ON ar.run_by = u.id
             WHERE ar.activity_id = ?
             ORDER BY ar.created_at DESC"
        );
        $stmt->execute([$activityId]);
        return $stmt->fetchAll();
    }

    public function getRunDetails(int $runId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ar.*, u.full_name as run_by_name, act.title as activity_title
             FROM assignment_runs ar
             JOIN users u ON ar.run_by = u.id
             JOIN activities act ON ar.activity_id = act.id
             WHERE ar.id = ? LIMIT 1"
        );
        $stmt->execute([$runId]);
        $run = $stmt->fetch();
        if (!$run) return null;

        $itemsStmt = $this->db->prepare(
            "SELECT ari.*, s.name as shift_name, s.date as shift_date, s.time_start, s.time_end,
                    s.min_volunteers, s.max_volunteers
             FROM assignment_run_items ari
             JOIN shifts s ON ari.shift_id = s.id
             WHERE ari.run_id = ?
             ORDER BY s.date ASC, s.time_start ASC"
        );
        $itemsStmt->execute([$runId]);
        $run['items'] = $itemsStmt->fetchAll();

        return $run;
    }

    /**
     * Thêm phân công thủ công với kiểm tra ràng buộc đầy đủ & transaction
     */
    public function manualAdd(int $shiftId, int $userId, int $assignedBy): array
    {
        $shiftModel = new ShiftModel();
        $shift = $shiftModel->findWithDetails($shiftId);
        if (!$shift) {
            return ['success' => false, 'message' => 'Ca trực không tồn tại.'];
        }

        try {
            $this->db->beginTransaction();

            $volunteerStmt = $this->db->prepare(
                "SELECT u.id
                 FROM users u
                 INNER JOIN user_roles ur ON ur.user_id = u.id
                 INNER JOIN roles r ON r.id = ur.role_id AND r.name = 'volunteer'
                 WHERE u.id = ? AND u.status = 'active'
                 LIMIT 1"
            );
            $volunteerStmt->execute([$userId]);
            if (!$volunteerStmt->fetchColumn()) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Tài khoản không phải tình nguyện viên đang hoạt động.'];
            }

            $registrationStmt = $this->db->prepare(
                "SELECT id FROM registrations
                 WHERE shift_id = ? AND user_id = ? AND status = 'approved'
                 LIMIT 1"
            );
            $registrationStmt->execute([$shiftId, $userId]);
            if (!$registrationStmt->fetchColumn()) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Chỉ có thể phân công tình nguyện viên đã đăng ký và được duyệt ca này.'];
            }

            // 1. Khóa hàng và kiểm tra số người hiện tại (Concurrency control)
            $countStmt = $this->db->prepare(
                "SELECT COUNT(*) FROM assignments WHERE shift_id = ? AND status NOT IN ('removed', 'replaced') FOR UPDATE"
            );
            $countStmt->execute([$shiftId]);
            $currentAssigned = (int)$countStmt->fetchColumn();

            if ($currentAssigned >= (int)$shift['max_volunteers']) {
                $this->db->rollBack();
                return ['success' => false, 'message' => "Ca trực đã đạt số lượng tối đa ({$shift['max_volunteers']} người)."];
            }

            // 2. Kiểm tra user đã có trong ca chưa
            $existStmt = $this->db->prepare(
                "SELECT id, status FROM assignments WHERE shift_id = ? AND user_id = ? LIMIT 1"
            );
            $existStmt->execute([$shiftId, $userId]);
            $existing = $existStmt->fetch();

            if ($existing && !in_array($existing['status'], ['removed', 'replaced'], true)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Tình nguyện viên này đã được phân công vào ca.'];
            }

            // 3. Kiểm tra kỹ năng bắt buộc
            $reqSkills = $shiftModel->getRequiredSkills($shiftId);
            $userSkillStmt = $this->db->prepare("SELECT skill_id FROM volunteer_skills WHERE user_id = ?");
            $userSkillStmt->execute([$userId]);
            $userSkillIds = $userSkillStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($reqSkills as $rs) {
                if ($rs['is_mandatory'] && !in_array((int)$rs['skill_id'], array_map('intval', $userSkillIds), true)) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => "Tình nguyện viên thiếu kỹ năng bắt buộc: {$rs['name']}."];
                }
            }

            // 4. Kiểm tra xung đột lịch
            if ($shiftModel->hasTimeConflict($userId, $shiftId)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Tình nguyện viên bị trùng lịch với một ca trực khác cùng thời điểm.'];
            }

            // 5. Thêm hoặc cập nhật bản ghi phân công
            $assignmentId = 0;
            if ($existing) {
                $this->db->prepare(
                    "UPDATE assignments SET status = 'assigned', assigned_by = ?, assigned_at = NOW(), updated_at = NOW() WHERE id = ?"
                )->execute([$assignedBy, $existing['id']]);
                $assignmentId = (int)$existing['id'];
            } else {
                $this->db->prepare(
                    "INSERT INTO assignments (shift_id, user_id, status, assigned_by, assigned_at) VALUES (?, ?, 'assigned', ?, NOW())"
                )->execute([$shiftId, $userId, $assignedBy]);
                $assignmentId = (int)$this->db->lastInsertId();
            }

            // 6. Tạo bản ghi attendance
            $this->db->prepare(
                "INSERT INTO attendance (assignment_id, user_id, shift_id, status) VALUES (?, ?, ?, 'pending')
                 ON DUPLICATE KEY UPDATE status = 'pending', updated_at = NOW()"
            )->execute([$assignmentId, $userId, $shiftId]);

            // 7. Ghi lịch sử phân công
            $this->db->prepare(
                "INSERT INTO assignment_history (assignment_id, changed_by, action, new_data, note)
                 VALUES (?, ?, 'add', ?, ?)"
            )->execute([
                $assignmentId,
                $assignedBy,
                json_encode(['shift_id' => $shiftId, 'user_id' => $userId]),
                'Thêm phân công thủ công bởi điều phối viên'
            ]);

            // 8. Gửi thông báo đến TNV
            $this->db->prepare(
                "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'assignment', ?, ?, '/volunteer/schedule')"
            )->execute([
                $userId,
                'Bạn được phân công vào ca trực mới',
                "Bạn vừa được phân công vào ca trực '{$shift['name']}' ngày {$shift['date']}. Vui lòng kiểm tra và xác nhận tham gia."
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Đã thêm phân công tình nguyện viên thành công!'];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[ManualAdd Error] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }

    /**
     * Gỡ tình nguyện viên khỏi ca
     */
    public function manualRemove(int $assignmentId, int $changedBy, string $note = ''): array
    {
        $stmt = $this->db->prepare("SELECT a.*, s.name as shift_name FROM assignments a JOIN shifts s ON a.shift_id = s.id WHERE a.id = ? LIMIT 1");
        $stmt->execute([$assignmentId]);
        $assignment = $stmt->fetch();

        if (!$assignment || in_array($assignment['status'], ['removed', 'replaced'], true)) {
            return ['success' => false, 'message' => 'Phân công không tồn tại hoặc đã bị gỡ.'];
        }

        try {
            $this->db->beginTransaction();

            $this->db->prepare(
                "UPDATE assignments SET status = 'removed', updated_at = NOW() WHERE id = ?"
            )->execute([$assignmentId]);

            // Ghi lịch sử
            $this->db->prepare(
                "INSERT INTO assignment_history (assignment_id, changed_by, action, old_data, note)
                 VALUES (?, ?, 'remove', ?, ?)"
            )->execute([
                $assignmentId,
                $changedBy,
                json_encode($assignment),
                $note ?: 'Gỡ phân công bởi điều phối viên'
            ]);

            // Thông báo tới TNV
            $this->db->prepare(
                "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'assignment', ?, ?, '/volunteer/schedule')"
            )->execute([
                $assignment['user_id'],
                'Thay đổi phân công ca trực',
                "Bạn đã được rút khỏi ca trực '{$assignment['shift_name']}'."
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Đã gỡ tình nguyện viên khỏi ca thành công.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    /**
     * TNV xác nhận tham gia ca
     */
    public function confirmAttendance(int $assignmentId, int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM assignments WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$assignmentId, $userId]);
        $assignment = $stmt->fetch();

        if (!$assignment) {
            return ['success' => false, 'message' => 'Không tìm thấy phân công.'];
        }

        $this->db->prepare("UPDATE assignments SET status = 'confirmed', updated_at = NOW() WHERE id = ?")->execute([$assignmentId]);
        $this->db->prepare("UPDATE attendance SET status = 'confirmed', confirmed_at = NOW(), updated_at = NOW() WHERE assignment_id = ?")->execute([$assignmentId]);

        return ['success' => true, 'message' => 'Đã xác nhận tham gia ca trực thành công!'];
    }
}
