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
