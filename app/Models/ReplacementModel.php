<?php
/**
 * app/Models/ReplacementModel.php
 * Quản lý yêu cầu báo vắng và thay người
 */

declare(strict_types=1);

namespace App\Models;

use PDO;
use Exception;

class ReplacementModel extends BaseModel
{
    protected string $table = 'replacement_requests';

    public function createRequest(int $assignmentId, int $requesterId, string $reason): array
    {
        // 1. Kiểm tra quyền sở hữu phân công
        $stmt = $this->db->prepare(
            "SELECT a.*, s.name as shift_name, s.date as shift_date, act.coordinator_id
             FROM assignments a
             JOIN shifts s ON a.shift_id = s.id
             JOIN activities act ON s.activity_id = act.id
             WHERE a.id = ? AND a.user_id = ? LIMIT 1"
        );
        $stmt->execute([$assignmentId, $requesterId]);
        $assignment = $stmt->fetch();

        if (!$assignment) {
            return ['success' => false, 'message' => 'Phân công không tồn tại hoặc không thuộc quyền của bạn.'];
        }

        if (in_array($assignment['status'], ['absent', 'replaced', 'removed'], true)) {
            return ['success' => false, 'message' => 'Phân công này đã bị hủy hoặc đã xử lý vắng mặt.'];
        }

        // 2. Kiểm tra xem đã có yêu cầu pending nào chưa
        $checkStmt = $this->db->prepare(
            "SELECT id FROM replacement_requests WHERE assignment_id = ? AND status = 'pending' LIMIT 1"
        );
        $checkStmt->execute([$assignmentId]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'message' => 'Yêu cầu báo vắng cho ca này đang được xử lý.'];
        }

        // 3. Tạo yêu cầu
        $this->db->prepare(
            "INSERT INTO replacement_requests (assignment_id, requester_id, reason, status)
             VALUES (?, ?, ?, 'pending')"
        )->execute([$assignmentId, $requesterId, $reason]);

        // Cập nhật trạng thái attendance thành absent tạm thời hoặc ghi chú
        $this->db->prepare("UPDATE attendance SET note = ? WHERE assignment_id = ?")
                 ->execute(["Đang gửi yêu cầu báo vắng: $reason", $assignmentId]);

        // Thông báo đến Điều phối viên
        $this->db->prepare(
            "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'replacement', ?, ?, '/coordinator/replacement')"
        )->execute([
            $assignment['coordinator_id'],
            'Yêu cầu báo vắng mới từ tình nguyện viên',
            "Tình nguyện viên đã báo vắng ca '{$assignment['shift_name']}' ngày {$assignment['shift_date']}. Lý do: $reason"
        ]);

