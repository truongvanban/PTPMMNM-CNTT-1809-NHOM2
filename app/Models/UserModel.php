<?php
/**
 * app/Models/UserModel.php
 * Xử lý dữ liệu người dùng
 */

declare(strict_types=1);

namespace App\Models;

class UserModel extends BaseModel
{
    protected string $table = 'users';

    /**
     * Tìm user theo email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, GROUP_CONCAT(r.name ORDER BY r.name) as role_names,
                    GROUP_CONCAT(r.id ORDER BY r.name) as role_ids
             FROM users u
             LEFT JOIN user_roles ur ON u.id = ur.user_id
             LEFT JOIN roles r ON ur.role_id = r.id
             WHERE u.email = ?
             GROUP BY u.id
             LIMIT 1"
        );
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Tìm user theo ID kèm vai trò
     */
    public function findWithRoles(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, GROUP_CONCAT(r.name ORDER BY r.name) as role_names,
                    GROUP_CONCAT(r.id ORDER BY r.name) as role_ids
             FROM users u
             LEFT JOIN user_roles ur ON u.id = ur.user_id
             LEFT JOIN roles r ON ur.role_id = r.id
             WHERE u.id = ?
             GROUP BY u.id
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findSessionIdentity(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.email, u.full_name, u.status,
                    GROUP_CONCAT(r.name ORDER BY r.name) as role_names
             FROM users u
             LEFT JOIN user_roles ur ON u.id = ur.user_id
             LEFT JOIN roles r ON ur.role_id = r.id
             WHERE u.id = ?
             GROUP BY u.id
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Tạo user mới
     */
    public function create(string $email, string $password, string $fullName, string $phone = ''): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare(
            "INSERT INTO users (email, password_hash, full_name, phone, status, email_verified)
             VALUES (?, ?, ?, ?, 'active', 0)"
        );
        $stmt->execute([$email, $hash, $fullName, $phone]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Cập nhật thông tin user
     */
    public function updateProfile(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET full_name = ?, phone = ?, updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$data['full_name'], $data['phone'] ?? '', $id]);
    }

    public function updateAccountDetails(int $id, string $email, string $fullName, string $phone): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET email = ?, full_name = ?, phone = ?, updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$email, $fullName, $phone, $id]);
    }

    /**
     * Đổi mật khẩu
     */
    public function changePassword(int $id, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$hash, $id]);
    }

    /**
     * Cập nhật lần đăng nhập cuối
     */
    public function updateLastLogin(int $id): void
    {
        $this->db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?")
                 ->execute([$id]);
    }

    /**
     * Gán vai trò cho user
     */
    public function assignRole(int $userId, int $roleId): void
    {
        $this->db->prepare(
            "INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)"
        )->execute([$userId, $roleId]);
    }

    /**
     * Gỡ vai trò
     */
    public function removeRole(int $userId, int $roleId): void
    {
        $this->db->prepare(
            "DELETE FROM user_roles WHERE user_id = ? AND role_id = ?"
        )->execute([$userId, $roleId]);
    }

    /**
     * Cập nhật trạng thái tài khoản
     */
    public function updateStatus(int $id, string $status): bool
    {
        $allowed = ['active', 'inactive', 'locked'];
        if (!in_array($status, $allowed, true)) return false;
        $stmt = $this->db->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function changeRole(int $userId, int $roleId, string $action): bool
    {
        if (!in_array($action, ['add', 'remove'], true)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $userStmt = $this->db->prepare("SELECT id FROM users WHERE id = ? FOR UPDATE");
            $userStmt->execute([$userId]);
            if (!$userStmt->fetchColumn()) {
                $this->db->rollBack();
                return false;
            }

            $roleStmt = $this->db->prepare("SELECT id, name FROM roles WHERE id = ? LIMIT 1");
            $roleStmt->execute([$roleId]);
            $role = $roleStmt->fetch();
            if (!$role) {
                $this->db->rollBack();
                return false;
            }

            $currentRoleStmt = $this->db->prepare(
                "SELECT role_id FROM user_roles WHERE user_id = ? AND role_id = ? FOR UPDATE"
            );
            $currentRoleStmt->execute([$userId, $roleId]);
            $hasRole = (bool)$currentRoleStmt->fetchColumn();

            if ($action === 'add') {
                if ($hasRole) {
                    $this->db->rollBack();
                    return false;
                }
                $this->db->prepare(
                    "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)"
                )->execute([$userId, $roleId]);
            } else {
                if (!$hasRole) {
                    $this->db->rollBack();
                    return false;
                }

                $countStmt = $this->db->prepare(
                    "SELECT COUNT(*) FROM user_roles WHERE user_id = ? FOR UPDATE"
                );
                $countStmt->execute([$userId]);
                if ((int)$countStmt->fetchColumn() <= 1) {
                    throw new \DomainException('Mỗi tài khoản phải có ít nhất một vai trò.');
                }

                if ($role['name'] === 'admin') {
                    $adminCountStmt = $this->db->prepare(
                        "SELECT COUNT(*) FROM user_roles ur
                         INNER JOIN roles r ON r.id = ur.role_id
                         WHERE r.name = 'admin' FOR UPDATE"
                    );
                    $adminCountStmt->execute();
                    if ((int)$adminCountStmt->fetchColumn() <= 1) {
                        throw new \DomainException('Không thể gỡ vai trò quản trị viên cuối cùng.');
                    }
                }

                $this->db->prepare(
                    "DELETE FROM user_roles WHERE user_id = ? AND role_id = ?"
                )->execute([$userId, $roleId]);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Danh sách user với phân trang và tìm kiếm
     */
    public function search(
        string $keyword = '',
        string $role = '',
        string $status = '',
        int $page = 1,
        int $perPage = 20,
        string $sortBy = 'created_at',
        string $sortDirection = 'DESC'
    ): array {
        $params = [];
        $wheres = [];

        $sortColumns = [
            'name'         => 'u.full_name',
            'email'        => 'u.email',
            'status'       => 'u.status',
            'created_at'   => 'u.created_at',
            'last_login_at' => 'u.last_login_at',
        ];
        $orderBy = $sortColumns[$sortBy] ?? $sortColumns['created_at'];
        $direction = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        if ($keyword !== '') {
            $wheres[] = '(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }
        if ($status !== '') {
            $wheres[] = 'u.status = ?';
            $params[] = $status;
        }

        $whereSQL = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';

        // Lọc theo vai trò
        $joinSQL = '';
        if ($role !== '') {
            $joinSQL = "INNER JOIN user_roles ur2 ON u.id = ur2.user_id
                        INNER JOIN roles r2 ON ur2.role_id = r2.id AND r2.name = ?";
            array_unshift($params, $role); // prepend
        }

        $countSQL = "SELECT COUNT(DISTINCT u.id) FROM users u $joinSQL $whereSQL";
        $countStmt = $this->db->prepare($countSQL);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $sql = "SELECT u.id, u.email, u.full_name, u.phone, u.status, u.created_at, u.last_login_at,
                       GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ',') as role_names
                FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                $joinSQL
                $whereSQL
                GROUP BY u.id
                ORDER BY $orderBy $direction, u.id DESC
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

    public function deleteUsers(array $userIds, int $adminId): int
    {
        $userIds = array_values(array_unique($userIds));
        if (!$userIds) return 0;
        if (in_array($adminId, $userIds, true)) {
            throw new \DomainException('Bạn không thể xóa tài khoản của chính mình.');
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $this->db->beginTransaction();

        try {
            $adminRoleId = $this->db->query(
                "SELECT id FROM roles WHERE name = 'admin' LIMIT 1 FOR UPDATE"
            )->fetchColumn();

            $stmt = $this->db->prepare(
                "SELECT u.id, u.email, u.full_name,
                        EXISTS (
                            SELECT 1 FROM user_roles ur
                            INNER JOIN roles r ON r.id = ur.role_id
                            WHERE ur.user_id = u.id AND r.name = 'admin'
                        ) AS is_admin
                 FROM users u
                 WHERE u.id IN ($placeholders)
                 FOR UPDATE"
            );
            $stmt->execute($userIds);
            $users = $stmt->fetchAll();

            if (!$users) {
                $this->db->commit();
                return 0;
            }

            $selectedAdminCount = count(array_filter(
                $users,
                static fn(array $user): bool => (bool)$user['is_admin']
            ));

            if ($adminRoleId !== false && $selectedAdminCount > 0) {
                $adminCountStmt = $this->db->prepare(
                    "SELECT user_id FROM user_roles WHERE role_id = ? FOR UPDATE"
                );
                $adminCountStmt->execute([(int)$adminRoleId]);
                $adminCount = count($adminCountStmt->fetchAll());

                if ($adminCount - $selectedAdminCount < 1) {
                    throw new \DomainException('Không thể xóa tài khoản quản trị viên cuối cùng.');
                }
            }

            $audit = $this->db->prepare(
                "INSERT INTO audit_logs
                    (user_id, action, entity_type, entity_id, old_data, ip_address, user_agent)
                 VALUES (?, 'delete_user', 'user', ?, ?, ?, ?)"
            );
            foreach ($users as $user) {
                $audit->execute([
                    $adminId,
                    (int)$user['id'],
                    json_encode(
                        ['email' => $user['email'], 'full_name' => $user['full_name']],
                        JSON_UNESCAPED_UNICODE
                    ),
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Internal', 0, 500),
                ]);
            }

            $deleteStmt = $this->db->prepare(
                "DELETE FROM users WHERE id IN ($placeholders)"
            );
            $deleteStmt->execute(array_column($users, 'id'));
            $deletedCount = $deleteStmt->rowCount();

            $this->db->commit();
            return $deletedCount;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function deleteAllUsers(int $adminId): int
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id <> ?");
        $stmt->execute([$adminId]);

        return $this->deleteUsers(array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN)), $adminId);
    }

    /**
     * Danh sách tình nguyện viên (dùng cho phân công)
     */
    public function getVolunteers(): array
    {
        return $this->db->query(
            "SELECT u.id, u.full_name, u.email, u.phone, u.status,
                    vp.student_id, vp.faculty, vp.class_name,
                    GROUP_CONCAT(vs.skill_id ORDER BY vs.skill_id) as skill_ids
             FROM users u
             INNER JOIN user_roles ur ON u.id = ur.user_id
             INNER JOIN roles r ON ur.role_id = r.id AND r.name = 'volunteer'
             LEFT JOIN volunteer_profiles vp ON u.id = vp.user_id
             LEFT JOIN volunteer_skills vs ON u.id = vs.user_id
             WHERE u.status = 'active'
             GROUP BY u.id
             ORDER BY u.full_name"
        )->fetchAll();
    }
}
