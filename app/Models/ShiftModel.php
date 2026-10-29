<?php
/**
 * app/Models/ShiftModel.php
 */

declare(strict_types=1);

namespace App\Models;

class ShiftModel extends BaseModel
{
    protected string $table = 'shifts';

    public function getByActivity(int $activityId): array
    {
        return $this->getByActivityPage($activityId, 1, 0)['data'];
    }

    public function getByActivityPage(int $activityId, int $page = 1, int $perPage = 15): array
    {
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM shifts WHERE activity_id = ?");
        $countStmt->execute([$activityId]);
        $total = (int)$countStmt->fetchColumn();
        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $stmt = $this->db->prepare(
            "SELECT s.*,
                    (SELECT COUNT(*) FROM assignments a WHERE a.shift_id = s.id AND a.status NOT IN ('removed','replaced')) as assigned_count,
                    (SELECT COUNT(*) FROM registrations r WHERE r.shift_id = s.id AND r.status IN ('pending','approved')) as registered_count,
                    (SELECT COUNT(*) FROM registrations r WHERE r.shift_id = s.id AND r.status = 'pending') as pending_count,
                    GROUP_CONCAT(DISTINCT sk.name ORDER BY sk.name SEPARATOR ', ') as required_skills,
                    GROUP_CONCAT(DISTINCT srs.skill_id ORDER BY srs.skill_id) as required_skill_ids,
                    GROUP_CONCAT(DISTINCT srs.is_mandatory ORDER BY srs.skill_id) as skill_mandatory_flags
             FROM shifts s
             LEFT JOIN shift_required_skills srs ON s.id = srs.shift_id
             LEFT JOIN skills sk ON srs.skill_id = sk.id
             WHERE s.activity_id = ?
             GROUP BY s.id
             ORDER BY s.date ASC, s.time_start ASC $limitSql"
        );
        $stmt->execute([$activityId]);
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
        ];
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, a.title as activity_title, a.coordinator_id, a.status as activity_status,
                    a.registration_deadline,
                    (SELECT COUNT(*) FROM assignments a2 WHERE a2.shift_id = s.id AND a2.status NOT IN ('removed','replaced')) as assigned_count,
                    GROUP_CONCAT(DISTINCT srs.skill_id ORDER BY srs.skill_id) as required_skill_ids,
                    GROUP_CONCAT(DISTINCT srs.is_mandatory ORDER BY srs.skill_id) as skill_mandatory_flags
             FROM shifts s
             JOIN activities a ON s.activity_id = a.id
             LEFT JOIN shift_required_skills srs ON s.id = srs.shift_id
             WHERE s.id = ?
             GROUP BY s.id LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getRequiredSkills(int $shiftId): array
    {
        $stmt = $this->db->prepare(
            "SELECT srs.skill_id, srs.is_mandatory, sk.name
             FROM shift_required_skills srs
             JOIN skills sk ON srs.skill_id = sk.id
             WHERE srs.shift_id = ?"
        );
        $stmt->execute([$shiftId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO shifts (activity_id, name, description, date, time_start, time_end, min_volunteers, max_volunteers, location, status)
             VALUES (:activity_id, :name, :description, :date, :time_start, :time_end, :min_volunteers, :max_volunteers, :location, 'open')"
        );
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE shifts SET name=:name, description=:description, date=:date, time_start=:time_start,
             time_end=:time_end, min_volunteers=:min_volunteers, max_volunteers=:max_volunteers,
             location=:location, status=:status, updated_at=NOW() WHERE id=:id"
        );
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    public function close(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE shifts SET status = 'closed', updated_at = NOW()
             WHERE id = ? AND status IN ('open', 'full')"
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function setRequiredSkills(int $shiftId, array $skills): void
    {
        $this->db->prepare("DELETE FROM shift_required_skills WHERE shift_id = ?")->execute([$shiftId]);
        if (empty($skills)) return;
        $stmt = $this->db->prepare(
            "INSERT INTO shift_required_skills (shift_id, skill_id, is_mandatory) VALUES (?, ?, ?)"
        );
        foreach ($skills as $s) {
            $stmt->execute([$shiftId, (int)$s['skill_id'], (int)($s['is_mandatory'] ?? 1)]);
        }
    }

    /**
     * Lấy ca có thể đăng ký (đang mở, chưa đầy, còn trong hạn đăng ký)
     */
    public function getAvailableForVolunteer(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, a.title as activity_title, a.description as activity_description,
                    a.location as activity_location,
                    a.registration_deadline,
                    (SELECT COUNT(*) FROM assignments asgn WHERE asgn.shift_id = s.id AND asgn.status NOT IN ('removed','replaced')) as assigned_count,
                    (SELECT COUNT(*) FROM registrations r2 WHERE r2.shift_id = s.id AND r2.status IN ('pending','approved')) as registered_count,
                    (SELECT COUNT(*) FROM registrations r2 WHERE r2.shift_id = s.id AND r2.status = 'pending') as pending_count,
                    (SELECT id FROM registrations r3 WHERE r3.shift_id = s.id AND r3.user_id = ? LIMIT 1) as my_reg_id,
                    (SELECT status FROM registrations r4 WHERE r4.shift_id = s.id AND r4.user_id = ? LIMIT 1) as my_reg_status,
                    GROUP_CONCAT(DISTINCT sk.name ORDER BY sk.name SEPARATOR ', ') as required_skills
             FROM shifts s
             JOIN activities a ON s.activity_id = a.id
             LEFT JOIN shift_required_skills srs ON s.id = srs.shift_id
             LEFT JOIN skills sk ON srs.skill_id = sk.id
             WHERE s.status = 'open'
               AND a.status IN ('open','ongoing')
               AND a.registration_deadline >= NOW()
             GROUP BY s.id
             ORDER BY s.date ASC, s.time_start ASC"
        );
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Kiểm tra xung đột lịch: user có ca nào khác trùng giờ không?
     */
    public function hasTimeConflict(int $userId, int $shiftId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT s.id
             FROM assignments asgn
             JOIN shifts s ON asgn.shift_id = s.id
             JOIN shifts target ON target.id = ?
             WHERE asgn.user_id = ?
               AND asgn.status NOT IN ('removed', 'replaced', 'absent')
               AND asgn.shift_id != ?
               AND s.date = target.date
               AND s.time_start < target.time_end
               AND s.time_end > target.time_start
             LIMIT 1"
        );
        $stmt->execute([$shiftId, $userId, $shiftId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Lấy ca của volunteer theo tháng (cho lịch cá nhân)
     */
    public function getVolunteerSchedule(int $userId, int $year, int $month): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, a.title as activity_title, a.location as activity_location,
                    asgn.status as assignment_status, asgn.id as assignment_id,
                    att.status as attendance_status
             FROM assignments asgn
             JOIN shifts s ON asgn.shift_id = s.id
             JOIN activities a ON s.activity_id = a.id
             LEFT JOIN attendance att ON att.assignment_id = asgn.id
             WHERE asgn.user_id = ?
               AND asgn.status NOT IN ('removed','replaced')
               AND YEAR(s.date) = ?
               AND MONTH(s.date) = ?
             ORDER BY s.date ASC, s.time_start ASC"
        );
        $stmt->execute([$userId, $year, $month]);
        return $stmt->fetchAll();
    }
}
