<?php
/**
 * app/Services/ImprovedGreedy.php
 * Thuật toán phân công cải tiến (Improved Greedy - Most Constrained First & Workload Balancing)
 * 1. Sắp xếp ca khó trước (ít ứng viên hợp lệ, nhiều kỹ năng bắt buộc, giờ bắt đầu sớm)
 * 2. Ưu tiên ứng viên có số ca ít hơn để cân bằng khối lượng công việc
 * 3. Thứ tự ổn định để đảm bảo kết quả tái tạo được (deterministic)
 */

declare(strict_types=1);

namespace App\Services;

class ImprovedGreedy
{
    /**
     * Chạy thuật toán Improved Greedy
     *
     * @param array $shifts Danh sách ca trực
     * @param array $volunteers Danh sách tình nguyện viên
     * @param array $registrations Danh sách đăng ký ca (shift_id => [user_id, ...])
     * @return array Kết quả phân công và chỉ số đo lường
     */
    public function execute(array $shifts, array $volunteers, array $registrations): array
    {
        $startTime = microtime(true);

        // Khởi tạo
        $assignments = [];     // shift_id => [user_id, ...]
        $userAssignments = []; // user_id => [shift_id, ...]
        $shiftResults = [];

        foreach ($shifts as $s) {
            $assignments[(int)$s['id']] = [];
        }
        foreach ($volunteers as $v) {
            $userAssignments[(int)$v['id']] = [];
        }

        $volunteerMap = [];
        foreach ($volunteers as $v) {
            $volunteerMap[(int)$v['id']] = $v;
        }

        // Bước 1 & 2: Xác định số ứng viên hợp lệ tiềm năng cho từng ca
        $shiftEligibility = [];
        foreach ($shifts as $shift) {
            $sid = (int)$shift['id'];
            $mandatorySkills = $shift['mandatory_skills'] ?? [];
            $registeredUsers = $registrations[$sid] ?? [];

            $eligibleCount = 0;
            foreach ($registeredUsers as $uid) {
                $uid = (int)$uid;
                if (!isset($volunteerMap[$uid])) continue;
                $vol = $volunteerMap[$uid];

                // Đếm các ứng viên đã đăng ký và đủ kỹ năng bắt buộc.
                if ($this->hasMandatorySkills($vol['skills'] ?? [], $mandatorySkills)) {
                    $eligibleCount++;
                }
            }
            $shiftEligibility[$sid] = $eligibleCount;
        }

        // Bước 3: Sắp xếp ca theo độ khó
        // - Ít ứng viên hợp lệ trước (Most Constrained First)
        // - Nếu bằng nhau: ca có nhiều kỹ năng bắt buộc hơn trước
        // - Nếu bằng nhau: ca bắt đầu sớm hơn trước
        // - Nếu bằng nhau: theo ID tăng dần (deterministic)
        $sortedShifts = $shifts;
        usort($sortedShifts, function($a, $b) use ($shiftEligibility) {
            $aId = (int)$a['id'];
            $bId = (int)$b['id'];

            // 1. Ít ứng viên hợp lệ trước
            $diffEligible = ($shiftEligibility[$aId] ?? 0) <=> ($shiftEligibility[$bId] ?? 0);
            if ($diffEligible !== 0) return $diffEligible;

            // 2. Nhiều kỹ năng bắt buộc hơn trước
            $aMandatoryCount = count($a['mandatory_skills'] ?? []);
            $bMandatoryCount = count($b['mandatory_skills'] ?? []);
            if ($aMandatoryCount !== $bMandatoryCount) {
                return $bMandatoryCount <=> $aMandatoryCount; // giảm dần
            }

            // 3. Giờ bắt đầu sớm hơn
            if ($a['date'] !== $b['date']) return strcmp($a['date'], $b['date']);
            if ($a['time_start'] !== $b['time_start']) return strcmp($a['time_start'], $b['time_start']);

            // 4. ID ổn định
            return $aId <=> $bId;
        });

        // Bước 4 & 5: Duyệt và phân công cho từng ca
        foreach ($sortedShifts as $shift) {
            $sid = (int)$shift['id'];
            $maxVolunteers = (int)$shift['max_volunteers'];
            $minVolunteers = (int)$shift['min_volunteers'];
            $mandatorySkills = $shift['mandatory_skills'] ?? [];

            $registeredUserIds = $registrations[$sid] ?? [];

            // Lọc ứng viên hợp lệ cho ca này tại thời điểm hiện tại
            $validCandidates = [];
            foreach ($registeredUserIds as $uid) {
                $uid = (int)$uid;
                if (!isset($volunteerMap[$uid])) continue;
                $vol = $volunteerMap[$uid];

                // Kiểm tra kỹ năng
                if (!$this->hasMandatorySkills($vol['skills'] ?? [], $mandatorySkills)) {
                    continue;
                }

                // Kiểm tra xung đột lịch với các ca đã được gán trước đó
                if ($this->hasOverlapWithAssigned($uid, $shift, $userAssignments[$uid], $shifts)) {
                    continue;
                }

                $validCandidates[] = $uid;
            }

            // Bước 5 & 6: Sắp xếp ứng viên hợp lệ
            // - Ưu tiên người có ít ca đã phân công nhất (cân bằng tải)
            // - Nếu bằng nhau: theo user_id tăng dần để đảm bảo kết quả tái tạo được
            usort($validCandidates, function($uA, $uB) use ($userAssignments) {
                $countA = count($userAssignments[$uA] ?? []);
                $countB = count($userAssignments[$uB] ?? []);
                if ($countA !== $countB) {
                    return $countA <=> $countB; // ít ca hơn đứng trước
                }
                return $uA <=> $uB; // thứ tự ổn định
            });

            // Phân công tối đa đến $maxVolunteers
            foreach ($validCandidates as $candidateId) {
                if (count($assignments[$sid]) >= $maxVolunteers) {
                    break;
                }
                $assignments[$sid][] = $candidateId;
                $userAssignments[$candidateId][] = $sid;
            }

            // Bước 8: Ghi nhận trạng thái và nguyên nhân nếu thiếu
            $assignedCount = count($assignments[$sid]);
            $isSufficient = ($assignedCount >= $minVolunteers);
            $shortage = max(0, $minVolunteers - $assignedCount);
            $reason = '';

            if (!$isSufficient) {
                if (empty($registeredUserIds)) {
                    $reason = 'Không có tình nguyện viên nào đăng ký ca này.';
                } elseif (empty($validCandidates)) {
                    $reason = "Đã có " . count($registeredUserIds) . " người đăng ký nhưng không ai thỏa mãn kỹ năng bắt buộc và điều kiện không trùng lịch.";
                } else {
                    $reason = "Thiếu $shortage người. Chỉ có $assignedCount ứng viên hợp lệ đáp ứng đủ điều kiện (yêu cầu tối thiểu $minVolunteers).";
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

        // Tính các chỉ số
        $metrics = $this->calculateMetrics($shifts, $assignments, $userAssignments, $durationMs);

        return [
            'algorithm'       => 'improved_greedy',
            'algorithm_name'  => 'Improved Greedy (Thuật toán cải tiến MCF + Workload Balancing)',
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
