<?php
/**
 * app/Controllers/Coordinator/VolunteerController.php
 * Danh sách tình nguyện viên, tra cứu kỹ năng và thông tin liên lạc
 */

declare(strict_types=1);

namespace App\Controllers\Coordinator;

use App\Core\Controller;
use App\Models\Database;
use App\Models\SkillModel;

class VolunteerController extends Controller
{
    protected string $layout = 'coordinator';

    public function index(): void
    {
        $db = Database::getInstance();
        $keyword = trim($this->get('keyword', ''));
        $skillId = (int)$this->get('skill_id', 0);
        $page = max(1, (int)$this->get('page', 1));
        $perPage = $this->getPageSize(15);

        $wheres = ["u.status = 'active'"];
        $whereParams = [];

        if ($keyword !== '') {
            $wheres[] = "(u.full_name LIKE ? OR u.email LIKE ? OR vp.student_id LIKE ?)";
            $whereParams[] = "%$keyword%";
            $whereParams[] = "%$keyword%";
            $whereParams[] = "%$keyword%";
        }

        $joinSkill = "";
        $joinParams = [];
        if ($skillId > 0) {
            $joinSkill = "INNER JOIN volunteer_skills vs_filter ON u.id = vs_filter.user_id AND vs_filter.skill_id = ?";
            $joinParams[] = $skillId;
        }

        $whereSql = "WHERE " . implode(" AND ", $wheres);
        $fromSql = "FROM users u
                    INNER JOIN user_roles ur ON u.id = ur.user_id
                    INNER JOIN roles r ON ur.role_id = r.id AND r.name = 'volunteer'
                    LEFT JOIN volunteer_profiles vp ON u.id = vp.user_id
                    $joinSkill
                    $whereSql";
        $countStmt = $db->prepare("SELECT COUNT(DISTINCT u.id) $fromSql");
        $countStmt->execute(array_merge($joinParams, $whereParams));
        $total = (int)$countStmt->fetchColumn();
        $lastPage = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage > 0 ? "LIMIT $perPage OFFSET $offset" : '';

        $sql = "SELECT u.id, u.full_name, u.email, u.phone, vp.student_id, vp.faculty, vp.class_name,
                       (SELECT COUNT(*) FROM assignments asgn WHERE asgn.user_id = u.id AND asgn.status IN ('assigned','confirmed')) as total_shifts,
                       GROUP_CONCAT(DISTINCT CONCAT(sk.name, ' (', vs.level, ')') SEPARATOR ', ') as skills_list
                FROM users u
                INNER JOIN user_roles ur ON u.id = ur.user_id
                INNER JOIN roles r ON ur.role_id = r.id AND r.name = 'volunteer'
                LEFT JOIN volunteer_profiles vp ON u.id = vp.user_id
                LEFT JOIN volunteer_skills vs ON u.id = vs.user_id
                LEFT JOIN skills sk ON vs.skill_id = sk.id
                $joinSkill
                $whereSql
                GROUP BY u.id
                ORDER BY u.full_name ASC $limitSql";

        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge($joinParams, $whereParams));
        $volunteers = $stmt->fetchAll();

        $skillModel = new SkillModel();
        $allSkills = $skillModel->getActive();

        $this->render('coordinator.volunteers.index', [
            'title'      => 'Danh Sách Tình Nguyện Viên | VolunteerHub',
            'volunteers' => $volunteers,
            'total'      => $total,
            'page'       => $page,
            'lastPage'   => $lastPage,
            'perPage'    => $perPage,
            'allSkills'  => $allSkills,
            'keyword'    => $keyword,
            'skillId'    => $skillId,
        ]);
    }
}
