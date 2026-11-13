<?php
/**
 * app/Models/SkillRequestModel.php
 * Quản lý yêu cầu bổ sung kỹ năng của tình nguyện viên
 */

declare(strict_types=1);

namespace App\Models;

use Throwable;

class SkillRequestModel extends BaseModel
{
    protected string $table = 'skill_requests';

    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM skill_requests
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getAdminPage(int $page = 1, int $perPage = 15): array
    {
        $total = (int)$this->db->query("SELECT COUNT(*) FROM skill_requests")->fetchColumn();
        $pending = $this->countPending();
        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';
        $stmt = $this->db->prepare(
            "SELECT sr.*, u.full_name, u.email
             FROM skill_requests sr
             JOIN users u ON u.id = sr.user_id
             ORDER BY (sr.status = 'pending') DESC, sr.created_at DESC, sr.id DESC
             $limitSql"
        );
        $stmt->execute();
        $data = $stmt->fetchAll();
        return [
            'data' => $data,
            'total' => $total,
            'pending' => $pending,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'visible_count' => count($data),
        ];
    }

    public function countPending(): int
    {
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM skill_requests WHERE status = 'pending'"
        )->fetchColumn();
    }

    public function countPendingForUser(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM skill_requests WHERE user_id = ? AND status = 'pending'"
        );
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function countThisMonthForUser(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM skill_requests
             WHERE user_id = ? AND created_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')"
        );
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function createRequest(int $userId, string $skillName, string $proof): array
    {
        $skillName = trim($skillName);
        $proof = trim($proof);
        if ($userId <= 0 || mb_strlen($skillName) < 2 || mb_strlen($skillName) > 150) {
            return ['success' => false, 'message' => 'Tên kỹ năng phải từ 2 đến 150 ký tự.'];
        }
        if ($proof === '' || mb_strlen($proof) > 5000) {
            return ['success' => false, 'message' => 'Vui lòng cung cấp bằng chứng (tối đa 5000 ký tự).'];
        }

        try {
            $this->db->beginTransaction();
            $userStmt = $this->db->prepare("SELECT id FROM users WHERE id = ? FOR UPDATE");
            $userStmt->execute([$userId]);
            if (!$userStmt->fetchColumn()) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Không tìm thấy tài khoản tình nguyện viên.'];
            }

            $pendingStmt = $this->db->prepare(
                "SELECT id FROM skill_requests
                 WHERE user_id = ? AND status = 'pending'
                 LIMIT 1"
            );
            $pendingStmt->execute([$userId]);
            if ($pendingStmt->fetchColumn()) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Bạn đang có một yêu cầu kỹ năng chờ duyệt.'];
            }

            $monthlyStmt = $this->db->prepare(
                "SELECT COUNT(*) FROM skill_requests
                 WHERE user_id = ? AND created_at >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')"
            );
            $monthlyStmt->execute([$userId]);
            if ((int)$monthlyStmt->fetchColumn() >= 3) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Bạn đã đạt giới hạn 3 yêu cầu kỹ năng trong tháng này.'];
            }

            $alreadyHasSkill = $this->db->prepare(
                "SELECT vs.skill_id
                 FROM volunteer_skills vs
                 JOIN skills s ON s.id = vs.skill_id
                 WHERE vs.user_id = ? AND LOWER(TRIM(s.name)) = LOWER(?)
                 LIMIT 1"
            );
            $alreadyHasSkill->execute([$userId, $skillName]);
            if ($alreadyHasSkill->fetchColumn()) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Kỹ năng này đã có trong hồ sơ của bạn.'];
            }

            $stmt = $this->db->prepare(
                "INSERT INTO skill_requests (user_id, skill_name, proof, status)
                 VALUES (?, ?, ?, 'pending')"
            );
            $stmt->execute([$userId, $skillName, $proof]);
            $this->db->commit();
            return ['success' => true, 'message' => 'Đã gửi yêu cầu bổ sung kỹ năng để Admin xét duyệt.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[SkillRequestModel] Không thể tạo yêu cầu kỹ năng: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Không thể gửi yêu cầu kỹ năng. Vui lòng thử lại.'];
        }
    }

    public function approve(int $requestId): array
    {
        try {
            $this->db->beginTransaction();
            $requestStmt = $this->db->prepare(
                "SELECT * FROM skill_requests WHERE id = ? FOR UPDATE"
            );
            $requestStmt->execute([$requestId]);
            $request = $requestStmt->fetch();
            if (!$request || $request['status'] !== 'pending') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Yêu cầu không tồn tại hoặc đã được xử lý.'];
            }

            $skillName = trim((string)$request['skill_name']);
            $skillStmt = $this->db->prepare(
                "SELECT id FROM skills
                 WHERE LOWER(TRIM(name)) = LOWER(?)
                 LIMIT 1
                 FOR UPDATE"
            );
            $skillStmt->execute([$skillName]);
            $skillId = $skillStmt->fetchColumn();
            if ($skillId === false) {
                $insertSkill = $this->db->prepare(
                    "INSERT INTO skills (name, category, description, is_active)
                     VALUES (?, 'Khác', NULL, 1)
                     ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
                );
                $insertSkill->execute([$skillName]);
                $skillId = $this->db->lastInsertId();
            }

            $volunteerSkillStmt = $this->db->prepare(
                "INSERT INTO volunteer_skills (user_id, skill_id, level)
                 VALUES (?, ?, 'beginner')
                 ON DUPLICATE KEY UPDATE skill_id = VALUES(skill_id)"
            );
            $volunteerSkillStmt->execute([(int)$request['user_id'], (int)$skillId]);

            $this->db->prepare(
                "UPDATE skill_requests SET status = 'approved', admin_note = NULL, updated_at = NOW()
                 WHERE id = ? AND status = 'pending'"
            )->execute([$requestId]);
            $this->notifyVolunteer(
                (int)$request['user_id'],
                'Yêu cầu kỹ năng đã được duyệt',
                "Kỹ năng '{$skillName}' đã được duyệt và thêm vào hồ sơ của bạn."
            );
            $this->db->commit();
            return ['success' => true, 'message' => "Đã duyệt yêu cầu kỹ năng: {$skillName}."];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[SkillRequestModel] Không thể duyệt yêu cầu kỹ năng: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Không thể duyệt yêu cầu kỹ năng. Vui lòng thử lại.'];
        }
    }

    public function reject(int $requestId, string $note): array
    {
        $note = trim($note);
        if ($note === '' || mb_strlen($note) > 2000) {
            return ['success' => false, 'message' => 'Vui lòng nhập lý do từ chối (tối đa 2000 ký tự).'];
        }

        try {
            $this->db->beginTransaction();
            $requestStmt = $this->db->prepare(
                "SELECT user_id, skill_name FROM skill_requests
                 WHERE id = ? AND status = 'pending'
                 FOR UPDATE"
            );
            $requestStmt->execute([$requestId]);
            $request = $requestStmt->fetch();
            if (!$request) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Yêu cầu không tồn tại hoặc đã được xử lý.'];
            }

            $updateStmt = $this->db->prepare(
                "UPDATE skill_requests
                 SET status = 'rejected', admin_note = ?, updated_at = NOW()
                 WHERE id = ? AND status = 'pending'"
            );
            $updateStmt->execute([$note, $requestId]);
            if ($updateStmt->rowCount() !== 1) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Yêu cầu đã được xử lý bởi Admin khác.'];
            }

            $this->notifyVolunteer(
                (int)$request['user_id'],
                'Yêu cầu kỹ năng bị từ chối',
                "Yêu cầu kỹ năng '{$request['skill_name']}' bị từ chối. Lý do: {$note}"
            );
            $this->db->commit();
            return ['success' => true, 'message' => 'Đã từ chối yêu cầu kỹ năng.'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[SkillRequestModel] Không thể từ chối yêu cầu kỹ năng: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Không thể từ chối yêu cầu kỹ năng. Vui lòng thử lại.'];
        }
    }

    private function notifyVolunteer(int $userId, string $title, string $message): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notifications (user_id, type, title, message, link)
             VALUES (?, 'skill_request', ?, ?, '/volunteer/skills')"
        );
        $stmt->execute([$userId, $title, $message]);
    }
}
