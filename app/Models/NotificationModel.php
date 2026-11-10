<?php
/**
 * app/Models/NotificationModel.php
 * Quản lý thông báo người dùng
 */

declare(strict_types=1);

namespace App\Models;

class NotificationModel extends BaseModel
{
    protected string $table = 'notifications';

    public function getByUser(int $userId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getByUserPage(int $userId, int $page = 1, int $perPage = 15): array
    {
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
        $countStmt->execute([$userId]);
        $total = (int)$countStmt->fetchColumn();
        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications WHERE user_id = ?
             ORDER BY created_at DESC $limitSql"
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

    public function getByIdForUser(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications WHERE id = ? AND user_id = ? LIMIT 1"
        );
        $stmt->execute([$id, $userId]);
        $notification = $stmt->fetch();
        return $notification ?: null;
    }

    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0"
        );
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function markAsRead(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute([$id, $userId]);
    }

    public function markAllAsRead(int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0"
        );
        return $stmt->execute([$userId]);
    }

    public function send(int $userId, string $type, string $title, string $message, string $link = ''): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $type, $title, $message, $link]);
        return (int)$this->db->lastInsertId();
    }
}
