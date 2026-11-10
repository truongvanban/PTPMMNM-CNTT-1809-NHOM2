<?php

declare(strict_types=1);

namespace App\Models;

class PointsModel extends BaseModel
{
    private const DEFAULT_SETTINGS = [
        'points_per_hour' => 1,
        'difficult_shift_multiplier' => 1.5,
        'skilled_shift_multiplier' => 2,
        'on_time_bonus' => 5,
        'late_cancel_penalty' => 10,
        'no_show_penalty' => 20,
        'daily_points_cap' => 30,
        'certificate_threshold' => 500,
    ];

    public static function tierForPoints(int $points): string
    {
        return match (true) {
            $points >= 1501 => 'diamond',
            $points >= 501 => 'gold',
            $points >= 101 => 'silver',
            default => 'bronze',
        };
    }

    public static function calculateBasePoints(float $hours, float $pointsPerHour, float $multiplier): int
    {
        return (int)round($hours * $pointsPerHour * $multiplier);
    }

    public static function capPositiveAward(int $requested, int $earnedToday, int $dailyCap): int
    {
        return $requested > 0
            ? min($requested, max(0, $dailyCap - $earnedToday))
            : $requested;
    }

    public function getSettings(): array
    {
        $settings = self::DEFAULT_SETTINGS;
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM gamification_settings");
        foreach ($stmt->fetchAll() as $row) {
            if (array_key_exists($row['setting_key'], $settings)) {
                $settings[$row['setting_key']] = (float)$row['setting_value'];
            }
        }
        return $settings;
    }

    public function getSummary(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT total_points, current_tier FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch() ?: ['total_points' => 0, 'current_tier' => 'bronze'];
        $points = (int)$user['total_points'];
        $tiers = [
            'bronze' => ['label' => 'Tân binh (Đồng)', 'minimum' => 0, 'next' => 101, 'next_label' => 'Nhiệt huyết'],
            'silver' => ['label' => 'Nhiệt huyết (Bạc)', 'minimum' => 101, 'next' => 501, 'next_label' => 'Truyền cảm hứng'],
            'gold' => ['label' => 'Truyền cảm hứng (Vàng)', 'minimum' => 501, 'next' => 1501, 'next_label' => 'Đại sứ xanh'],
            'diamond' => ['label' => 'Đại sứ xanh (Kim Cương)', 'minimum' => 1501, 'next' => null, 'next_label' => null],
        ];
        $tier = $tiers[$user['current_tier']] ?? $tiers['bronze'];
        $progress = $tier['next'] === null
            ? 100
            : (int)min(100, max(0, round((($points - $tier['minimum']) / ($tier['next'] - $tier['minimum'])) * 100)));

        $badgeStmt = $this->db->prepare(
            "SELECT id, name, icon, required_points, description,
                    (required_points <= ?) AS unlocked
             FROM badges
             ORDER BY required_points ASC, id ASC"
        );
        $badgeStmt->execute([$points]);

        return [
            'points' => $points,
            'tier' => $user['current_tier'],
            'tier_label' => $tier['label'],
            'next_tier_points' => $tier['next'],
            'next_tier_label' => $tier['next_label'],
            'points_to_next_tier' => $tier['next'] === null ? 0 : max(0, $tier['next'] - $points),
            'progress' => $progress,
            'badges' => $badgeStmt->fetchAll(),
        ];
    }

