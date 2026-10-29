<?php
/**
 * app/Models/ActivityModel.php
 */

declare(strict_types=1);

namespace App\Models;

class ActivityModel extends BaseModel
{
    protected string $table = 'activities';

    public function findWithCoordinator(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name as coordinator_name, u.email as coordinator_email
             FROM activities a
             JOIN users u ON a.coordinator_id = u.id
             WHERE a.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getOpenActivities(int $page = 1, int $perPage = 12): array
    {
        $total = $this->count("status IN ('open','ongoing') AND registration_deadline >= NOW()");
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name as coordinator_name,
                    (SELECT COUNT(*) FROM shifts s WHERE s.activity_id = a.id AND s.status != 'cancelled') as shift_count
             FROM activities a
             JOIN users u ON a.coordinator_id = u.id
             WHERE a.status IN ('open','ongoing') AND a.registration_deadline >= NOW()
             ORDER BY a.date_start ASC
             LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute();
        return [
            'data'         => $stmt->fetchAll(),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / $perPage),
        ];
    }

    public function getByCoordinator(int $coordinatorId, string $status = ''): array
    {
        $where = 'coordinator_id = ?';
        $params = [$coordinatorId];
        if ($status) { $where .= ' AND status = ?'; $params[] = $status; }
        $stmt = $this->db->prepare(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM shifts s WHERE s.activity_id = a.id) as shift_count,
                    (SELECT COUNT(*) FROM shifts s2 JOIN registrations reg ON s2.id = reg.shift_id WHERE s2.activity_id = a.id AND reg.status = 'approved') as reg_count
             FROM activities a WHERE $where ORDER BY a.date_start DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getByCoordinatorPage(
        int $coordinatorId,
        string $status = '',
        string $keyword = '',
        int $page = 1,
        int $perPage = 15
    ): array {
        $where = ['coordinator_id = ?'];
        $params = [$coordinatorId];
        if ($status !== '') {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        if ($keyword !== '') {
            $where[] = '(title LIKE ? OR location LIKE ? OR description LIKE ?)';
            array_push($params, "%$keyword%", "%$keyword%", "%$keyword%");
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM activities $whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $stmt = $this->db->prepare(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM shifts s WHERE s.activity_id = a.id) as shift_count,
                    (SELECT COUNT(*) FROM shifts s2 JOIN registrations reg ON s2.id = reg.shift_id
                     WHERE s2.activity_id = a.id AND reg.status = 'approved') as reg_count
             FROM activities a $whereSql ORDER BY a.date_start DESC $limitSql"
        );
        $stmt->execute($params);
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
        ];
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO activities (coordinator_id, title, description, location, date_start, date_end, registration_deadline, status, max_volunteers)
             VALUES (:coordinator_id, :title, :description, :location, :date_start, :date_end, :registration_deadline, :status, :max_volunteers)"
        );
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE activities SET title=:title, description=:description, location=:location,
             date_start=:date_start, date_end=:date_end, registration_deadline=:registration_deadline,
             status=:status, max_volunteers=:max_volunteers, updated_at=NOW()
             WHERE id=:id"
        );
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE activities SET status=?, updated_at=NOW() WHERE id=?");
        return $stmt->execute([$status, $id]);
    }

    public function getAllForAdmin(string $search = '', string $status = '', int $page = 1, int $perPage = 20): array
    {
        $params = [];
        $wheres = [];
        if ($search) { $wheres[] = '(a.title LIKE ? OR a.location LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($status) { $wheres[] = 'a.status = ?'; $params[] = $status; }
        $where = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM activities a $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name as coordinator_name,
                    (SELECT COUNT(*) FROM shifts s WHERE s.activity_id = a.id) as shift_count
             FROM activities a JOIN users u ON a.coordinator_id = u.id
             $where ORDER BY a.created_at DESC $limitSql"
        );
        $stmt->execute($params);
        return ['data' => $stmt->fetchAll(), 'total' => $total, 'current_page' => $page,
                'last_page' => $lastPage, 'per_page' => $perPage];
    }

    /**
     * Lấy tất cả hoạt động đang mở (không lọc theo deadline) cho TNV duyệt
     */
    public function getAllOpenActivities(int $page = 1, int $perPage = 9): array
    {
        $total = $this->count("status IN ('open','ongoing')");
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name as coordinator_name,
                    (SELECT COUNT(*) FROM shifts s WHERE s.activity_id = a.id AND s.status != 'cancelled') as shift_count,
                    (SELECT COUNT(DISTINCT r.id) FROM registrations r
                     JOIN shifts s2 ON r.shift_id = s2.id
                     WHERE s2.activity_id = a.id AND r.status IN ('pending','approved')) as registered_count
             FROM activities a
             JOIN users u ON a.coordinator_id = u.id
             WHERE a.status IN ('open','ongoing')
             ORDER BY a.date_start DESC
             LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute();
        return [
            'data'         => $stmt->fetchAll(),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
        ];
    }

    /**
     * Lấy tất cả ca trực của một hoạt động kèm trạng thái đăng ký của TNV
     */
    public function getActivityShiftsForVolunteer(int $activityId, int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, act.title as activity_title,
                    (SELECT GROUP_CONCAT(sk.name SEPARATOR ', ')
                     FROM shift_required_skills srs
                     JOIN skills sk ON srs.skill_id = sk.id
                     WHERE srs.shift_id = s.id) as required_skills,
                    (SELECT COUNT(*) FROM registrations r2
                     WHERE r2.shift_id = s.id AND r2.status IN ('pending','approved')) as registered_count,
                    (SELECT COUNT(*) FROM assignments asgn
                     WHERE asgn.shift_id = s.id AND asgn.status NOT IN ('removed','replaced')) as assigned_count,
                    reg.id as my_reg_id,
                    reg.status as my_reg_status
             FROM shifts s
             JOIN activities act ON s.activity_id = act.id
             LEFT JOIN registrations reg ON reg.shift_id = s.id AND reg.user_id = ?
             WHERE s.activity_id = ? AND s.status != 'cancelled'
             ORDER BY s.date ASC, s.time_start ASC"
        );
        $stmt->execute([$userId, $activityId]);
        return $stmt->fetchAll();
    }
}
