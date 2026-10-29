<?php
/**
 * app/Services/BaselineGreedy.php
 * Thuật toán phân công cơ sở (Baseline Greedy)
 * Duyệt tuần tự các ca và các ứng viên theo thứ tự đăng ký
 */

declare(strict_types=1);

namespace App\Services;

class BaselineGreedy
{
    /**
     * Chạy thuật toán Baseline Greedy
     *
     * @param array $shifts Danh sách ca trực kèm yêu cầu kỹ năng
     * @param array $volunteers Danh sách tình nguyện viên kèm kỹ năng
     * @param array $registrations Danh sách đăng ký ca (shift_id => [user_id, ...])
     * @return array Kết quả phân công và chỉ số đo lường
     */
    public function execute(array $shifts, array $volunteers, array $registrations): array
    {
        $startTime = microtime(true);

        // Khởi tạo cấu trúc lưu trữ
        $assignments = []; // shift_id => [user_id, ...]
        $userAssignments = []; // user_id => [shift_id, ...]
        $shiftResults = [];

        foreach ($shifts as $s) {
            $sid = (int)$s['id'];
            $assignments[$sid] = [];
        }
        foreach ($volunteers as $v) {
            $uid = (int)$v['id'];
            $userAssignments[$uid] = [];
        }

        // Tạo map tra cứu nhanh cho TNV
        $volunteerMap = [];
        foreach ($volunteers as $v) {
            $volunteerMap[(int)$v['id']] = $v;
        }

        // Baseline: Duyệt các ca theo thứ tự xác định (theo ngày và giờ bắt đầu)
        $sortedShifts = $shifts;
        usort($sortedShifts, function($a, $b) {
            if ($a['date'] !== $b['date']) return strcmp($a['date'], $b['date']);
            if ($a['time_start'] !== $b['time_start']) return strcmp($a['time_start'], $b['time_start']);
            return (int)$a['id'] <=> (int)$b['id'];
        });

        foreach ($sortedShifts as $shift) {
            $sid = (int)$shift['id'];
            $maxVolunteers = (int)$shift['max_volunteers'];
            $minVolunteers = (int)$shift['min_volunteers'];
            $mandatorySkills = $shift['mandatory_skills'] ?? [];

            // Lấy danh sách ứng viên đăng ký ca này theo thứ tự đăng ký ban đầu
            $candidateUserIds = $registrations[$sid] ?? [];

            foreach ($candidateUserIds as $uid) {
                $uid = (int)$uid;
                if (!isset($volunteerMap[$uid])) continue;

                // Nếu ca đã đầy -> dừng ca này
                if (count($assignments[$sid]) >= $maxVolunteers) {
                    break;
                }

                $vol = $volunteerMap[$uid];

                // Ràng buộc 1: Kỹ năng bắt buộc
                if (!$this->hasMandatorySkills($vol['skills'] ?? [], $mandatorySkills)) {
                    continue;
                }

                // Ràng buộc 2: Trùng lịch với ca đã được phân công trước đó của chính user này
                if ($this->hasOverlapWithAssigned($uid, $shift, $userAssignments[$uid], $sortedShifts)) {
                    continue;
                }

                // Hợp lệ -> Phân công
                $assignments[$sid][] = $uid;
                $userAssignments[$uid][] = $sid;
            }

            // Ghi nhận tình trạng ca
            $assignedCount = count($assignments[$sid]);
            $isSufficient = ($assignedCount >= $minVolunteers);
            $shortage = max(0, $minVolunteers - $assignedCount);
            $reason = '';
            if (!$isSufficient) {
                if (empty($candidateUserIds)) {
                    $reason = 'Không có tình nguyện viên nào đăng ký ca này.';
                } else {
                    $reason = "Thiếu $shortage người (chỉ có $assignedCount/$minVolunteers). Một số ứng viên không đủ kỹ năng hoặc xung đột thời gian.";
                }
            }

            $shiftResults[$sid] = [
                'shift_id'       => $sid,
                'shift_name'     => $shift['name'],
                'date'           => $shift['date'],
                'time_start'     => $shift['time_start'],
                'time_end'       => $shift['time_end'],
                'min_volunteers' => $minVolunteers,
                'max_volunteers' => $maxVolunteers,
                'assigned_count' => $assignedCount,
                'is_sufficient'  => $isSufficient,
                'shortage'       => $shortage,
                'reason'         => $reason,
                'assigned_users' => $assignments[$sid],
            ];
        }

        $durationMs = (int)round((microtime(true) - $startTime) * 1000);

        // Tính các chỉ số so sánh
        $metrics = $this->calculateMetrics($sortedShifts, $assignments, $userAssignments, $durationMs);

        return [
            'algorithm'       => 'baseline_greedy',
            'algorithm_name'  => 'Baseline Greedy (Thuật toán cơ sở)',
            'assignments'     => $assignments,
            'shift_results'   => $shiftResults,
            'metrics'         => $metrics,
            'duration_ms'     => $durationMs,
        ];
    }

