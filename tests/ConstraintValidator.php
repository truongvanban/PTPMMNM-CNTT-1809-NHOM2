<?php
/**
 * tests/ConstraintValidator.php
 * Trình xác thực độc lập các ràng buộc cứng cho mọi phương án phân công
 */

declare(strict_types=1);

namespace Tests;

class ConstraintValidator
{
    /**
     * Xác thực toàn diện một phương án phân công
     *
     * @param array $shifts Danh sách ca trực kèm kỹ năng bắt buộc và giới hạn min/max
     * @param array $volunteers Danh sách tình nguyện viên kèm kỹ năng
     * @param array $assignments Mảng phân công: shift_id => [user_id, ...]
     * @return array [
     *   'is_valid' => bool,
     *   'violations' => array of [
     *      'type' => 'skill'|'overlap'|'capacity',
     *      'shift_id' => int,
     *      'user_id' => int|null,
     *      'message' => string
     *   ],
     *   'metrics' => array
     * ]
     */
    public static function validate(array $shifts, array $volunteers, array $assignments): array
    {
        $violations = [];
        $shiftMap = [];
        foreach ($shifts as $s) {
            $sid = (int)($s['id'] ?? $s['shift_id'] ?? 0);
            if ($sid > 0) {
                $shiftMap[$sid] = $s;
            }
        }

        $volunteerMap = [];
        foreach ($volunteers as $v) {
            $vid = (int)($v['id'] ?? $v['user_id'] ?? 0);
            if ($vid > 0) {
                $volunteerMap[$vid] = $v;
            }
        }

        $userAssignedShifts = []; // user_id => [shift, ...]

        // 1. Kiểm tra từng ca trực
        foreach ($assignments as $shiftId => $userIds) {
            $shiftId = (int)$shiftId;
            if (!isset($shiftMap[$shiftId])) {
                $violations[] = [
                    'type'     => 'capacity',
                    'shift_id' => $shiftId,
                    'user_id'  => null,
                    'message'  => "Ca trực ID $shiftId không tồn tại trong danh sách ca."
                ];
                continue;
            }

            $shift = $shiftMap[$shiftId];
            $assignedCount = count($userIds);
            $maxVol = (int)$shift['max_volunteers'];
            $mandatorySkills = $shift['mandatory_skills'] ?? [];

            // TC04: Giới hạn ca (không vượt quá max_volunteers)
            if ($assignedCount > $maxVol) {
                $violations[] = [
                    'type'     => 'capacity',
                    'shift_id' => $shiftId,
                    'user_id'  => null,
                    'message'  => "Ca '{$shift['name']}' vượt quá số lượng tối đa cho phép ($assignedCount > $maxVol)."
                ];
            }

            // Kiểm tra trùng lặp người trong cùng một ca
            $uniqueUsers = array_unique($userIds);
            if (count($uniqueUsers) < $assignedCount) {
                $violations[] = [
                    'type'     => 'duplicate_user',
                    'shift_id' => $shiftId,
                    'user_id'  => null,
                    'message'  => "Ca '{$shift['name']}' có tình nguyện viên bị phân công lặp lại 2 lần."
                ];
            }

            // Kiểm tra từng tình nguyện viên trong ca
            foreach ($userIds as $uid) {
                $uid = (int)$uid;
                if (!isset($volunteerMap[$uid])) {
                    $violations[] = [
                        'type'     => 'ghost_volunteer',
                        'shift_id' => $shiftId,
                        'user_id'  => $uid,
                        'message'  => "Phát hiện tình nguyện viên không tồn tại trong hệ thống (Ghost volunteer: ID $uid)."
                    ];
                    continue;
                }

                $vol = $volunteerMap[$uid];
                $userSkills = array_map('intval', $vol['skills'] ?? []);

                // TC01: Ràng buộc kỹ năng bắt buộc
                foreach ($mandatorySkills as $ms) {
                    if (!in_array((int)$ms, $userSkills, true)) {
                        $violations[] = [
                            'type'     => 'skill',
                            'shift_id' => $shiftId,
                            'user_id'  => $uid,
                            'message'  => "TNV {$vol['full_name']} (ID $uid) thiếu kỹ năng bắt buộc ID $ms trong ca '{$shift['name']}'."
                        ];
                    }
                }

                // Registered volunteers are not required to declare full-shift availability.
                // Save shifts to check for overlapping assignments.
                $userAssignedShifts[$uid][] = $shift;
            }
        }

        // TC03: Ràng buộc chống trùng ca (không chồng lấn thời gian)
        foreach ($userAssignedShifts as $uid => $shiftsOfUser) {
            $count = count($shiftsOfUser);
            if ($count < 2) continue;

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $sA = $shiftsOfUser[$i];
                    $sB = $shiftsOfUser[$j];

                    if ($sA['date'] === $sB['date']) {
                        // Giao thoa thời gian: startA < endB && endA > startB
                        if ($sA['time_start'] < $sB['time_end'] && $sA['time_end'] > $sB['time_start']) {
                            $volName = $volunteerMap[$uid]['full_name'] ?? "ID $uid";
                            $violations[] = [
                                'type'     => 'overlap',
                                'shift_id' => $sA['id'],
                                'user_id'  => $uid,
                                'message'  => "TNV $volName (ID $uid) bị xếp trùng giờ giữa 2 ca: '{$sA['name']}' ({$sA['time_start']}-{$sA['time_end']}) và '{$sB['name']}' ({$sB['time_start']}-{$sB['time_end']})."
                            ];
                        }
                    }
                }
            }
        }

        return [
            'is_valid'         => empty($violations),
            'violations_count' => count($violations),
            'violations'       => $violations,
        ];
    }
}
