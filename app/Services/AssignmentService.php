<?php
/**
 * app/Services/AssignmentService.php
 * Dịch vụ điều phối phân công: chuẩn bị dữ liệu, chạy thuật toán, lưu phiên, xác nhận phương án và so sánh thuật toán
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Database;
use PDO;

class AssignmentService
{
    private PDO $db;
    private BaselineGreedy $baseline;
    private ImprovedGreedy $improved;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->baseline = new BaselineGreedy();
        $this->improved = new ImprovedGreedy();
    }

    /**
     * Thu thập dữ liệu đầu vào chuẩn hóa cho một hoạt động
     */
    public function prepareInputData(int $activityId): array
    {
        // 1. Danh sách ca trực và kỹ năng bắt buộc
        $shiftStmt = $this->db->prepare(
            "SELECT s.id, s.activity_id, s.name, s.date, s.time_start, s.time_end,
                    s.min_volunteers, s.max_volunteers, s.status
             FROM shifts s
             WHERE s.activity_id = ? AND s.status != 'cancelled'
             ORDER BY s.date ASC, s.time_start ASC"
        );
        $shiftStmt->execute([$activityId]);
        $shifts = $shiftStmt->fetchAll();

        foreach ($shifts as &$shift) {
            $sId = (int)$shift['id'];
            $reqStmt = $this->db->prepare(
                "SELECT skill_id FROM shift_required_skills WHERE shift_id = ? AND is_mandatory = 1"
            );
            $reqStmt->execute([$sId]);
            $shift['mandatory_skills'] = array_map('intval', $reqStmt->fetchAll(PDO::FETCH_COLUMN));
        }
        unset($shift);

        // 2. Danh sách đăng ký ca (shift_id => [user_id, ...])
        $regStmt = $this->db->prepare(
            "SELECT r.shift_id, r.user_id
             FROM registrations r
             JOIN shifts s ON r.shift_id = s.id
             WHERE s.activity_id = ? AND r.status = 'approved'
             ORDER BY r.registered_at ASC, r.id ASC"
        );
        $regStmt->execute([$activityId]);
        $registrations = [];
        while ($row = $regStmt->fetch()) {
            $sId = (int)$row['shift_id'];
            $uId = (int)$row['user_id'];
            $registrations[$sId][] = $uId;
        }

        $pendingRegStmt = $this->db->prepare(
            "SELECT r.shift_id, COUNT(*) AS pending_count
             FROM registrations r
             JOIN shifts s ON r.shift_id = s.id
             WHERE s.activity_id = ? AND r.status = 'pending'
             GROUP BY r.shift_id"
        );
        $pendingRegStmt->execute([$activityId]);
        $pendingRegistrationCounts = [];
        while ($row = $pendingRegStmt->fetch()) {
            $pendingRegistrationCounts[(int)$row['shift_id']] = (int)$row['pending_count'];
        }

        // 3. Lấy tất cả user ID đã đăng ký vào bất kỳ ca nào của hoạt động này
        $allRegisteredUserIds = [];
        foreach ($registrations as $uids) {
            foreach ($uids as $uid) {
                $allRegisteredUserIds[$uid] = true;
            }
        }
        $userIdList = array_keys($allRegisteredUserIds);
        $userIdList = array_values(array_filter(
            array_map('intval', $userIdList),
            static fn(int $id): bool => $id > 0
        ));

        // 4. Lấy thông tin tình nguyện viên và kỹ năng
        $volunteers = [];
        if (!empty($userIdList)) {
            $placeholders = implode(',', array_fill(0, count($userIdList), '?'));

            // Users
            $userStmt = $this->db->prepare(
                "SELECT u.id, u.full_name, u.email, u.phone
                 FROM users u
                 WHERE u.id IN ($placeholders) AND u.status = 'active'"
            );
            $userStmt->execute($userIdList);
            $users = $userStmt->fetchAll();

            $workloadStmt = $this->db->prepare(
                "SELECT a.user_id,
                        COALESCE(SUM(TIMESTAMPDIFF(MINUTE, s.time_start, s.time_end)), 0) AS workload_minutes
                 FROM assignments a
                 JOIN shifts s ON s.id = a.shift_id
                 WHERE a.user_id IN ($placeholders)
                   AND a.status IN ('assigned', 'confirmed')
                   AND s.date >= CURRENT_DATE
                 GROUP BY a.user_id"
            );
            $workloadStmt->execute($userIdList);
            $workloadMinutes = [];
            while ($workload = $workloadStmt->fetch()) {
                $workloadMinutes[(int)$workload['user_id']] = (int)$workload['workload_minutes'];
            }

            // Skills
            $skillsQuery = $this->db->prepare(
                "SELECT user_id, skill_id, level FROM volunteer_skills WHERE user_id IN ($placeholders)"
            );
            $skillsQuery->execute($userIdList);
            $userSkills = [];
            while ($sk = $skillsQuery->fetch()) {
                $userSkills[(int)$sk['user_id']][] = (int)$sk['skill_id'];
            }

            foreach ($users as $u) {
                $uId = (int)$u['id'];
                $volunteers[] = [
                    'id'           => $uId,
                    'full_name'    => $u['full_name'],
                    'email'        => $u['email'],
                    'skills'       => $userSkills[$uId] ?? [],
                    'workload_minutes' => $workloadMinutes[$uId] ?? 0,
                ];
            }
        }

        return [
            'shifts'        => $shifts,
            'volunteers'    => $volunteers,
            'registrations' => $registrations,
            'pending_registration_counts' => $pendingRegistrationCounts,
        ];
    }

    public function haveApprovedRegistrationsChanged(array $run): bool
    {
        $snapshot = json_decode($run['input_snapshot'] ?? '', true);
        if (!is_array($snapshot) || !isset($snapshot['registrations']) || !is_array($snapshot['registrations'])) {
            return true;
        }

        return $this->normalizeRegistrations($snapshot['registrations'])
            !== $this->getApprovedRegistrations((int)$run['activity_id']);
    }

    /**
     * Chạy thuật toán và lưu phiên (assignment_runs) ở trạng thái preview
     */
    public function runAlgorithm(int $activityId, string $algorithm, int $runBy): int
    {
        $input = $this->prepareInputData($activityId);

        $result = ($algorithm === 'improved_greedy')
            ? $this->improved->execute($input['shifts'], $input['volunteers'], $input['registrations'])
            : $this->baseline->execute($input['shifts'], $input['volunteers'], $input['registrations']);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO assignment_runs (activity_id, run_by, algorithm, status, input_snapshot, config, result_summary, duration_ms)
                 VALUES (?, ?, ?, 'preview', ?, ?, ?, ?)"
            );
            $stmt->execute([
                $activityId,
                $runBy,
                $algorithm,
                json_encode($input, JSON_UNESCAPED_UNICODE),
                json_encode(['version' => '1.0', 'activity_id' => $activityId], JSON_UNESCAPED_UNICODE),
                json_encode($result['metrics'], JSON_UNESCAPED_UNICODE),
                $result['duration_ms'],
            ]);
            $runId = (int)$this->db->lastInsertId();

            $itemStmt = $this->db->prepare(
                "INSERT INTO assignment_run_items (run_id, shift_id, assigned_count, required_min, is_sufficient, shortage, reason, assigned_users)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            foreach ($result['shift_results'] as $sr) {
                $itemStmt->execute([
                    $runId,
                    $sr['shift_id'],
                    $sr['assigned_count'],
                    $sr['min_volunteers'],
                    $sr['is_sufficient'] ? 1 : 0,
                    $sr['shortage'],
                    $sr['reason'],
                    json_encode($sr['assigned_users']),
                ]);
            }

            $this->db->commit();
            return $runId;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function getUnassignedRegistrants(array $input, array $items): array
    {
        $volunteersById = [];
        foreach ($input['volunteers'] ?? [] as $volunteer) {
            $volunteersById[(int)$volunteer['id']] = $volunteer;
        }

        $shiftsById = [];
        foreach ($input['shifts'] ?? [] as $shift) {
            $shiftsById[(int)$shift['id']] = $shift;
        }

        $assignmentsByShift = [];
        $assignedShiftsByUser = [];
        foreach ($items as $item) {
            $shiftId = (int)$item['shift_id'];
            $assignedUsers = json_decode($item['assigned_users'] ?? '[]', true) ?: [];
            $assignmentsByShift[$shiftId] = array_map('intval', $assignedUsers);
            foreach ($assignmentsByShift[$shiftId] as $userId) {
                $assignedShiftsByUser[$userId][] = $shiftId;
            }
        }

        $unassignedByShift = [];
        foreach ($input['registrations'] ?? [] as $shiftId => $registeredUserIds) {
            $shiftId = (int)$shiftId;
            $shift = $shiftsById[$shiftId] ?? null;
            if (!$shift) {
                continue;
            }

            foreach ($registeredUserIds as $registeredUserId) {
                $userId = (int)$registeredUserId;
                if (in_array($userId, $assignmentsByShift[$shiftId] ?? [], true)) {
                    continue;
                }

                $volunteer = $volunteersById[$userId] ?? null;
                if (!$volunteer) {
                    $reason = 'Tài khoản không còn hoạt động hoặc không đủ điều kiện.';
                } elseif (!$this->hasRequiredSkills(
                    $volunteer['skills'] ?? [],
                    $shift['mandatory_skills'] ?? []
                )) {
                    $reason = 'Thiếu kỹ năng bắt buộc của ca.';
                } else {
                    $overlappingShiftName = $this->findOverlappingAssignedShift(
                        $shift,
                        $assignedShiftsByUser[$userId] ?? [],
                        $shiftsById
                    );
                    if ($overlappingShiftName !== null) {
                        $reason = "Trùng giờ với ca đã phân công: {$overlappingShiftName}.";
                    } elseif (count($assignmentsByShift[$shiftId] ?? []) >= (int)$shift['max_volunteers']) {
                        $reason = 'Ca đã đạt số lượng tối đa.';
                    } else {
                        $reason = 'Không xác định được nguyên nhân; vui lòng chạy lại phương án.';
                    }
                }

                $unassignedByShift[$shiftId][] = [
                    'full_name' => $volunteer['full_name'] ?? 'Tình nguyện viên',
                    'reason' => $reason,
                ];
            }
        }

        return $unassignedByShift;
    }

    private function hasRequiredSkills(array $userSkills, array $requiredSkills): bool
    {
        $userSkillIds = array_map('intval', $userSkills);
        foreach ($requiredSkills as $requiredSkillId) {
            if (!in_array((int)$requiredSkillId, $userSkillIds, true)) {
                return false;
            }
        }
        return true;
    }

    private function findOverlappingAssignedShift(array $currentShift, array $assignedShiftIds, array $shiftsById): ?string
    {
        foreach ($assignedShiftIds as $assignedShiftId) {
            $assignedShift = $shiftsById[(int)$assignedShiftId] ?? null;
            if (
                $assignedShift
                && $assignedShift['date'] === $currentShift['date']
                && $assignedShift['time_start'] < $currentShift['time_end']
                && $assignedShift['time_end'] > $currentShift['time_start']
            ) {
                return (string)$assignedShift['name'];
            }
        }

        return null;
    }

    /**
     * Xác nhận phương án phân công (chuyển kết quả từ preview sang assignments thực tế)
     */
    public function confirmRun(int $runId, int $confirmedBy): array
    {
        try {
            $this->db->beginTransaction();

            $runStmt = $this->db->prepare(
                "SELECT * FROM assignment_runs WHERE id = ? FOR UPDATE"
            );
            $runStmt->execute([$runId]);
            $run = $runStmt->fetch();
            if (!$run || $run['status'] !== 'preview') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Phiên chạy không hợp lệ hoặc đã được xử lý trước đó.'];
            }

            $activityStmt = $this->db->prepare(
                "SELECT status FROM activities WHERE id = ? FOR UPDATE"
            );
            $activityStmt->execute([(int)$run['activity_id']]);
            $activityStatus = $activityStmt->fetchColumn();
            if ($activityStatus === false || in_array($activityStatus, ['cancelled', 'completed'], true)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Không thể xác nhận phân công cho hoạt động đã hủy hoặc kết thúc.'];
            }

            $shiftStmt = $this->db->prepare(
                "SELECT id, activity_id, name, date, time_start, time_end,
                        min_volunteers, max_volunteers, status
                 FROM shifts
                 WHERE activity_id = ?
                 ORDER BY id
                 FOR UPDATE"
            );
            $shiftStmt->execute([(int)$run['activity_id']]);
            $shiftsById = [];
            foreach ($shiftStmt->fetchAll() as $shift) {
                $shiftsById[(int)$shift['id']] = $shift;
            }

            $inputSnapshot = json_decode($run['input_snapshot'] ?? '', true);
            if (!is_array($inputSnapshot) || !is_array($inputSnapshot['registrations'] ?? null)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Dữ liệu xem trước không hợp lệ. Vui lòng chạy lại thuật toán.'];
            }
            $snapshotShiftsById = [];
            foreach ($inputSnapshot['shifts'] ?? [] as $snapshotShift) {
                if (isset($snapshotShift['id'])) {
                    $snapshotShiftsById[(int)$snapshotShift['id']] = $snapshotShift;
                }
            }
            $currentShiftIds = [];
            foreach ($shiftsById as $shiftId => $shift) {
                if ($shift['status'] !== 'cancelled') {
                    $currentShiftIds[] = (int)$shiftId;
                }
            }
            $snapshotShiftIds = array_keys($snapshotShiftsById);
            sort($currentShiftIds);
            sort($snapshotShiftIds);
            if ($currentShiftIds !== $snapshotShiftIds) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Danh sách ca trực đã thay đổi sau khi xem trước. Vui lòng chạy lại thuật toán.'];
            }
            if (
                $this->normalizeRegistrations($inputSnapshot['registrations'])
                !== $this->getApprovedRegistrations((int)$run['activity_id'], true)
            ) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Danh sách đơn đã duyệt đã thay đổi sau lần chạy này. Vui lòng chạy lại thuật toán.'];
            }

            $itemsStmt = $this->db->prepare(
                "SELECT * FROM assignment_run_items WHERE run_id = ? ORDER BY shift_id FOR UPDATE"
            );
            $itemsStmt->execute([$runId]);
            $items = $itemsStmt->fetchAll();
            if ($items === []) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Phiên chạy không có kết quả phân công để xác nhận.'];
            }
            $approvedRegistrations = $this->normalizeRegistrations($inputSnapshot['registrations']);
            $proposedByShift = [];
            $proposedShiftsByUser = [];
            $allUserIds = [];
            $allShiftIds = [];

            foreach ($items as $item) {
                $shiftId = (int)$item['shift_id'];
                $shift = $shiftsById[$shiftId] ?? null;
                $snapshotShift = $snapshotShiftsById[$shiftId] ?? null;
                $assignedUsers = json_decode($item['assigned_users'] ?? '[]', true);
                if (
                    !$shift
                    || !$snapshotShift
                    || $shift['status'] === 'cancelled'
                    || $shift['status'] !== ($snapshotShift['status'] ?? null)
                    || $shift['date'] !== ($snapshotShift['date'] ?? null)
                    || $shift['time_start'] !== ($snapshotShift['time_start'] ?? null)
                    || $shift['time_end'] !== ($snapshotShift['time_end'] ?? null)
                    || (int)$shift['min_volunteers'] !== (int)($snapshotShift['min_volunteers'] ?? -1)
                    || (int)$shift['max_volunteers'] !== (int)($snapshotShift['max_volunteers'] ?? -1)
                    || !is_array($assignedUsers)
                    || (int)$item['assigned_count'] !== count($assignedUsers)
                    || count($assignedUsers) > (int)$shift['max_volunteers']
                ) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Thông tin ca hoặc số lượng trong phương án đã thay đổi. Vui lòng chạy lại thuật toán.'];
                }

                $uniqueUserIds = [];
                foreach ($assignedUsers as $assignedUserId) {
                    $userId = filter_var($assignedUserId, FILTER_VALIDATE_INT);
                    if ($userId === false || $userId <= 0 || isset($uniqueUserIds[$userId])) {
                        $this->db->rollBack();
                        return ['success' => false, 'message' => 'Danh sách tình nguyện viên trong phương án không hợp lệ.'];
                    }
                    if (!in_array($userId, $approvedRegistrations[$shiftId] ?? [], true)) {
                        $this->db->rollBack();
                        return ['success' => false, 'message' => 'Phương án có tình nguyện viên chưa được duyệt đăng ký ca này.'];
                    }
                    $uniqueUserIds[$userId] = true;
                    $allUserIds[$userId] = true;
                    $proposedShiftsByUser[$userId][] = $shift;
                }
                $proposedByShift[$shiftId] = array_keys($uniqueUserIds);
                $allShiftIds[$shiftId] = true;
            }

            if ($allUserIds !== []) {
                $userIds = array_keys($allUserIds);
                sort($userIds);
                $userPlaceholders = implode(',', array_fill(0, count($userIds), '?'));
                $activeUsersStmt = $this->db->prepare(
                    "SELECT u.id
                     FROM users u
                     JOIN user_roles ur ON ur.user_id = u.id
                     JOIN roles r ON r.id = ur.role_id AND r.name = 'volunteer'
                     WHERE u.status = 'active' AND u.id IN ($userPlaceholders)
                     ORDER BY u.id
                     FOR UPDATE"
                );
                $activeUsersStmt->execute($userIds);
                $activeUserIds = array_map('intval', $activeUsersStmt->fetchAll(PDO::FETCH_COLUMN));
                if (array_diff($userIds, $activeUserIds) !== []) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Có tình nguyện viên không còn hoạt động trong phương án. Vui lòng chạy lại thuật toán.'];
                }

                $skillStmt = $this->db->prepare(
                    "SELECT shift_id, skill_id
                     FROM shift_required_skills
                     WHERE is_mandatory = 1 AND shift_id IN (" . implode(',', array_fill(0, count($allShiftIds), '?')) . ")
                     ORDER BY shift_id, skill_id
                     FOR UPDATE"
                );
                $shiftIds = array_keys($allShiftIds);
                sort($shiftIds);
                $skillStmt->execute($shiftIds);
                $requiredSkillsByShift = [];
                foreach ($skillStmt->fetchAll() as $requiredSkill) {
                    $requiredSkillsByShift[(int)$requiredSkill['shift_id']][] = (int)$requiredSkill['skill_id'];
                }
                foreach ($requiredSkillsByShift as &$requiredSkillIds) {
                    sort($requiredSkillIds);
                }
                unset($requiredSkillIds);
                foreach ($proposedByShift as $shiftId => $_userIdsForShift) {
                    $snapshotSkillIds = array_map(
                        'intval',
                        $snapshotShiftsById[$shiftId]['mandatory_skills'] ?? []
                    );
                    sort($snapshotSkillIds);
                    if ($snapshotSkillIds !== ($requiredSkillsByShift[$shiftId] ?? [])) {
                        $this->db->rollBack();
                        return ['success' => false, 'message' => 'Kỹ năng yêu cầu của ca đã thay đổi sau khi xem trước. Vui lòng chạy lại thuật toán.'];
                    }
                }

                $volunteerSkillStmt = $this->db->prepare(
                    "SELECT user_id, skill_id
                     FROM volunteer_skills
                     WHERE user_id IN ($userPlaceholders)
                     ORDER BY user_id, skill_id
                     FOR UPDATE"
                );
                $volunteerSkillStmt->execute($userIds);
                $volunteerSkills = [];
                foreach ($volunteerSkillStmt->fetchAll() as $skill) {
                    $volunteerSkills[(int)$skill['user_id']][] = (int)$skill['skill_id'];
                }

                foreach ($proposedByShift as $shiftId => $userIdsForShift) {
                    foreach ($userIdsForShift as $userId) {
                        foreach ($requiredSkillsByShift[$shiftId] ?? [] as $requiredSkillId) {
                            if (!in_array($requiredSkillId, $volunteerSkills[$userId] ?? [], true)) {
                                $this->db->rollBack();
                                return ['success' => false, 'message' => 'Kỹ năng tình nguyện viên đã thay đổi sau khi xem trước. Vui lòng chạy lại thuật toán.'];
                            }

                            $conflictStmt = $this->db->prepare(
                                "SELECT conflict_assignment.id
                                 FROM assignments conflict_assignment
                                 JOIN shifts conflict_shift ON conflict_shift.id = conflict_assignment.shift_id
                                 WHERE conflict_assignment.user_id = ?
                                   AND conflict_assignment.status NOT IN ('removed', 'replaced', 'absent')
                                   AND conflict_assignment.shift_id != ?
                                   AND conflict_shift.date = ?
                                   AND conflict_shift.time_start < ?
                                   AND conflict_shift.time_end > ?
                                 LIMIT 1
                                 FOR UPDATE"
                            );
                            $conflictStmt->execute([
                                $userId,
                                $shiftId,
                                $shiftsById[$shiftId]['date'],
                                $shiftsById[$shiftId]['time_end'],
                                $shiftsById[$shiftId]['time_start'],
                            ]);
                            if ($conflictStmt->fetchColumn()) {
                                $this->db->rollBack();
                                return ['success' => false, 'message' => 'Tình nguyện viên đã được phân công ca trùng lịch. Vui lòng chạy lại phương án.'];
                            }

                            $existingStmt = $this->db->prepare(
                                "SELECT id FROM assignments
                                 WHERE shift_id = ? AND user_id = ?
                                   AND status NOT IN ('removed', 'replaced', 'absent')
                                 LIMIT 1
                                 FOR UPDATE"
                            );
                            $existingStmt->execute([$shiftId, $userId]);
                            if ($existingStmt->fetchColumn()) {
                                $this->db->rollBack();
                                return ['success' => false, 'message' => 'Tình nguyện viên đã có phân công ở ca này. Vui lòng chạy lại phương án.'];
                            }
                        }
                    }

                    $assignedCountStmt = $this->db->prepare(
                        "SELECT COUNT(*) FROM assignments
                         WHERE shift_id = ? AND status NOT IN ('removed', 'replaced', 'absent')
                         FOR UPDATE"
                    );
                    $assignedCountStmt->execute([$shiftId]);
                    $existingCount = (int)$assignedCountStmt->fetchColumn();
                    if ($existingCount + count($userIdsForShift) > (int)$shiftsById[$shiftId]['max_volunteers']) {
                        $this->db->rollBack();
                        return ['success' => false, 'message' => 'Sức chứa ca đã thay đổi sau khi xem trước. Vui lòng chạy lại phương án.'];
                    }
                }

                foreach ($proposedShiftsByUser as $userShifts) {
                    for ($i = 0, $count = count($userShifts); $i < $count; $i++) {
                        for ($j = $i + 1; $j < $count; $j++) {
                            if (
                                $userShifts[$i]['date'] === $userShifts[$j]['date']
                                && $userShifts[$i]['time_start'] < $userShifts[$j]['time_end']
                                && $userShifts[$i]['time_end'] > $userShifts[$j]['time_start']
                            ) {
                                $this->db->rollBack();
                                return ['success' => false, 'message' => 'Phương án có ca trùng lịch cho cùng tình nguyện viên. Vui lòng chạy lại thuật toán.'];
                            }
                        }
                    }
                }
            }

            $updateRunStmt = $this->db->prepare(
                "UPDATE assignment_runs
                 SET status = 'confirmed', confirmed_at = NOW()
                 WHERE id = ? AND status = 'preview'"
            );
            $updateRunStmt->execute([$runId]);
            if ($updateRunStmt->rowCount() !== 1) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Phiên chạy đã được xác nhận bởi một điều phối viên khác.'];
            }

            // Thêm các bản ghi assignments thực tế
            $assignStmt = $this->db->prepare(
                "INSERT INTO assignments (run_id, shift_id, user_id, status, assigned_by, assigned_at)
                 VALUES (?, ?, ?, 'assigned', ?, NOW())
                 ON DUPLICATE KEY UPDATE status = 'assigned', run_id = VALUES(run_id), assigned_by = VALUES(assigned_by), updated_at = NOW()"
            );

            $attStmt = $this->db->prepare(
                "INSERT INTO attendance (assignment_id, user_id, shift_id, status)
                 VALUES (?, ?, ?, 'pending')
                 ON DUPLICATE KEY UPDATE status = 'pending', confirmed_at = NULL,
                     hours_worked = NULL, note = NULL, updated_at = NOW()"
            );

            $notifStmt = $this->db->prepare(
                "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'assignment', ?, ?, '/volunteer/schedule')"
            );

            foreach ($items as $item) {
                $shiftId = (int)$item['shift_id'];
                $assignedUsers = json_decode($item['assigned_users'] ?? '[]', true) ?: [];

                foreach ($assignedUsers as $uid) {
                    $uid = (int)$uid;
                    $assignStmt->execute([$runId, $shiftId, $uid, $confirmedBy]);

                    // Tìm assignment ID
                    $findStmt = $this->db->prepare("SELECT id FROM assignments WHERE shift_id = ? AND user_id = ? LIMIT 1");
                    $findStmt->execute([$shiftId, $uid]);
                    $asgnId = (int)$findStmt->fetchColumn();

                    if ($asgnId > 0) {
                        $attStmt->execute([$asgnId, $uid, $shiftId]);
                        $this->db->prepare(
                            "INSERT INTO assignment_history (assignment_id, changed_by, action, new_data, note)
                             VALUES (?, ?, 'add', ?, ?)"
                        )->execute([
                            $asgnId,
                            $confirmedBy,
                            json_encode(['shift_id' => $shiftId, 'user_id' => $uid], JSON_UNESCAPED_UNICODE),
                            'Phân công tự động được xác nhận'
                        ]);
                    }

                    // Gửi thông báo
                    $notifStmt->execute([
                        $uid,
                        'Bạn đã được phân công ca trực',
                        'Bạn vừa được phân công vào ca trực thuộc phương án đã được duyệt. Hãy vào lịch cá nhân để xác nhận tham gia.'
                    ]);
                }
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Đã lưu và áp dụng phương án phân công thành công!'];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[AssignmentService] Không thể xác nhận phương án: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Không thể xác nhận phương án phân công. Vui lòng thử lại.'];
        }
    }

    private function getApprovedRegistrations(int $activityId, bool $forUpdate = false): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.shift_id, r.user_id, r.status
             FROM registrations r
             JOIN shifts s ON s.id = r.shift_id
             WHERE s.activity_id = ?" . ($forUpdate ? " ORDER BY r.shift_id, r.user_id FOR UPDATE" : " AND r.status = 'approved' ORDER BY r.shift_id, r.user_id")
        );
        $stmt->execute([$activityId]);
        $registrations = [];
        while ($row = $stmt->fetch()) {
            if (($row['status'] ?? 'approved') === 'approved') {
                $registrations[(int)$row['shift_id']][] = (int)$row['user_id'];
            }
        }
        return $this->normalizeRegistrations($registrations);
    }

    private function normalizeRegistrations(array $registrations): array
    {
        $normalized = [];
        foreach ($registrations as $shiftId => $userIds) {
            $normalizedUserIds = array_map('intval', is_array($userIds) ? $userIds : []);
            sort($normalizedUserIds);
            $normalized[(int)$shiftId] = $normalizedUserIds;
        }
        ksort($normalized);
        return $normalized;
    }

    /**
     * So sánh thực nghiệm hai thuật toán trên cùng một bộ dữ liệu đầu vào
     */
    public function compareAlgorithms(int $activityId): array
    {
        $input = $this->prepareInputData($activityId);

        $baselineResult = $this->baseline->execute($input['shifts'], $input['volunteers'], $input['registrations']);
        $improvedResult = $this->improved->execute($input['shifts'], $input['volunteers'], $input['registrations']);

        return [
            'activity_id' => $activityId,
            'input_summary' => [
                'total_shifts'      => count($input['shifts']),
                'total_volunteers'  => count($input['volunteers']),
                'total_registrations' => array_sum(array_map('count', $input['registrations'])),
            ],
            'baseline' => $baselineResult,
            'improved' => $improvedResult,
            'comparison' => [
                'rate_diff'          => $improvedResult['metrics']['sufficient_rate'] - $baselineResult['metrics']['sufficient_rate'],
                'deviation_diff'     => $baselineResult['metrics']['shift_deviation'] - $improvedResult['metrics']['shift_deviation'],
                'person_shifts_diff' => $improvedResult['metrics']['total_person_shifts'] - $baselineResult['metrics']['total_person_shifts'],
            ]
        ];
    }
}
