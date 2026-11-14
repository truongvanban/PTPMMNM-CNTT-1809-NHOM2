<?php
/**
 * app/Services/ImprovedGreedy.php
 * Thuật toán phân công tối ưu (Smart Assignment: Skill Prioritization & Workload Balancing)
 * 1. Sắp xếp ca trực theo độ khó (ca có kỹ năng cần thiết và ít ứng viên hợp lệ xét trước)
 * 2. ƯU TIÊN ứng viên có kỹ năng cần thiết vào ca; nếu còn chỗ thì ứng viên khác vẫn được phân công
 * 3. Cân bằng khối lượng công việc (ưu tiên người có ít thời lượng phân công hơn)
 * 4. Đảm bảo ràng buộc cứng: không trùng lịch, đúng sĩ số min/max, chỉ phân công đơn đã duyệt
 */

declare(strict_types=1);

namespace App\Services;

class ImprovedGreedy
{
    /**
     * Chạy thuật toán phân công
     *
     * @param array $shifts Danh sách ca trực kèm kỹ năng cần thiết
     * @param array $volunteers Danh sách tình nguyện viên kèm kỹ năng và thời lượng hiện tại
     * @param array $registrations Danh sách đăng ký ca (shift_id => [user_id, ...])
     * @return array Kết quả phân công và chỉ số đo lường
     */
    public function execute(array $shifts, array $volunteers, array $registrations): array
    {
        $startTime = microtime(true);

        // Khởi tạo
        $assignments = [];         // shift_id => [user_id, ...]
        $userAssignments = [];     // user_id => [shift_id, ...]
        $userWorkloadMinutes = []; // user_id => assigned minutes
        $shiftResults = [];

        foreach ($shifts as $s) {
            $assignments[(int)$s['id']] = [];
        }
        foreach ($volunteers as $v) {
            $userId = (int)$v['id'];
            $userAssignments[$userId] = [];
            $userWorkloadMinutes[$userId] = (int)($v['workload_minutes'] ?? 0);
        }

        $volunteerMap = [];
        foreach ($volunteers as $v) {
            $volunteerMap[(int)$v['id']] = $v;
        }

        // Bước 1: Tính toán mức độ ràng buộc của từng ca
        // Đếm số ứng viên có kỹ năng phù hợp và tổng số ứng viên
        $shiftStats = [];
        foreach ($shifts as $shift) {
            $sid = (int)$shift['id'];
            $neededSkills = $shift['needed_skills'] ?? $shift['required_skills'] ?? $shift['mandatory_skills'] ?? [];
            $registeredUsers = $registrations[$sid] ?? [];

            $skilledCount = 0;
            $validRegisteredCount = 0;
            foreach ($registeredUsers as $uid) {
                $uid = (int)$uid;
                if (!isset($volunteerMap[$uid])) continue;
                $validRegisteredCount++;
                $userSkills = $volunteerMap[$uid]['skills'] ?? [];
                if (!empty($neededSkills) && !empty(array_intersect($userSkills, $neededSkills))) {
                    $skilledCount++;
                }
            }

            $shiftStats[$sid] = [
                'needed_skills_count' => count($neededSkills),
                'skilled_count'       => $skilledCount,
                'total_registered'    => $validRegisteredCount,
            ];
        }

        // Bước 2: Sắp xếp ca trực theo độ khó (Most Constrained First)
        // - Ca có kỹ năng cần thiết và ít người có kỹ năng đó được xếp trước
        // - Ca có nhiều kỹ năng cần thiết hơn xếp trước
        // - Ca có ít ứng viên đăng ký hơn xếp trước
        // - Giờ bắt đầu sớm hơn xếp trước
        // - ID tăng dần (deterministic)
        $sortedShifts = $shifts;
        usort($sortedShifts, function($a, $b) use ($shiftStats) {
            $aId = (int)$a['id'];
            $bId = (int)$b['id'];
            $statA = $shiftStats[$aId] ?? ['needed_skills_count' => 0, 'skilled_count' => 0, 'total_registered' => 0];
            $statB = $shiftStats[$bId] ?? ['needed_skills_count' => 0, 'skilled_count' => 0, 'total_registered' => 0];

            // 1. Nếu ca có yêu cầu kỹ năng, ca nào có ít ứng viên có kỹ năng hơn thì ưu tiên xếp trước
            $hasSkillsA = $statA['needed_skills_count'] > 0;
            $hasSkillsB = $statB['needed_skills_count'] > 0;
            if ($hasSkillsA !== $hasSkillsB) {
                return $hasSkillsB <=> $hasSkillsA; // Ca cần kỹ năng xếp trước
            }
            if ($hasSkillsA && $hasSkillsB) {
                if ($statA['skilled_count'] !== $statB['skilled_count']) {
                    return $statA['skilled_count'] <=> $statB['skilled_count']; // Ít người có kỹ năng hơn xếp trước
                }
            }

            // 2. Ca có ít ứng viên đăng ký hơn xếp trước
            if ($statA['total_registered'] !== $statB['total_registered']) {
                return $statA['total_registered'] <=> $statB['total_registered'];
            }

            // 3. Giờ bắt đầu sớm hơn
            if ($a['date'] !== $b['date']) return strcmp($a['date'], $b['date']);
            if ($a['time_start'] !== $b['time_start']) return strcmp($a['time_start'], $b['time_start']);

            // 4. ID ổn định
            return $aId <=> $bId;
        });

        // Bước 3: Duyệt và phân công cho từng ca
        foreach ($sortedShifts as $shift) {
            $sid = (int)$shift['id'];
            $maxVolunteers = (int)$shift['max_volunteers'];
            $minVolunteers = (int)$shift['min_volunteers'];
            $neededSkills = $shift['needed_skills'] ?? $shift['required_skills'] ?? $shift['mandatory_skills'] ?? [];
            $registeredUserIds = $registrations[$sid] ?? [];

            // Lọc ứng viên hợp lệ: chỉ loại trừ những người bị trùng lịch
            // (Không loại trừ người thiếu kỹ năng!)
            $validCandidates = [];
            foreach ($registeredUserIds as $uid) {
                $uid = (int)$uid;
                if (!isset($volunteerMap[$uid])) continue;

                // Kiểm tra xung đột lịch với các ca đã được gán trước đó
                if ($this->hasOverlapWithAssigned($uid, $shift, $userAssignments[$uid], $shifts)) {
                    continue;
                }

                $validCandidates[] = $uid;
            }

            // Bước 4: Sắp xếp ứng viên cho ca:
            // 1. ƯU TIÊN người có nhiều kỹ năng cần thiết trùng khớp hơn (Match count DESC)
            // 2. Ưu tiên người có tổng thời lượng ca ít hơn (Workload balancing ASC)
            // 3. User ID tăng dần (deterministic)
            usort($validCandidates, function($uA, $uB) use ($volunteerMap, $neededSkills, $userWorkloadMinutes) {
                $skillsA = $volunteerMap[$uA]['skills'] ?? [];
                $skillsB = $volunteerMap[$uB]['skills'] ?? [];

                $matchA = !empty($neededSkills) ? count(array_intersect($skillsA, $neededSkills)) : 0;
                $matchB = !empty($neededSkills) ? count(array_intersect($skillsB, $neededSkills)) : 0;

                // Ưu tiên 1: Kỹ năng cần thiết
                if ($matchA !== $matchB) {
                    return $matchB <=> $matchA;
                }

                // Ưu tiên 2: Cân bằng khối lượng công việc
                $workloadA = $userWorkloadMinutes[$uA] ?? 0;
                $workloadB = $userWorkloadMinutes[$uB] ?? 0;
                if ($workloadA !== $workloadB) {
                    return $workloadA <=> $workloadB;
                }

                // Ưu tiên 3: Thứ tự ổn định
                return $uA <=> $uB;
            });

            // Bước 5: Phân công tối đa đến $maxVolunteers
            $shiftDuration = $this->getShiftDurationMinutes($shift);
            foreach ($validCandidates as $candidateId) {
                if (count($assignments[$sid]) >= $maxVolunteers) {
                    break;
                }
                $assignments[$sid][] = $candidateId;
                $userAssignments[$candidateId][] = $sid;
                $userWorkloadMinutes[$candidateId] += $shiftDuration;
            }

            // Bước 6: Ghi nhận trạng thái và nguyên nhân nếu thiếu
            $assignedCount = count($assignments[$sid]);
            $isSufficient = ($assignedCount >= $minVolunteers);
            $shortage = max(0, $minVolunteers - $assignedCount);
            $reason = '';

            if (!$isSufficient) {
                if (empty($registeredUserIds)) {
                    $reason = 'Không có tình nguyện viên nào đăng ký ca này.';
                } elseif (empty($validCandidates)) {
                    $reason = 'Có ' . count($registeredUserIds) . ' người đăng ký nhưng tất cả đều bị trùng lịch với ca khác.';
                } else {
                    $reason = "Thiếu $shortage người. Ca cần tối thiểu $minVolunteers người nhưng chỉ có $assignedCount ứng viên hợp lệ không bị trùng lịch.";
                }
            }

            // Đếm số người được phân công có kỹ năng cần thiết
            $skilledAssignedCount = 0;
            if (!empty($neededSkills)) {
                foreach ($assignments[$sid] as $assignedUid) {
                    $uSkills = $volunteerMap[$assignedUid]['skills'] ?? [];
                    if (!empty(array_intersect($uSkills, $neededSkills))) {
                        $skilledAssignedCount++;
                    }
                }
            }

            $shiftResults[$sid] = [
                'shift_id'                => $sid,
                'shift_name'              => $shift['name'],
                'date'                    => $shift['date'],
                'time_start'              => $shift['time_start'],
                'time_end'                => $shift['time_end'],
                'min_volunteers'          => $minVolunteers,
                'max_volunteers'          => $maxVolunteers,
                'assigned_count'          => $assignedCount,
                'is_sufficient'           => $isSufficient,
                'shortage'                => $shortage,
                'reason'                  => $reason,
                'needed_skills'           => $neededSkills,
                'skilled_assigned_count'  => $skilledAssignedCount,
                'assigned_users'          => $assignments[$sid],
            ];
        }

        $durationMs = (int)round((microtime(true) - $startTime) * 1000);

        // Tính các chỉ số đo lường
        $metrics = $this->calculateMetrics($shifts, $assignments, $userAssignments, $shiftResults, $durationMs);

        return [
            'algorithm'       => 'improved_greedy',
            'algorithm_name'  => 'Thuật toán phân công tối ưu (Ưu tiên kỹ năng cần thiết & Cân bằng tải)',
            'assignments'     => $assignments,
            'shift_results'   => $shiftResults,
            'metrics'         => $metrics,
            'duration_ms'     => $durationMs,
        ];
    }