        return ['success' => true, 'message' => 'Yêu cầu báo vắng đã được gửi tới Điều phối viên.'];
    }

    public function getPendingForCoordinator(int $coordinatorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT rr.*, u.full_name as requester_name, u.email as requester_email, u.phone as requester_phone,
                    s.id as shift_id, s.name as shift_name, s.date as shift_date, s.time_start, s.time_end,
                    act.title as activity_title, act.id as activity_id,
                    repl.full_name as replacement_name
             FROM replacement_requests rr
             JOIN assignments a ON rr.assignment_id = a.id
             JOIN shifts s ON a.shift_id = s.id
             JOIN activities act ON s.activity_id = act.id
             JOIN users u ON rr.requester_id = u.id
             LEFT JOIN users repl ON rr.replacement_id = repl.id
             WHERE act.coordinator_id = ? AND rr.status = 'pending'
             ORDER BY rr.created_at ASC"
        );
        $stmt->execute([$coordinatorId]);
        return $stmt->fetchAll();
    }

    public function countPendingForCoordinator(int $coordinatorId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM replacement_requests rr
             JOIN assignments a ON rr.assignment_id = a.id
             JOIN shifts s ON a.shift_id = s.id
             JOIN activities act ON s.activity_id = act.id
             WHERE act.coordinator_id = ? AND rr.status = 'pending'"
        );
        $stmt->execute([$coordinatorId]);
        return (int)$stmt->fetchColumn();
    }

    public function getPendingForCoordinatorPage(int $coordinatorId, int $page = 1, int $perPage = 15): array
    {
        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM replacement_requests rr
             JOIN assignments a ON rr.assignment_id = a.id
             JOIN shifts s ON a.shift_id = s.id
             JOIN activities act ON s.activity_id = act.id
             WHERE act.coordinator_id = ? AND rr.status = 'pending'"
        );
        $countStmt->execute([$coordinatorId]);
        $total = (int)$countStmt->fetchColumn();
        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $stmt = $this->db->prepare(
            "SELECT rr.*, u.full_name as requester_name, u.email as requester_email, u.phone as requester_phone,
                    s.id as shift_id, s.name as shift_name, s.date as shift_date, s.time_start, s.time_end,
                    act.title as activity_title, act.id as activity_id,
                    repl.full_name as replacement_name
             FROM replacement_requests rr
             JOIN assignments a ON rr.assignment_id = a.id
             JOIN shifts s ON a.shift_id = s.id
             JOIN activities act ON s.activity_id = act.id
             JOIN users u ON rr.requester_id = u.id
             LEFT JOIN users repl ON rr.replacement_id = repl.id
             WHERE act.coordinator_id = ? AND rr.status = 'pending'
             ORDER BY rr.created_at ASC $limitSql"
        );
        $stmt->execute([$coordinatorId]);
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
        ];
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT rr.*, s.name as shift_name, s.date as shift_date, s.time_start, s.time_end,
                    act.title as activity_title, repl.full_name as replacement_name
             FROM replacement_requests rr
             JOIN assignments a ON rr.assignment_id = a.id
             JOIN shifts s ON a.shift_id = s.id
             JOIN activities act ON s.activity_id = act.id
             LEFT JOIN users repl ON rr.replacement_id = repl.id
             WHERE rr.requester_id = ?
             ORDER BY rr.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findWithDetails(int $requestId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT rr.*, a.shift_id, a.user_id as assigned_user_id,
                    s.name as shift_name, s.date as shift_date, s.time_start, s.time_end,
                    s.min_volunteers, s.max_volunteers,
                    act.coordinator_id, act.title as activity_title,
                    u.full_name as requester_name
             FROM replacement_requests rr
             JOIN assignments a ON rr.assignment_id = a.id
             JOIN shifts s ON a.shift_id = s.id
             JOIN activities act ON s.activity_id = act.id
             JOIN users u ON rr.requester_id = u.id
             WHERE rr.id = ? LIMIT 1"
        );
        $stmt->execute([$requestId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Tìm danh sách ứng viên phù hợp để thay thế (đáp ứng kỹ năng + không trùng lịch + rảnh)
     */
    public function findEligibleReplacements(int $shiftId, int $excludeUserId): array
    {
        $shiftModel = new ShiftModel();
        $shift = $shiftModel->findWithDetails($shiftId);
        if (!$shift) return [];

        $reqSkills = $shiftModel->getRequiredSkills($shiftId);
        $mandatorySkillIds = [];
        foreach ($reqSkills as $rs) {
            if ($rs['is_mandatory']) {
                $mandatorySkillIds[] = (int)$rs['skill_id'];
            }
        }

        // Lấy tất cả tình nguyện viên active (trừ người yêu cầu báo vắng)
        $userStmt = $this->db->prepare(
            "SELECT u.id, u.full_name, u.email, u.phone, vp.student_id, vp.faculty,
                    (SELECT COUNT(*) FROM assignments asgn WHERE asgn.user_id = u.id AND asgn.status IN ('assigned','confirmed')) as current_assignments_count
             FROM users u
             INNER JOIN user_roles ur ON u.id = ur.user_id
             INNER JOIN roles r ON ur.role_id = r.id AND r.name = 'volunteer'
             LEFT JOIN volunteer_profiles vp ON u.id = vp.user_id
             WHERE u.status = 'active' AND u.id != ?
             ORDER BY current_assignments_count ASC, u.full_name ASC"
        );
        $userStmt->execute([$excludeUserId]);
        $allVolunteers = $userStmt->fetchAll();

        $eligible = [];
        foreach ($allVolunteers as $vol) {
            $uid = (int)$vol['id'];

            // 1. Kiểm tra đã có trong ca này chưa
            $inShiftStmt = $this->db->prepare(
                "SELECT id FROM assignments WHERE shift_id = ? AND user_id = ? AND status NOT IN ('removed','replaced') LIMIT 1"
            );
            $inShiftStmt->execute([$shiftId, $uid]);
            if ($inShiftStmt->fetch()) continue;

            // 2. Kiểm tra xung đột lịch
            if ($shiftModel->hasTimeConflict($uid, $shiftId)) continue;

            // 3. Kiểm tra kỹ năng bắt buộc
            if (!empty($mandatorySkillIds)) {
                $skillStmt = $this->db->prepare("SELECT skill_id FROM volunteer_skills WHERE user_id = ?");
                $skillStmt->execute([$uid]);
                $userSkills = array_map('intval', $skillStmt->fetchAll(PDO::FETCH_COLUMN));
                $hasAll = true;
                foreach ($mandatorySkillIds as $msId) {
                    if (!in_array($msId, $userSkills, true)) {
                        $hasAll = false;
                        break;
                    }
                }
                if (!$hasAll) continue;
            }

            // 4. Kiểm tra lịch rảnh (availability)
            $availStmt = $this->db->prepare(
                "SELECT id FROM availability
                 WHERE user_id = ? AND date = ?
                   AND time_start <= ? AND time_end >= ?
                 LIMIT 1"
            );
            $availStmt->execute([$uid, $shift['date'], $shift['time_start'], $shift['time_end']]);
            $isAvailable = (bool)$availStmt->fetch();

            $vol['is_available'] = $isAvailable;
            $eligible[] = $vol;
        }

        // Ưu tiên người rảnh trước, rồi đến ít ca hơn
        usort($eligible, function($a, $b) {
            if ($a['is_available'] !== $b['is_available']) {
                return $b['is_available'] <=> $a['is_available'];
            }
            return $a['current_assignments_count'] <=> $b['current_assignments_count'];
        });

        return $eligible;
    }

    /**
     * Phê duyệt yêu cầu thay người & gán người mới
     */
    public function approveReplacement(int $requestId, ?int $replacementUserId, int $reviewedBy, string $note = '', bool $isAdmin = false): array
    {
        $req = $this->findWithDetails($requestId);
        if (!$req || $req['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Yêu cầu không hợp lệ hoặc đã được xử lý.'];
        }
        if (!$isAdmin && (int)$req['coordinator_id'] !== $reviewedBy) {
            return ['success' => false, 'message' => 'Yêu cầu này không thuộc hoạt động của bạn.'];
        }

        $shiftId = (int)$req['shift_id'];
        $assignmentId = (int)$req['assignment_id'];
        $requesterId = (int)$req['requester_id'];

        try {
            $this->db->beginTransaction();

            // Nếu có chỉ định người thay
            if ($replacementUserId !== null && $replacementUserId > 0) {
                if ($replacementUserId === $requesterId) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Người báo vắng không thể tự thay thế chính mình.'];
                }

                $volunteerStmt = $this->db->prepare(
                    "SELECT u.id
                     FROM users u
                     INNER JOIN user_roles ur ON ur.user_id = u.id
                     INNER JOIN roles r ON r.id = ur.role_id AND r.name = 'volunteer'
                     WHERE u.id = ? AND u.status = 'active'
                     LIMIT 1"
                );
                $volunteerStmt->execute([$replacementUserId]);
                if (!$volunteerStmt->fetchColumn()) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Tài khoản được chọn không phải tình nguyện viên đang hoạt động.'];
                }

                // Kiểm tra lại ràng buộc người thay
                $shiftModel = new ShiftModel();
                $availabilityStmt = $this->db->prepare(
                    "SELECT id FROM availability
                     WHERE user_id = ? AND date = ? AND time_start <= ? AND time_end >= ?
                     LIMIT 1"
                );
                $availabilityStmt->execute([
                    $replacementUserId,
                    $req['shift_date'],
                    $req['time_start'],
                    $req['time_end'],
                ]);
                if (!$availabilityStmt->fetchColumn()) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Người thay thế chưa khai báo rảnh trong toàn bộ ca trực.'];
                }

                $existingAssignmentStmt = $this->db->prepare(
                    "SELECT id FROM assignments
                     WHERE shift_id = ? AND user_id = ? AND status NOT IN ('removed', 'replaced')
                     LIMIT 1"
                );
                $existingAssignmentStmt->execute([$shiftId, $replacementUserId]);
                if ($existingAssignmentStmt->fetchColumn()) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Người thay thế đã được phân công vào ca này.'];
                }

                if ($shiftModel->hasTimeConflict($replacementUserId, $shiftId)) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Người thay thế bị trùng lịch với ca khác.'];
                }

                $reqSkills = $shiftModel->getRequiredSkills($shiftId);
                $skillStmt = $this->db->prepare("SELECT skill_id FROM volunteer_skills WHERE user_id = ?");
                $skillStmt->execute([$replacementUserId]);
                $userSkills = array_map('intval', $skillStmt->fetchAll(PDO::FETCH_COLUMN));
                foreach ($reqSkills as $rs) {
                    if ($rs['is_mandatory'] && !in_array((int)$rs['skill_id'], $userSkills, true)) {
                        $this->db->rollBack();
                        return ['success' => false, 'message' => "Người thay thế thiếu kỹ năng: {$rs['name']}."];
                    }
                }

                // Gán người thay vào ca
                $this->db->prepare(
                    "INSERT INTO assignments (shift_id, user_id, status, assigned_by, assigned_at)
                     VALUES (?, ?, 'assigned', ?, NOW())"
                )->execute([$shiftId, $replacementUserId, $reviewedBy]);
                $newAssignId = (int)$this->db->lastInsertId();

                $this->db->prepare(
                    "INSERT INTO attendance (assignment_id, user_id, shift_id, status) VALUES (?, ?, ?, 'pending')"
                )->execute([$newAssignId, $replacementUserId, $shiftId]);

                // Thông báo tới người thay thế
                $this->db->prepare(
                    "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'replacement', ?, ?, '/volunteer/schedule')"
                )->execute([
                    $replacementUserId,
                    'Bạn được chỉ định thay thế ca trực',
                    "Bạn được phân công thay thế vào ca '{$req['shift_name']}' ngày {$req['shift_date']}."
                ]);
            }

            // Đánh dấu phân công cũ là replaced hoặc absent
            $newStatus = ($replacementUserId !== null && $replacementUserId > 0) ? 'replaced' : 'absent';
            $this->db->prepare("UPDATE assignments SET status = ?, updated_at = NOW() WHERE id = ?")
                     ->execute([$newStatus, $assignmentId]);
            $this->db->prepare("UPDATE attendance SET status = 'absent', updated_at = NOW() WHERE assignment_id = ?")
                     ->execute([$assignmentId]);

            // Cập nhật yêu cầu thay người
            $this->db->prepare(
                "UPDATE replacement_requests
                 SET status = 'approved', replacement_id = ?, coordinator_note = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW()
                 WHERE id = ?"
            )->execute([$replacementUserId ?: null, $note, $reviewedBy, $requestId]);

            // Thông báo tới người yêu cầu
            $this->db->prepare(
                "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'replacement', ?, ?, '/volunteer/replacement')"
            )->execute([
                $requesterId,
                'Yêu cầu báo vắng đã được duyệt',
                "Yêu cầu báo vắng ca '{$req['shift_name']}' ngày {$req['shift_date']} đã được điều phối viên chấp thuận."
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Đã phê duyệt yêu cầu báo vắng và phân công người thay thế thành công!'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    public function rejectReplacement(int $requestId, int $reviewedBy, string $note = '', bool $isAdmin = false): array
    {
        $req = $this->findWithDetails($requestId);
        if (!$req || $req['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Yêu cầu không hợp lệ.'];
        }
        if (!$isAdmin && (int)$req['coordinator_id'] !== $reviewedBy) {
            return ['success' => false, 'message' => 'Yêu cầu này không thuộc hoạt động của bạn.'];
        }

        $this->db->prepare(
            "UPDATE replacement_requests SET status = 'rejected', coordinator_note = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW() WHERE id = ?"
        )->execute([$note, $reviewedBy, $requestId]);

        // Thông báo
        $this->db->prepare(
            "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'replacement', ?, ?, '/volunteer/replacement')"
        )->execute([
            $req['requester_id'],
            'Yêu cầu báo vắng bị từ chối',
            "Yêu cầu báo vắng ca '{$req['shift_name']}' ngày {$req['shift_date']} đã bị từ chối. Lý do: $note"
        ]);

        return ['success' => true, 'message' => 'Đã từ chối yêu cầu báo vắng.'];
    }
}
