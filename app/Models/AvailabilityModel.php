<?php
/**
 * app/Models/AvailabilityModel.php
 * Quản lý lịch rảnh của tình nguyện viên
 */

declare(strict_types=1);

namespace App\Models;

class AvailabilityModel extends BaseModel
{
    protected string $table = 'availability';

    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM availability WHERE user_id = ? ORDER BY date ASC, time_start ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getByUserPage(int $userId, int $page = 1, int $perPage = 15): array
    {
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM availability WHERE user_id = ?");
        $countStmt->execute([$userId]);
        $total = (int)$countStmt->fetchColumn();
        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $stmt = $this->db->prepare(
            "SELECT * FROM availability WHERE user_id = ?
             ORDER BY date ASC, time_start ASC $limitSql"
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

    public function add(int $userId, string $date, string $timeStart, string $timeEnd, string $note = ''): array
    {
        if ($timeEnd <= $timeStart) {
            return ['success' => false, 'message' => 'Giờ kết thúc phải lớn hơn giờ bắt đầu.'];
        }

        // Kiểm tra trùng lặp khoảng thời gian rảnh của chính user đó
        $stmt = $this->db->prepare(
            "SELECT id FROM availability
             WHERE user_id = ? AND date = ?
               AND time_start < ? AND time_end > ?
             LIMIT 1"
        );
        $stmt->execute([$userId, $date, $timeEnd, $timeStart]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Khoảng thời gian này bị trùng với một lịch rảnh đã khai báo trước đó.'];
        }

        $insert = $this->db->prepare(
            "INSERT INTO availability (user_id, date, time_start, time_end, note) VALUES (?, ?, ?, ?, ?)"
        );
        $insert->execute([$userId, $date, $timeStart, $timeEnd, $note]);

        return ['success' => true, 'message' => 'Đã thêm lịch rảnh thành công!'];
    }

    public function deleteForUser(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM availability WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}