    private function getShiftDurationMinutes(array $shift): int
    {
        [$start, $end] = ShiftTime::interval($shift);
        return (int)(($end - $start) / 60);
    }

    private function hasOverlapWithAssigned(int $uid, array $currentShift, array $assignedShiftIds, array $allShifts): bool
    {
        $shiftMap = [];
        foreach ($allShifts as $s) {
            $shiftMap[(int)$s['id']] = $s;
        }

        foreach ($assignedShiftIds as $asid) {
            if (!isset($shiftMap[$asid])) continue;
            if (ShiftTime::overlaps($currentShift, $shiftMap[$asid])) {
                return true;
            }
        }
        return false;
    }

    private function calculateMetrics(array $shifts, array $assignments, array $userAssignments, array $shiftResults, int $durationMs): array
    {
        $totalShifts = count($shifts);
        $sufficientCount = 0;
        $totalAssignedPersonShifts = 0;
        $violations = 0;

        $totalSkilledNeededShifts = 0;
        $totalSkilledAssignments = 0;

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

            $neededSkills = $s['needed_skills'] ?? $s['required_skills'] ?? $s['mandatory_skills'] ?? [];
            if (!empty($neededSkills)) {
                $totalSkilledNeededShifts++;
                $totalSkilledAssignments += ($shiftResults[$sid]['skilled_assigned_count'] ?? 0);
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
            'total_shifts'                => $totalShifts,
            'sufficient_shifts'           => $sufficientCount,
            'sufficient_rate'             => $sufficientRate,
            'total_person_shifts'         => $totalAssignedPersonShifts,
            'violations_count'            => $violations,
            'active_volunteers_used'      => count($counts),
            'max_shifts_per_user'         => $maxShifts,
            'min_shifts_per_user'         => $minShifts,
            'shift_deviation'             => $shiftDeviation,
            'total_skilled_needed_shifts' => $totalSkilledNeededShifts,
            'total_skilled_assignments'   => $totalSkilledAssignments,
            'duration_ms'                 => $durationMs,
        ];
    }
}