    private function hasMandatorySkills(array $userSkills, array $mandatorySkills): bool
    {
        $userSkillIds = array_map('intval', $userSkills);
        foreach ($mandatorySkills as $ms) {
            if (!in_array((int)$ms, $userSkillIds, true)) {
                return false;
            }
        }
        return true;
    }

    private function hasOverlapWithAssigned(int $uid, array $currentShift, array $assignedShiftIds, array $allShifts): bool
    {
        $shiftMap = [];
        foreach ($allShifts as $s) {
            $shiftMap[(int)$s['id']] = $s;
        }

        foreach ($assignedShiftIds as $asid) {
            if (!isset($shiftMap[$asid])) continue;
            $other = $shiftMap[$asid];
            if ($other['date'] === $currentShift['date']) {
                // Kiểm tra giao thoa [startA, endA) và [startB, endB)
                if ($currentShift['time_start'] < $other['time_end'] && $currentShift['time_end'] > $other['time_start']) {
                    return true;
                }
            }
        }
        return false;
    }

    private function calculateMetrics(array $shifts, array $assignments, array $userAssignments, int $durationMs): array
    {
        $totalShifts = count($shifts);
        $sufficientCount = 0;
        $totalAssignedPersonShifts = 0;
        $violations = 0;

        foreach ($shifts as $s) {
            $sid = (int)$s['id'];
            $count = count($assignments[$sid] ?? []);
            $totalAssignedPersonShifts += $count;
            if ($count >= (int)$s['min_volunteers']) {
                $sufficientCount++;
            }
            if ($count > (int)$s['max_volunteers']) {
                $violations++;
            }
        }

        $sufficientRate = $totalShifts > 0 ? round(($sufficientCount / $totalShifts) * 100, 2) : 0.0;

        // Tính độ lệch số ca giữa các tình nguyện viên được phân công ít nhất 1 ca
        $counts = [];
        foreach ($userAssignments as $uid => $userShifts) {
            $c = count($userShifts);
            if ($c > 0) {
                $counts[] = $c;
            }
        }

        $shiftDeviation = 0;
        $maxShifts = 0;
        $minShifts = 0;
        if (!empty($counts)) {
            $maxShifts = max($counts);
            $minShifts = min($counts);
            $shiftDeviation = $maxShifts - $minShifts;
        }

        return [
            'total_shifts'           => $totalShifts,
            'sufficient_shifts'      => $sufficientCount,
            'sufficient_rate'        => $sufficientRate,
            'total_person_shifts'    => $totalAssignedPersonShifts,
            'violations_count'       => $violations,
            'active_volunteers_used' => count($counts),
            'max_shifts_per_user'    => $maxShifts,
            'min_shifts_per_user'    => $minShifts,
            'shift_deviation'        => $shiftDeviation,
            'duration_ms'            => $durationMs,
        ];
    }
}
