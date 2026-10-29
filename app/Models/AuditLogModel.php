<?php
/**
 * app/Models/AuditLogModel.php
 * Ghi nhận và truy vấn nhật ký thao tác quan trọng
 */

declare(strict_types=1);

namespace App\Models;

class AuditLogModel extends BaseModel
{
    protected string $table = 'audit_logs';

    public function log(
        ?int $userId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        array $oldData = [],
        array $newData = []
    ): void {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_data, new_data, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                !empty($oldData) ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
                !empty($newData) ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Internal', 0, 500),
            ]);
        } catch (\Exception $e) {
            error_log('[AuditLog Exception] ' . $e->getMessage());
        }
    }

    public function search(string $action = '', string $entity = '', int $page = 1, int $perPage = 25): array
    {
        $wheres = [];
        $params = [];
        if ($action !== '') {
            $wheres[] = "al.action LIKE ?";
            $params[] = "%$action%";
        }
        if ($entity !== '') {
            $wheres[] = "al.entity_type = ?";
            $params[] = $entity;
        }

        $whereSql = $wheres ? "WHERE " . implode(" AND ", $wheres) : "";
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM audit_logs al $whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $sql = "SELECT al.*, u.full_name as user_name, u.email as user_email
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                $whereSql
                ORDER BY al.created_at DESC
                $limitSql";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'data'         => $stmt->fetchAll(),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => $lastPage,
        ];
    }
}