    public function getRecentTransactions(int $userId, int $limit = 20): array
    {
        $limit = min(100, max(1, $limit));
        $stmt = $this->db->prepare(
            "SELECT pt.*, s.name AS shift_name, s.date AS shift_date
             FROM point_transactions pt
             LEFT JOIN shifts s ON s.id = pt.reference_id
                 AND pt.type IN ('attendance', 'on_time_bonus', 'late_cancel', 'no_show')
             WHERE pt.user_id = ?
             ORDER BY pt.created_at DESC, pt.id DESC
             LIMIT $limit"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getLeaderboard(string $period, ?int $termId = null, int $limit = 20): array
    {
        $limit = min(100, max(1, $limit));
        $where = '';
        $params = [];
        if ($period === 'weekly') {
            $where = 'WHERE pt.created_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)';
        } elseif ($period === 'monthly') {
            $where = "WHERE pt.created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
        } elseif ($period === 'semester') {
            if ($termId === null || $termId <= 0) {
                return [];
            }
            $where = 'WHERE pt.created_at >= t.starts_on AND pt.created_at < DATE_ADD(t.ends_on, INTERVAL 1 DAY) AND t.id = ?';
            $params[] = $termId;
        }
        if ($period === 'semester') {
            $stmt = $this->db->prepare(
                "SELECT u.id, u.full_name, SUM(pt.points) AS points
                 FROM point_transactions pt
                 JOIN users u ON u.id = pt.user_id
                 JOIN user_roles ur ON ur.user_id = u.id
                 JOIN roles r ON r.id = ur.role_id AND r.name = 'volunteer'
                 JOIN academic_terms t ON t.id = ?
                 WHERE pt.created_at >= t.starts_on
                   AND pt.created_at < DATE_ADD(t.ends_on, INTERVAL 1 DAY)
                 GROUP BY u.id, u.full_name
                 ORDER BY points DESC, u.full_name ASC
                 LIMIT $limit"
            );
            $stmt->execute([$termId]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT u.id, u.full_name, SUM(pt.points) AS points
                 FROM point_transactions pt
                 JOIN users u ON u.id = pt.user_id
                 JOIN user_roles ur ON ur.user_id = u.id
                 JOIN roles r ON r.id = ur.role_id AND r.name = 'volunteer'
                 $where
                 GROUP BY u.id, u.full_name
                 ORDER BY points DESC, u.full_name ASC
                 LIMIT $limit"
            );
            $stmt->execute($params);
        }
        return $stmt->fetchAll();
    }

    public function getTerms(): array
    {
        return $this->db->query(
            "SELECT id, name, starts_on, ends_on FROM academic_terms ORDER BY starts_on DESC, id DESC"
        )->fetchAll();
    }

    public function getActivityMultipliers(): array
    {
        return $this->db->query(
            "SELECT id, title, date_start, points_multiplier
             FROM activities
             ORDER BY date_start DESC, id DESC"
        )->fetchAll();
    }

    public function saveActivityMultiplier(int $activityId, float $multiplier): bool
    {
        $exists = $this->db->prepare("SELECT id FROM activities WHERE id = ?");
        $exists->execute([$activityId]);
        if (!$exists->fetchColumn()) {
            return false;
        }
        $stmt = $this->db->prepare(
            "UPDATE activities SET points_multiplier = ? WHERE id = ?"
        );
        $stmt->execute([$multiplier, $activityId]);
        return true;
    }

    public function saveSettings(array $settings): void
    {
        $allowed = array_keys(self::DEFAULT_SETTINGS);
        $stmt = $this->db->prepare(
            "INSERT INTO gamification_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        $this->db->beginTransaction();
        try {
            foreach ($settings as $key => $value) {
                if (in_array($key, $allowed, true)) {
                    $stmt->execute([$key, $value]);
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function saveTerm(?int $termId, string $name, string $startsOn, string $endsOn, int $adminId): array
    {
        if ($termId !== null) {
            $exists = $this->db->prepare("SELECT id FROM academic_terms WHERE id = ?");
            $exists->execute([$termId]);
            if (!$exists->fetchColumn()) {
                return ['success' => false, 'message' => 'Không tìm thấy kỳ học cần cập nhật.'];
            }
        }
        $overlap = $this->db->prepare(
            "SELECT id FROM academic_terms
             WHERE starts_on <= ? AND ends_on >= ? AND (? IS NULL OR id <> ?)
             LIMIT 1"
        );
        $overlap->execute([$endsOn, $startsOn, $termId, $termId]);
        if ($overlap->fetchColumn()) {
            return ['success' => false, 'message' => 'Khoảng ngày bị trùng với một kỳ học đã khai báo.'];
        }
        if ($termId !== null) {
            $stmt = $this->db->prepare(
                "UPDATE academic_terms SET name = ?, starts_on = ?, ends_on = ? WHERE id = ?"
            );
            $stmt->execute([$name, $startsOn, $endsOn, $termId]);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO academic_terms (name, starts_on, ends_on, created_by) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$name, $startsOn, $endsOn, $adminId]);
        }
        return ['success' => true, 'message' => 'Đã lưu kỳ học.'];
    }

    public function deleteTerm(int $termId): void
    {
        $this->db->prepare("DELETE FROM academic_terms WHERE id = ?")->execute([$termId]);
    }

    public function getRecentMonthlyTopThree(): array
    {
        return $this->getLeaderboard('monthly', null, 3);
    }

    public function recordInTransaction(
        int $userId,
        int $points,
        string $type,
        ?int $referenceId,
        string $description,
        ?int $createdBy = null
    ): int|false {
        $userLock = $this->db->prepare("SELECT id FROM users WHERE id = ? FOR UPDATE");
        $userLock->execute([$userId]);
        if (!$userLock->fetchColumn()) {
            throw new \RuntimeException('Không tìm thấy tình nguyện viên để cập nhật điểm.');
        }
        $existing = $this->db->prepare(
            "SELECT id FROM point_transactions
             WHERE user_id = ? AND type = ? AND reference_id <=> ?
             LIMIT 1 FOR UPDATE"
        );
        $existing->execute([$userId, $type, $referenceId]);
        if ($existing->fetchColumn()) {
            return false;
        }

        if ($points > 0) {
            $dailyStmt = $this->db->prepare(
                "SELECT COALESCE(SUM(points), 0)
                 FROM point_transactions
                 WHERE user_id = ? AND points > 0 AND created_at >= CURDATE()"
            );
            $dailyStmt->execute([$userId]);
            $earnedToday = (int)$dailyStmt->fetchColumn();
            $cap = (int)$this->getSettings()['daily_points_cap'];
            $points = self::capPositiveAward($points, $earnedToday, $cap);
        }

        $insert = $this->db->prepare(
            "INSERT INTO point_transactions (user_id, points, type, reference_id, description, created_by)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $insert->execute([$userId, $points, $type, $referenceId, $description, $createdBy]);
        $this->syncUserCache($userId);
        return $points;
    }

    public function completeAssignment(
        int $assignmentId,
        int $coordinatorId,
        float $hours,
        bool $onTime,
        bool $isAdmin = false
    ): array {
        if ($assignmentId <= 0 || $hours <= 0 || $hours > 24) {
            return ['success' => false, 'message' => 'Số giờ thực tế phải lớn hơn 0 và không vượt quá 24 giờ.'];
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                "SELECT a.id AS assignment_id, a.user_id, a.status AS assignment_status,
                        s.id AS shift_id, s.name AS shift_name, s.date AS shift_date,
                        s.time_start, s.time_end,
                        TIMESTAMPDIFF(MINUTE, NOW(), TIMESTAMP(s.date, s.time_end)) AS minutes_until_end,
                        act.title AS activity_title,
                        act.coordinator_id, act.points_multiplier,
                        (SELECT COUNT(*) FROM shift_required_skills req
                         WHERE req.shift_id = s.id AND req.is_mandatory = 1) AS mandatory_skill_count,
                        att.status AS attendance_status
                 FROM assignments a
                 JOIN shifts s ON s.id = a.shift_id
                 JOIN activities act ON act.id = s.activity_id
                 LEFT JOIN attendance att ON att.assignment_id = a.id
                 WHERE a.id = ? AND (? = 1 OR act.coordinator_id = ?)
                 FOR UPDATE"
            );
            $stmt->execute([$assignmentId, (int)$isAdmin, $coordinatorId]);
            $assignment = $stmt->fetch();
            if (!$assignment || !in_array($assignment['assignment_status'], ['assigned', 'confirmed'], true)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Không tìm thấy phân công hợp lệ thuộc hoạt động của bạn.'];
            }
            if ($assignment['attendance_status'] === 'completed') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Ca này đã được ghi nhận hoàn thành trước đó.'];
            }
            if ((int)$assignment['minutes_until_end'] > 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Chỉ có thể ghi nhận hoàn thành sau khi ca trực kết thúc.'];
            }

            $settings = $this->getSettings();
            $multiplier = (float)$assignment['points_multiplier'];
            $startHour = (int)substr($assignment['time_start'], 0, 2);
            if ($startHour < 7 || $startHour >= 22) {
                $multiplier *= (float)$settings['difficult_shift_multiplier'];
            }
            if ((int)$assignment['mandatory_skill_count'] > 0) {
                $multiplier *= (float)$settings['skilled_shift_multiplier'];
            }
            $basePoints = self::calculateBasePoints($hours, (float)$settings['points_per_hour'], $multiplier);

            $attendance = $this->db->prepare(
                "INSERT INTO attendance (assignment_id, user_id, shift_id, status, hours_worked, confirmed_at)
                 VALUES (?, ?, ?, 'completed', ?, NOW())
                 ON DUPLICATE KEY UPDATE status = 'completed', hours_worked = VALUES(hours_worked),
                     confirmed_at = COALESCE(confirmed_at, NOW()), updated_at = NOW()"
            );
            $attendance->execute([
                (int)$assignment['assignment_id'],
                (int)$assignment['user_id'],
                (int)$assignment['shift_id'],
                $hours,
            ]);
            $this->db->prepare(
                "INSERT INTO volunteer_profiles (user_id, total_hours)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE total_hours = total_hours + VALUES(total_hours), updated_at = NOW()"
            )->execute([(int)$assignment['user_id'], $hours]);
            $this->db->prepare(
                "UPDATE assignments SET status = 'confirmed', updated_at = NOW() WHERE id = ?"
            )->execute([(int)$assignment['assignment_id']]);

            $description = sprintf(
                'Hoàn thành ca %s (%s), %.2f giờ, hệ số %.2f',
                $assignment['shift_name'],
                date('d/m/Y', strtotime($assignment['shift_date'])),
                $hours,
                $multiplier
            );
            $awardedBase = $this->recordInTransaction(
                (int)$assignment['user_id'],
                $basePoints,
                'attendance',
                (int)$assignment['shift_id'],
                $description,
                $coordinatorId
            );
            $awardedBonus = 0;
            if ($onTime) {
                $awardedBonus = $this->recordInTransaction(
                    (int)$assignment['user_id'],
                    (int)$settings['on_time_bonus'],
                    'on_time_bonus',
                    (int)$assignment['shift_id'],
                    'Thưởng hoàn thành ca đúng giờ: ' . $assignment['shift_name'],
                    $coordinatorId
                );
            }
            $pointsAwarded = (int)$awardedBase + (int)$awardedBonus;
            $this->writeAudit($coordinatorId, (int)$assignment['assignment_id'], [
                'hours_worked' => $hours,
                'on_time' => $onTime,
                'points_awarded' => $pointsAwarded,
                'multiplier' => $multiplier,
            ]);
            $this->db->prepare(
                "INSERT INTO notifications (user_id, type, title, message, link)
                 VALUES (?, 'system', ?, ?, '/volunteer/points')"
            )->execute([
                (int)$assignment['user_id'],
                'Ca trực đã được ghi nhận hoàn thành',
                "Bạn đã được ghi nhận hoàn thành ca '{$assignment['shift_name']}' và nhận {$pointsAwarded} điểm cống hiến.",
            ]);
            $this->db->commit();
            return ['success' => true, 'message' => "Đã ghi nhận hoàn thành ca và cộng {$pointsAwarded} điểm."];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function markNoShow(int $assignmentId, int $coordinatorId, bool $isAdmin = false): array
    {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                "SELECT a.id, a.user_id, a.status, s.id AS shift_id, s.name AS shift_name,
                        s.date AS shift_date,
                        TIMESTAMPDIFF(MINUTE, NOW(), TIMESTAMP(s.date, s.time_end)) AS minutes_until_end,
                        EXISTS(
                            SELECT 1 FROM replacement_requests rr
                            WHERE rr.assignment_id = a.id AND rr.status = 'pending'
                        ) AS has_pending_replacement,
                        act.coordinator_id
                 FROM assignments a
                 JOIN shifts s ON s.id = a.shift_id
                 JOIN activities act ON act.id = s.activity_id
                 WHERE a.id = ? AND (? = 1 OR act.coordinator_id = ?)
                 FOR UPDATE"
            );
            $stmt->execute([$assignmentId, (int)$isAdmin, $coordinatorId]);
            $assignment = $stmt->fetch();
            if (!$assignment || !in_array($assignment['status'], ['assigned', 'confirmed'], true)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Phân công không hợp lệ hoặc đã được xử lý.'];
            }
            if ((int)$assignment['minutes_until_end'] > 0) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Chỉ có thể ghi nhận vắng mặt sau khi ca trực kết thúc.'];
            }
            if ((int)$assignment['has_pending_replacement'] === 1) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Không thể đánh dấu vắng khi yêu cầu báo vắng đang chờ xử lý.'];
            }

            $this->db->prepare(
                "UPDATE assignments SET status = 'absent', updated_at = NOW() WHERE id = ?"
            )->execute([$assignmentId]);
            $this->db->prepare(
                "INSERT INTO attendance (assignment_id, user_id, shift_id, status)
                 VALUES (?, ?, ?, 'absent')
                 ON DUPLICATE KEY UPDATE status = 'absent', updated_at = NOW()"
            )->execute([$assignmentId, (int)$assignment['user_id'], (int)$assignment['shift_id']]);
            $settings = $this->getSettings();
            $this->recordInTransaction(
                (int)$assignment['user_id'],
                -(int)$settings['no_show_penalty'],
                'no_show',
                (int)$assignment['shift_id'],
                "Phạt vắng mặt không phép: {$assignment['shift_name']} ngày " . date('d/m/Y', strtotime($assignment['shift_date'])),
                $coordinatorId
            );
            $this->writeAudit(
                $coordinatorId,
                $assignmentId,
                ['status' => 'absent', 'penalty' => (int)$settings['no_show_penalty']],
                'mark_no_show'
            );
            $this->db->prepare(
                "INSERT INTO notifications (user_id, type, title, message, link)
                 VALUES (?, 'system', ?, ?, '/volunteer/points')"
            )->execute([
                (int)$assignment['user_id'],
                'Ghi nhận vắng mặt không phép',
                "Bạn bị ghi nhận vắng mặt không phép ở ca '{$assignment['shift_name']}' ngày " .
                    date('d/m/Y', strtotime($assignment['shift_date'])) .
                    " và bị trừ {$settings['no_show_penalty']} điểm.",
            ]);
            $this->db->commit();
            return ['success' => true, 'message' => 'Đã ghi nhận vắng mặt không phép và cập nhật điểm.'];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function getCertificateData(int $userId): ?array
    {
        $summary = $this->getSummary($userId);
        $threshold = (int)$this->getSettings()['certificate_threshold'];
        if ($summary['points'] < $threshold) {
            return null;
        }
        $stmt = $this->db->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $name = $stmt->fetchColumn();
        return $name === false ? null : ['name' => $name, 'points' => $summary['points'], 'threshold' => $threshold];
    }

    private function syncUserCache(int $userId): void
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(points), 0) FROM point_transactions WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        $points = (int)$stmt->fetchColumn();
        $tier = self::tierForPoints($points);
        $this->db->prepare("UPDATE users SET total_points = ?, current_tier = ? WHERE id = ?")
            ->execute([$points, $tier, $userId]);
    }

    private function writeAudit(
        int $actorId,
        int $assignmentId,
        array $newData,
        string $action = 'record_attendance_points'
    ): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, new_data, ip_address, user_agent)
             VALUES (?, ?, 'assignment', ?, ?, ?, ?)"
        );
        $stmt->execute([
            $actorId,
            $action,
            $assignmentId,
            json_encode($newData, JSON_UNESCAPED_UNICODE),
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    }
}
