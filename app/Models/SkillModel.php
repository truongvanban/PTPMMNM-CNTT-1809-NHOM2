<?php
/**
 * app/Models/SkillModel.php
 * Quản lý danh mục kỹ năng và kỹ năng tình nguyện viên
 */

declare(strict_types=1);

namespace App\Models;

class SkillModel extends BaseModel
{
    protected string $table = 'skills';

    public function getActive(): array
    {
        $stmt = $this->db->query("SELECT * FROM skills WHERE is_active = 1 ORDER BY category ASC, name ASC");
        return $stmt->fetchAll();
    }

    public function getAdminPage(int $page = 1, int $perPage = 15): array
    {
        $total = (int)$this->db->query("SELECT COUNT(*) FROM skills")->fetchColumn();
        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $stmt = $this->db->query(
            "SELECT * FROM skills ORDER BY category ASC, name ASC $limitSql"
        );
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
        ];
    }

    public function getByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, vs.level, vs.created_at as assigned_at
             FROM skills s
             JOIN volunteer_skills vs ON s.id = vs.skill_id
             WHERE vs.user_id = ?
             ORDER BY s.name ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function syncVolunteerSkills(int $userId, array $skillsWithLevel): void
    {
        $this->db->prepare("DELETE FROM volunteer_skills WHERE user_id = ?")->execute([$userId]);
        if (empty($skillsWithLevel)) return;

        $stmt = $this->db->prepare(
            "INSERT INTO volunteer_skills (user_id, skill_id, level) VALUES (?, ?, ?)"
        );
        foreach ($skillsWithLevel as $skillId => $level) {
            $level = in_array($level, ['beginner', 'intermediate', 'advanced'], true) ? $level : 'beginner';
            $stmt->execute([$userId, (int)$skillId, $level]);
        }
    }

    public function create(string $name, string $category, string $description): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO skills (name, category, description, is_active) VALUES (?, ?, ?, 1)"
        );
        $stmt->execute([trim($name), trim($category), trim($description)]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $name, string $category, string $description, int $isActive): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE skills SET name = ?, category = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([trim($name), trim($category), trim($description), $isActive, $id]);
    }
}
