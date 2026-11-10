<?php
/**
 * app/Models/ReplacementModel.php
 * Quản lý yêu cầu báo vắng và thay người
 */

declare(strict_types=1);

namespace App\Models;

use PDO;

class ReplacementModel extends BaseModel
{
    protected string $table = 'replacement_requests';

    public function createRequest(int $assignmentId, int $requesterId, string $reason): array
    {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                "SELECT a.*, s.name AS shift_name, s.date AS shift_date, s.time_start,
                        s.status AS shift_status,
                        TIMESTAMPDIFF(MINUTE, NOW(), TIMESTAMP(s.date, s.time_start)) AS minutes_until_shift,
                        act.coordinator_id, act.status AS activity_status
                 FROM assignments a
                 JOIN shifts s ON a.shift_id = s.id
                 JOIN activities act ON s.activity_id = act.id
                 WHERE a.id = ? AND a.user_id = ?
                 FOR UPDATE"
            );
            $stmt->execute([$assignmentId, $requesterId]);
            $assignment = $stmt->fetch();

            if (!$assignment) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Phân công không tồn tại hoặc không thuộc quyền của bạn.'];
            }
            if (
                !in_array($assignment['status'], ['assigned', 'confirmed'], true)
                || $assignment['shift_status'] === 'cancelled'
                || in_array($assignment['activity_status'], ['cancelled', 'completed'], true)
            ) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Phân công này đã bị hủy hoặc không còn có thể báo vắng.'];
            }

            $checkStmt = $this->db->prepare(
                "SELECT id FROM replacement_requests WHERE assignment_id = ? AND status = 'pending' LIMIT 1 FOR UPDATE"
            );
            $checkStmt->execute([$assignmentId]);
            if ($checkStmt->fetchColumn()) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Yêu cầu báo vắng cho ca này đang được xử lý.'];
            }

            $this->db->prepare(
                "INSERT INTO replacement_requests (assignment_id, requester_id, reason, status)
                 VALUES (?, ?, ?, 'pending')"
            )->execute([$assignmentId, $requesterId, $reason]);
            $minutesUntilShift = (int)$assignment['minutes_until_shift'];
            if ($minutesUntilShift >= 0 && $minutesUntilShift < 12 * 60) {
                $pointsModel = new PointsModel();
                $settings = $pointsModel->getSettings();
                $penalty = $pointsModel->recordInTransaction(
                    $requesterId,
                    -(int)$settings['late_cancel_penalty'],
                    'late_cancel',
                    (int)$assignment['shift_id'],
                    "Phạt báo vắng dưới 12 giờ trước ca '{$assignment['shift_name']}' ngày {$assignment['shift_date']}"
                );
                if ($penalty !== false) {
                    $this->db->prepare(
                        "INSERT INTO notifications (user_id, type, title, message, link)
                         VALUES (?, 'system', ?, ?, '/volunteer/points')"
                    )->execute([
                        $requesterId,
                        'Đã áp dụng điểm phạt báo vắng trễ',
                        'Yêu cầu báo vắng của bạn được gửi trong vòng 12 giờ trước ca và bị trừ ' .
                            abs($penalty) . ' điểm.',
                    ]);
                }
            }

            $this->db->prepare("UPDATE attendance SET note = ? WHERE assignment_id = ?")
                ->execute(["Đang gửi yêu cầu báo vắng: $reason", $assignmentId]);

            $this->db->prepare(
                "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'replacement', ?, ?, '/coordinator/replacement')"
            )->execute([
                $assignment['coordinator_id'],
                'Yêu cầu báo vắng mới từ tình nguyện viên',
                "Tình nguyện viên đã báo vắng ca '{$assignment['shift_name']}' ngày {$assignment['shift_date']}. Lý do: $reason"
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Yêu cầu báo vắng đã được gửi tới Điều phối viên.'];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[ReplacementModel] Không thể tạo yêu cầu báo vắng: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Không thể gửi yêu cầu báo vắng. Vui lòng thử lại.'];
        }
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

    public function getByUserPage(int $userId, int $page = 1, int $perPage = 15): array
    {
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM replacement_requests WHERE requester_id = ?");
        $countStmt->execute([$userId]);
        $total = (int)$countStmt->fetchColumn();
        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $stmt = $this->db->prepare(
            "SELECT rr.*, s.name as shift_name, s.date as shift_date, s.time_start, s.time_end,
                    act.title as activity_title, repl.full_name as replacement_name
             FROM replacement_requests rr
             JOIN assignments a ON rr.assignment_id = a.id
             JOIN shifts s ON a.shift_id = s.id
             JOIN activities act ON s.activity_id = act.id
             LEFT JOIN users repl ON rr.replacement_id = repl.id
             WHERE rr.requester_id = ?
             ORDER BY rr.created_at DESC $limitSql"
        );
        $stmt->execute([$userId]);
        $data = $stmt->fetchAll();
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'visible_count' => count($data),
        ];
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
     * Tìm ứng viên thay thế phù hợp theo kỹ năng và lịch phân công hiện tại.
     */
    public function findEligibleReplacements(int $shiftId, int $excludeUserId): array
    {
        $shiftModel = new ShiftModel();
        $shift = $shiftModel->findWithDetails($shiftId);
        if (!$shift || $shift['status'] === 'cancelled' || $shift['activity_status'] === 'cancelled') {
            return [];
        }

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

            $eligible[] = $vol;
        }

        // Ưu tiên tình nguyện viên đang có ít ca phân công hơn.
        usort($eligible, function($a, $b) {
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

        try {
            $this->db->beginTransaction();
            $lockStmt = $this->db->prepare(
                "SELECT rr.id, rr.assignment_id, rr.requester_id, rr.status,
                        a.status AS assignment_status, s.id AS shift_id, s.status AS shift_status,
                        act.status AS activity_status, act.coordinator_id
                 FROM replacement_requests rr
                 JOIN assignments a ON a.id = rr.assignment_id
                 JOIN shifts s ON s.id = a.shift_id
                 JOIN activities act ON act.id = s.activity_id
                 WHERE rr.id = ? AND rr.status = 'pending'
                 FOR UPDATE"
            );
            $lockStmt->execute([$requestId]);
            $lockedRequest = $lockStmt->fetch();
            if (!$lockedRequest) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Yêu cầu không hợp lệ hoặc đã được xử lý.'];
            }

            $req = $this->findWithDetails($requestId);
            if (!$req || $req['status'] !== 'pending') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Yêu cầu không hợp lệ hoặc đã được xử lý.'];
            }
            if (!$isAdmin && (int)$req['coordinator_id'] !== $reviewedBy) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Yêu cầu này không thuộc hoạt động của bạn.'];
            }
            if (!in_array($lockedRequest['assignment_status'], ['assigned', 'confirmed'], true)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Phân công gốc đã được hủy hoặc thay thế.'];
            }
            if (
                $lockedRequest['shift_status'] === 'cancelled'
                || in_array($lockedRequest['activity_status'], ['cancelled', 'completed'], true)
            ) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Không thể xử lý thay người cho hoạt động hoặc ca đã kết thúc.'];
            }

            $shiftId = (int)$req['shift_id'];
            $assignmentId = (int)$req['assignment_id'];
            $requesterId = (int)$req['requester_id'];

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
                     . " FOR UPDATE"
                );
                $volunteerStmt->execute([$replacementUserId]);
                if (!$volunteerStmt->fetchColumn()) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Tài khoản được chọn không phải tình nguyện viên đang hoạt động.'];
                }

                // Kiểm tra lại ràng buộc người thay
                $shiftModel = new ShiftModel();
                $existingAssignmentStmt = $this->db->prepare(
                    "SELECT id, status FROM assignments
                     WHERE shift_id = ? AND user_id = ?
                     LIMIT 1
                     FOR UPDATE"
                );
                $existingAssignmentStmt->execute([$shiftId, $replacementUserId]);
                $existingAssignment = $existingAssignmentStmt->fetch();
                if ($existingAssignment && !in_array($existingAssignment['status'], ['removed', 'replaced', 'absent'], true)) {
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

                if ($existingAssignment) {
                    $newAssignId = (int)$existingAssignment['id'];
                    $this->db->prepare(
                        "UPDATE assignments
                         SET status = 'assigned', assigned_by = ?, assigned_at = NOW(), note = NULL, updated_at = NOW()
                         WHERE id = ?"
                    )->execute([$reviewedBy, $newAssignId]);
                } else {
                    $this->db->prepare(
                        "INSERT INTO assignments (shift_id, user_id, status, assigned_by, assigned_at)
                         VALUES (?, ?, 'assigned', ?, NOW())"
                    )->execute([$shiftId, $replacementUserId, $reviewedBy]);
                    $newAssignId = (int)$this->db->lastInsertId();
                }

                $this->db->prepare(
                    "INSERT INTO attendance (assignment_id, user_id, shift_id, status)
                     VALUES (?, ?, ?, 'pending')
                     ON DUPLICATE KEY UPDATE status = 'pending', confirmed_at = NULL,
                         hours_worked = NULL, note = NULL, updated_at = NOW()"
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
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[ReplacementModel] Không thể duyệt yêu cầu thay người: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Không thể xử lý yêu cầu thay người. Vui lòng thử lại.'];
        }
    }

    public function rejectReplacement(int $requestId, int $reviewedBy, string $note = '', bool $isAdmin = false): array
    {
        try {
            $this->db->beginTransaction();
            $requestStmt = $this->db->prepare(
                "SELECT rr.id, rr.assignment_id, rr.requester_id, rr.status,
                        s.name AS shift_name, s.date AS shift_date, act.coordinator_id
                 FROM replacement_requests rr
                 JOIN assignments a ON a.id = rr.assignment_id
                 JOIN shifts s ON s.id = a.shift_id
                 JOIN activities act ON act.id = s.activity_id
                 WHERE rr.id = ?
                 FOR UPDATE"
            );
            $requestStmt->execute([$requestId]);
            $req = $requestStmt->fetch();
            if (!$req || $req['status'] !== 'pending') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Yêu cầu không hợp lệ hoặc đã được xử lý.'];
            }
            if (!$isAdmin && (int)$req['coordinator_id'] !== $reviewedBy) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Yêu cầu này không thuộc hoạt động của bạn.'];
            }

            $updateStmt = $this->db->prepare(
                "UPDATE replacement_requests
                 SET status = 'rejected', coordinator_note = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW()
                 WHERE id = ? AND status = 'pending'"
            );
            $updateStmt->execute([$note, $reviewedBy, $requestId]);
            if ($updateStmt->rowCount() !== 1) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Yêu cầu đã được xử lý bởi một điều phối viên khác.'];
            }

            $this->db->prepare(
                "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'replacement', ?, ?, '/volunteer/schedule')"
            )->execute([
                (int)$req['requester_id'],
                'Yêu cầu báo vắng bị từ chối',
                "Yêu cầu báo vắng ca '{$req['shift_name']}' ngày {$req['shift_date']} đã bị từ chối. Lý do: $note"
            ]);
            $this->db->prepare(
                "UPDATE attendance SET note = NULL, updated_at = NOW() WHERE assignment_id = ? AND status = 'pending'"
            )->execute([(int)$req['assignment_id']]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Đã từ chối yêu cầu báo vắng. Phân công hiện tại không bị thay đổi.'];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
