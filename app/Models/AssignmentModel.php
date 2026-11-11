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

    public function getApprovedRegisteredVolunteersForShift(int $shiftId, int $page = 1, int $perPage = 15): array
    {
        $candidateSql =
            "SELECT u.id, u.full_name, u.email, u.phone, vp.student_id,
                    (SELECT COUNT(*) FROM assignments asgn
                     WHERE asgn.user_id = u.id AND asgn.status IN ('assigned','confirmed')) as current_shifts,
                    GROUP_CONCAT(DISTINCT sk.name ORDER BY sk.name SEPARATOR ', ') as skills
             FROM registrations reg
             INNER JOIN shifts target ON target.id = reg.shift_id
             INNER JOIN users u ON u.id = reg.user_id AND u.status = 'active'
             INNER JOIN user_roles ur ON ur.user_id = u.id
             INNER JOIN roles r ON r.id = ur.role_id AND r.name = 'volunteer'
             LEFT JOIN volunteer_profiles vp ON vp.user_id = u.id
             LEFT JOIN volunteer_skills vs ON vs.user_id = u.id
             LEFT JOIN skills sk ON sk.id = vs.skill_id
             WHERE reg.shift_id = ? AND reg.status IN ('approved', 'cancelled')
               AND NOT EXISTS (
                   SELECT 1 FROM assignments existing
                   WHERE existing.shift_id = reg.shift_id
                     AND existing.user_id = reg.user_id
                     AND existing.status NOT IN ('removed', 'replaced')
               )
               AND NOT EXISTS (
                   SELECT 1
                   FROM shift_required_skills required
                   WHERE required.shift_id = target.id
                     AND required.is_mandatory = 1
                     AND NOT EXISTS (
                         SELECT 1 FROM volunteer_skills matching
                         WHERE matching.user_id = u.id
                           AND matching.skill_id = required.skill_id
                     )
               )
               AND NOT EXISTS (
                   SELECT 1
                   FROM assignments conflict_assignment
                   INNER JOIN shifts conflict_shift ON conflict_shift.id = conflict_assignment.shift_id
                   WHERE conflict_assignment.user_id = u.id
                     AND conflict_assignment.status NOT IN ('removed', 'replaced', 'absent')
                     AND conflict_assignment.shift_id != target.id
                     AND TIMESTAMP(conflict_shift.date, conflict_shift.time_start) <
                         CASE
                             WHEN target.time_end <= target.time_start
                                 THEN DATE_ADD(TIMESTAMP(target.date, target.time_end), INTERVAL 1 DAY)
                             ELSE TIMESTAMP(target.date, target.time_end)
                         END
                     AND CASE
                             WHEN conflict_shift.time_end <= conflict_shift.time_start
                                 THEN DATE_ADD(TIMESTAMP(conflict_shift.date, conflict_shift.time_end), INTERVAL 1 DAY)
                             ELSE TIMESTAMP(conflict_shift.date, conflict_shift.time_end)
                         END > TIMESTAMP(target.date, target.time_start)
               )
             GROUP BY u.id";

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM ($candidateSql) eligible_volunteers");
        $countStmt->execute([$shiftId]);
        $total = (int)$countStmt->fetchColumn();
        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $stmt = $this->db->prepare("$candidateSql ORDER BY u.full_name ASC $limitSql");
        $stmt->execute([$shiftId]);
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

    public function getByUser(int $userId, bool $includeRemoved = false): array
    {
        $statusFilter = $includeRemoved
            ? "a.status <> 'replaced'"
            : "a.status NOT IN ('removed', 'replaced')";
        $stmt = $this->db->prepare(
            "SELECT a.*, a.status as assignment_status,
                    s.name as shift_name, s.date as shift_date, s.time_start, s.time_end,
                    act.title as activity_title, act.location as activity_location, act.id as activity_id,
                    coord.full_name as coordinator_name, coord.email as coordinator_email, coord.phone as coordinator_phone,
                    att.status as attendance_status, att.id as attendance_id,
                    rr.id as replacement_request_id, rr.status as replacement_status,
                    rr.reason as replacement_reason, rr.coordinator_note as replacement_coordinator_note
             FROM assignments a
             JOIN shifts s ON a.shift_id = s.id
             JOIN activities act ON s.activity_id = act.id
             JOIN users coord ON coord.id = act.coordinator_id
             LEFT JOIN attendance att ON att.assignment_id = a.id
             LEFT JOIN replacement_requests rr
                    ON rr.assignment_id = a.id
                   AND rr.requester_id = a.user_id
                   AND rr.id = (
                       SELECT MAX(rr_latest.id)
                       FROM replacement_requests rr_latest
                       WHERE rr_latest.assignment_id = a.id
                         AND rr_latest.requester_id = a.user_id
                   )
             WHERE a.user_id = ? AND $statusFilter
             ORDER BY s.date ASC, s.time_start ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getByUserPage(int $userId, int $page = 1, int $perPage = 15): array
    {
        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM assignments WHERE user_id = ? AND status NOT IN ('removed', 'replaced')"
        );
        $countStmt->execute([$userId]);
        $total = (int)$countStmt->fetchColumn();
        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $stmt = $this->db->prepare(
            "SELECT a.*, a.status as assignment_status,
                    s.name as shift_name, s.date as shift_date, s.time_start, s.time_end,
                    act.title as activity_title, act.location as activity_location, act.id as activity_id,
                    coord.full_name as coordinator_name, coord.email as coordinator_email, coord.phone as coordinator_phone,
                    att.status as attendance_status, att.id as attendance_id,
                    rr.id as replacement_request_id, rr.status as replacement_status,
                    rr.reason as replacement_reason, rr.coordinator_note as replacement_coordinator_note
             FROM assignments a
             JOIN shifts s ON a.shift_id = s.id
             JOIN activities act ON s.activity_id = act.id
             JOIN users coord ON coord.id = act.coordinator_id
             LEFT JOIN attendance att ON att.assignment_id = a.id
             LEFT JOIN replacement_requests rr
                    ON rr.assignment_id = a.id
                   AND rr.requester_id = a.user_id
                   AND rr.id = (
                       SELECT MAX(rr_latest.id)
                       FROM replacement_requests rr_latest
                       WHERE rr_latest.assignment_id = a.id
                         AND rr_latest.requester_id = a.user_id
                   )
             WHERE a.user_id = ? AND a.status NOT IN ('removed', 'replaced')
             ORDER BY s.date ASC, s.time_start ASC $limitSql"
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

        $ownsTransaction = !$this->db->inTransaction();
        try {
            if ($ownsTransaction) {
                $this->db->beginTransaction();
            }

            $shiftLockStmt = $this->db->prepare(
                "SELECT s.id, s.status AS shift_status, a.status AS activity_status
                 FROM shifts s
                 JOIN activities a ON a.id = s.activity_id
                 WHERE s.id = ?
                 FOR UPDATE"
            );
            $shiftLockStmt->execute([$shiftId]);
            $lockedShift = $shiftLockStmt->fetch();
            if (
                !$lockedShift
                || $lockedShift['shift_status'] !== 'open'
                || !in_array($lockedShift['activity_status'], ['open', 'ongoing'], true)
            ) {
                if ($ownsTransaction) {
                    $this->db->rollBack();
                }
                return ['success' => false, 'message' => 'Không thể thêm người vào ca hoặc hoạt động đã đóng.'];
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
            $volunteerStmt->execute([$userId]);
            if (!$volunteerStmt->fetchColumn()) {
                if ($ownsTransaction) {
                    $this->db->rollBack();
                }
                return ['success' => false, 'message' => 'Tài khoản không phải tình nguyện viên đang hoạt động.'];
            }

            $registrationStmt = $this->db->prepare(
                "SELECT id, status FROM registrations
                 WHERE shift_id = ? AND user_id = ? AND status IN ('approved', 'cancelled')
                 LIMIT 1
                 FOR UPDATE"
            );
            $registrationStmt->execute([$shiftId, $userId]);
            $registration = $registrationStmt->fetch();
            if (!$registration) {
                if ($ownsTransaction) {
                    $this->db->rollBack();
                }
                return ['success' => false, 'message' => 'Chỉ có thể phân công tình nguyện viên đã đăng ký ca này.'];
            }

            // 1. Khóa hàng và kiểm tra số người hiện tại (Concurrency control)
            $countStmt = $this->db->prepare(
                "SELECT COUNT(*) FROM assignments
                 WHERE shift_id = ? AND status NOT IN ('removed', 'replaced', 'absent')
                 FOR UPDATE"
            );
            $countStmt->execute([$shiftId]);
            $currentAssigned = (int)$countStmt->fetchColumn();

            if ($currentAssigned >= (int)$shift['max_volunteers']) {
                if ($ownsTransaction) {
                    $this->db->rollBack();
                }
                return ['success' => false, 'message' => "Ca trực đã đạt số lượng tối đa ({$shift['max_volunteers']} người)."];
            }

            // 2. Kiểm tra user đã có trong ca chưa
            $existStmt = $this->db->prepare(
                "SELECT id, status FROM assignments WHERE shift_id = ? AND user_id = ? LIMIT 1"
            );
            $existStmt->execute([$shiftId, $userId]);
            $existing = $existStmt->fetch();

            if ($existing && !in_array($existing['status'], ['removed', 'replaced'], true)) {
                if ($ownsTransaction) {
                    $this->db->rollBack();
                }
                return ['success' => false, 'message' => 'Tình nguyện viên này đã được phân công vào ca.'];
            }

            // 3. Kiểm tra kỹ năng bắt buộc
            $reqSkills = $shiftModel->getRequiredSkills($shiftId);
            $userSkillStmt = $this->db->prepare(
                "SELECT skill_id FROM volunteer_skills WHERE user_id = ? FOR UPDATE"
            );
            $userSkillStmt->execute([$userId]);
            $userSkillIds = $userSkillStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($reqSkills as $rs) {
                if ($rs['is_mandatory'] && !in_array((int)$rs['skill_id'], array_map('intval', $userSkillIds), true)) {
                    if ($ownsTransaction) {
                        $this->db->rollBack();
                    }
                    return ['success' => false, 'message' => "Tình nguyện viên thiếu kỹ năng bắt buộc: {$rs['name']}."];
                }
            }

            // Đơn đăng ký đã duyệt xác nhận người đăng ký có thể tham gia ca này.
            // Vẫn kiểm tra các phân công hiện có để ngăn trùng lịch.
            if ($shiftModel->hasTimeConflict($userId, $shiftId)) {
                if ($ownsTransaction) {
                    $this->db->rollBack();
                }
                return ['success' => false, 'message' => 'Tình nguyện viên bị trùng lịch với một ca trực khác cùng thời điểm.'];
            }

            if ($registration['status'] === 'cancelled') {
                $this->db->prepare(
                    "UPDATE registrations SET status = 'approved', updated_at = NOW() WHERE id = ?"
                )->execute([(int)$registration['id']]);
            }

            // 6. Thêm hoặc cập nhật bản ghi phân công
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
                 ON DUPLICATE KEY UPDATE status = 'pending', confirmed_at = NULL,
                     hours_worked = NULL, note = NULL, updated_at = NOW()"
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

            if ($ownsTransaction) {
                $this->db->commit();
            }
            return ['success' => true, 'message' => 'Đã thêm phân công tình nguyện viên thành công!'];

        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[ManualAdd Error] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Không thể thêm phân công. Vui lòng thử lại.'];
        }
    }

    public function removeExpiredUnconfirmed(): int
    {
        $removedCount = 0;

        while (true) {
            try {
                $this->db->beginTransaction();
                $stmt = $this->db->query(
                    "SELECT a.id AS assignment_id, a.user_id, a.assigned_at,
                            COALESCE(a.assigned_by, act.coordinator_id) AS changed_by,
                            s.id AS shift_id, s.name AS shift_name, s.date AS shift_date,
                            s.time_start, s.time_end, act.title AS activity_title,
                            act.coordinator_id, u.full_name AS volunteer_name
                     FROM assignments a
                     JOIN shifts s ON s.id = a.shift_id
                     JOIN activities act ON act.id = s.activity_id
                     JOIN users u ON u.id = a.user_id
                     LEFT JOIN attendance att ON att.assignment_id = a.id
                     WHERE a.status = 'assigned'
                       AND COALESCE(att.status, 'pending') = 'pending'
                       AND a.assigned_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                     ORDER BY a.assigned_at ASC, a.id ASC
                     LIMIT 1
                     FOR UPDATE"
                );
                $assignment = $stmt->fetch();

                if (!$assignment) {
                    $this->db->commit();
                    return $removedCount;
                }

                $updateStmt = $this->db->prepare(
                    "UPDATE assignments SET status = 'removed', updated_at = NOW()
                     WHERE id = ? AND status = 'assigned'"
                );
                $updateStmt->execute([(int)$assignment['assignment_id']]);
                if ($updateStmt->rowCount() !== 1) {
                    $this->db->commit();
                    continue;
                }

                $this->db->prepare(
                    "UPDATE registrations SET status = 'cancelled', updated_at = NOW()
                     WHERE user_id = ? AND shift_id = ? AND status = 'approved'"
                )->execute([(int)$assignment['user_id'], (int)$assignment['shift_id']]);

                $this->db->prepare(
                    "INSERT INTO assignment_history (assignment_id, changed_by, action, old_data, note)
                     VALUES (?, ?, 'remove', ?, ?)"
                )->execute([
                    (int)$assignment['assignment_id'],
                    (int)$assignment['changed_by'],
                    json_encode($assignment),
                    'Tự động gỡ do tình nguyện viên chưa xác nhận sau 24 giờ'
                ]);

                $date = date('d/m/Y', strtotime($assignment['shift_date']));
                $time = substr($assignment['time_start'], 0, 5) . ' - ' . substr($assignment['time_end'], 0, 5);
                $message = "Phân công ca '{$assignment['shift_name']}' thuộc hoạt động '{$assignment['activity_title']}' ngày {$date} ({$time}) đã bị gỡ vì bạn chưa xác nhận trong vòng 24 giờ.";
                $notificationStmt = $this->db->prepare(
                    "INSERT INTO notifications (user_id, type, title, message, link)
                     VALUES (?, 'assignment', ?, ?, ?)"
                );
                $notificationStmt->execute([
                    (int)$assignment['user_id'],
                    'Phân công đã hết hạn xác nhận',
                    $message,
                    '/volunteer/my-registrations'
                ]);
                $notificationStmt->execute([
                    (int)$assignment['coordinator_id'],
                    'Tình nguyện viên chưa xác nhận phân công',
                    "{$assignment['volunteer_name']} chưa xác nhận ca '{$assignment['shift_name']}' thuộc hoạt động '{$assignment['activity_title']}' trong vòng 24 giờ. Hệ thống đã gỡ phân công để bạn có thể chọn người khác.",
                    '/coordinator/assignment/manual/' . (int)$assignment['shift_id']
                ]);

                $this->db->commit();
                $removedCount++;
            } catch (\Throwable $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                throw $e;
            }
        }
    }

    /**
     * Gỡ tình nguyện viên khỏi ca
     */
    public function manualRemove(int $assignmentId, int $changedBy, string $note = ''): array
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "SELECT a.*, s.name AS shift_name, s.date AS shift_date,
                        s.time_start, s.time_end, act.title AS activity_title
                 FROM assignments a
                 JOIN shifts s ON s.id = a.shift_id
                 JOIN activities act ON act.id = s.activity_id
                 WHERE a.id = ?
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute([$assignmentId]);
            $assignment = $stmt->fetch();

            if (!$assignment || in_array($assignment['status'], ['removed', 'replaced'], true)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Phân công không tồn tại hoặc đã bị gỡ.'];
            }

            $updateStmt = $this->db->prepare(
                "UPDATE assignments SET status = 'removed', updated_at = NOW()
                 WHERE id = ? AND status NOT IN ('removed', 'replaced')"
            );
            $updateStmt->execute([$assignmentId]);
            if ($updateStmt->rowCount() !== 1) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Phân công không tồn tại hoặc đã bị gỡ.'];
            }

            $this->db->prepare(
                "UPDATE registrations
                 SET status = 'cancelled', updated_at = NOW()
                 WHERE user_id = ? AND shift_id = ? AND status = 'approved'"
            )->execute([(int)$assignment['user_id'], (int)$assignment['shift_id']]);

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
                (int)$assignment['user_id'],
                'Phân công ca trực đã bị hủy',
                "Điều phối viên đã hủy phân công của bạn cho ca '{$assignment['shift_name']}' thuộc hoạt động '{$assignment['activity_title']}' vào ngày "
                    . date('d/m/Y', strtotime($assignment['shift_date'])) . ' ('
                    . substr($assignment['time_start'], 0, 5) . ' - '
                    . substr($assignment['time_end'], 0, 5) . '). Đơn đăng ký ca này đã được hủy. Bạn có thể đăng ký lại nếu ca vẫn mở.'
                    . ($note !== '' ? " Lý do: {$note}" : '')
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Đã gỡ tình nguyện viên khỏi ca thành công.'];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[ManualRemove Error] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    /**
     * TNV xác nhận tham gia ca
     */
    public function confirmAttendance(int $assignmentId, int $userId): array
    {
        $shiftModel = new ShiftModel();
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                "SELECT a.*, s.id AS shift_id
                 FROM assignments a
                 JOIN shifts s ON s.id = a.shift_id
                 WHERE a.id = ? AND a.user_id = ?
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute([$assignmentId, $userId]);
            $assignment = $stmt->fetch();

            if (!$assignment || $assignment['status'] !== 'assigned') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Không tìm thấy phân công đang chờ xác nhận.'];
            }

            $this->db->prepare("SELECT id FROM shifts WHERE id = ? FOR UPDATE")
                ->execute([(int)$assignment['shift_id']]);
            if ($shiftModel->hasTimeConflict($userId, (int)$assignment['shift_id'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Bạn đã có ca trực khác bị trùng giờ. Vui lòng liên hệ điều phối viên.'];
            }

            $this->db->prepare(
                "UPDATE assignments SET status = 'confirmed', updated_at = NOW()
                 WHERE id = ? AND user_id = ? AND status = 'assigned'"
            )->execute([$assignmentId, $userId]);
            $this->db->prepare(
                "UPDATE attendance SET status = 'confirmed', confirmed_at = NOW(), updated_at = NOW()
                 WHERE assignment_id = ?"
            )->execute([$assignmentId]);
            $this->db->commit();

            return ['success' => true, 'message' => 'Đã xác nhận tham gia ca trực thành công!'];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
