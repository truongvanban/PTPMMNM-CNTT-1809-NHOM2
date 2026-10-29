<?php
/**
 * app/Services/AssignmentService.php
 * Dịch vụ điều phối phân công: chuẩn bị dữ liệu, chạy thuật toán, lưu phiên, xác nhận phương án và so sánh thuật toán
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Database;
use PDO;
use Exception;

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

        // 4. Lấy thông tin kỹ năng và lịch rảnh của các tình nguyện viên này
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

            // Skills
            $skillsQuery = $this->db->prepare(
                "SELECT user_id, skill_id, level FROM volunteer_skills WHERE user_id IN ($placeholders)"
            );
            $skillsQuery->execute($userIdList);
            $userSkills = [];
            while ($sk = $skillsQuery->fetch()) {
                $userSkills[(int)$sk['user_id']][] = (int)$sk['skill_id'];
            }

            // Availability
            $availQuery = $this->db->prepare(
                "SELECT user_id, date, time_start, time_end FROM availability WHERE user_id IN ($placeholders)"
            );
            $availQuery->execute($userIdList);
            $userAvails = [];
            while ($av = $availQuery->fetch()) {
                $userAvails[(int)$av['user_id']][] = $av;
            }

            foreach ($users as $u) {
                $uId = (int)$u['id'];
                $volunteers[] = [
                    'id'           => $uId,
                    'full_name'    => $u['full_name'],
                    'email'        => $u['email'],
                    'skills'       => $userSkills[$uId] ?? [],
                    'availability' => $userAvails[$uId] ?? [],
                ];
            }
        }

        return [
            'shifts'        => $shifts,
            'volunteers'    => $volunteers,
            'registrations' => $registrations,
        ];
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

        // Lưu vào assignment_runs
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

        // Lưu từng item vào assignment_run_items
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

        return $runId;
    }

    /**
     * Xác nhận phương án phân công (chuyển kết quả từ preview sang assignments thực tế)
     */
    public function confirmRun(int $runId, int $confirmedBy): array
    {
        $stmt = $this->db->prepare("SELECT * FROM assignment_runs WHERE id = ? LIMIT 1");
        $stmt->execute([$runId]);
        $run = $stmt->fetch();

        if (!$run || $run['status'] === 'confirmed') {
            return ['success' => false, 'message' => 'Phiên chạy không hợp lệ hoặc đã được xác nhận trước đó.'];
        }

        $itemsStmt = $this->db->prepare("SELECT * FROM assignment_run_items WHERE run_id = ?");
        $itemsStmt->execute([$runId]);
        $items = $itemsStmt->fetchAll();

        try {
            $this->db->beginTransaction();

            // Cập nhật trạng thái run
            $this->db->prepare(
                "UPDATE assignment_runs SET status = 'confirmed', confirmed_at = NOW() WHERE id = ?"
            )->execute([$runId]);

            // Thêm các bản ghi assignments thực tế
            $assignStmt = $this->db->prepare(
                "INSERT INTO assignments (run_id, shift_id, user_id, status, assigned_by, assigned_at)
                 VALUES (?, ?, ?, 'assigned', ?, NOW())
                 ON DUPLICATE KEY UPDATE status = 'assigned', run_id = VALUES(run_id), assigned_by = VALUES(assigned_by), updated_at = NOW()"
            );

            $attStmt = $this->db->prepare(
                "INSERT INTO attendance (assignment_id, user_id, shift_id, status)
                 VALUES (?, ?, ?, 'pending')
                 ON DUPLICATE KEY UPDATE status = 'pending', updated_at = NOW()"
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
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Lỗi khi lưu phương án: ' . $e->getMessage()];
        }
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
