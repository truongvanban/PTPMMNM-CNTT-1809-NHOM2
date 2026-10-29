<?php
/**
 * app/Controllers/PublicController.php
 * Trang công khai và các endpoint backend chỉ dành cho quản trị viên
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ActivityModel;
use App\Models\Database;
use App\Models\ShiftModel;
use App\Services\AssignmentService;

class PublicController extends Controller
{
    protected string $layout = 'main';
    private ActivityModel $activityModel;

    public function __construct()
    {
        $this->activityModel = new ActivityModel();
    }

    public function home(): void
    {
        $activities = $this->activityModel->getOpenActivities(1, 6);
        $this->render('public.home', [
            'title'      => 'VolunteerHub - Tổ Chức Tình Nguyện',
            'activities' => $activities['data'],
            'error'      => Session::getFlash('error'),
            'success'    => Session::getFlash('success'),
        ]);
    }

    public function activities(): void
    {
        $page = max(1, (int)$this->get('page', 1));
        $result = $this->activityModel->getOpenActivities($page, 9);
        $this->render('public.activities', [
            'title'      => 'Hoạt Động Tình Nguyện | VolunteerHub',
            'activities' => $result['data'],
            'pagination' => $result,
            'error'      => Session::getFlash('error'),
        ]);
    }

    public function activityDetail(string $id): void
    {
        $actId = (int)$id;
        $activity = $this->activityModel->findWithCoordinator($actId);

        if (!$activity) {
            http_response_code(404);
            $this->render('errors.404', ['title' => 'Không tìm thấy | VolunteerHub']);
            return;
        }

        $shiftModel = new ShiftModel();
        $shifts     = $shiftModel->getByActivity($actId);
        $userId     = Session::userId();

        $this->render('public.activity_detail', [
            'title'    => $activity['title'] . ' | VolunteerHub',
            'activity' => $activity,
            'shifts'   => $shifts,
            'userId'   => $userId,
            'isLogged' => Session::isLoggedIn(),
            'error'    => Session::getFlash('error'),
            'success'  => Session::getFlash('success'),
        ]);
    }

    /**
     * Màn hình kiểm tra dữ liệu backend dành riêng cho quản trị viên
     */
    public function backendInspect(): void
    {
        $db = Database::getInstance();

        // 1. Dữ liệu bảng Users
        $users = $db->query(
            "SELECT u.id, u.full_name, u.email, u.phone, u.status, u.created_at,
                    GROUP_CONCAT(r.name SEPARATOR ', ') as role_names
             FROM users u
             LEFT JOIN user_roles ur ON u.id = ur.user_id
             LEFT JOIN roles r ON ur.role_id = r.id
             GROUP BY u.id
             ORDER BY u.id ASC"
        )->fetchAll();

        // 2. Dữ liệu bảng Shifts & Hoạt động
        $shifts = $db->query(
            "SELECT s.*, act.title as activity_title,
                    (SELECT COUNT(*) FROM registrations r WHERE r.shift_id = s.id AND r.status = 'approved') as registered_count,
                    (SELECT COUNT(*) FROM assignments asgn WHERE asgn.shift_id = s.id AND asgn.status NOT IN ('removed','replaced')) as assigned_count
             FROM shifts s
             JOIN activities act ON s.activity_id = act.id
             ORDER BY s.id ASC"
        )->fetchAll();

        // 3. Dữ liệu bảng Kỹ năng
        $skills = $db->query("SELECT * FROM skills ORDER BY id ASC")->fetchAll();

        // 4. Dữ liệu Nhật ký hệ thống (Audit Logs)
        $auditLogs = $db->query(
            "SELECT al.*, u.full_name as user_name
             FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.id
             ORDER BY al.created_at DESC LIMIT 10"
        )->fetchAll();

        // 5. Kết quả chạy thử thuật toán trực tiếp trên Bộ A & Bộ B
        $service = new AssignmentService();
        $algoTest = $service->compareAlgorithms(1);

        $this->render('public.backend_inspect', [
            'title'     => 'Backend Inspector | VolunteerHub',
            'users'     => $users,
            'shifts'    => $shifts,
            'skills'    => $skills,
            'auditLogs' => $auditLogs,
            'algoTest'  => $algoTest,
        ]);
    }

    /**
     * API JSON trả về trạng thái backend, chỉ dành cho quản trị viên
     */
    public function apiStatus(): void
    {
        $db = Database::getInstance();
        $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalShifts = (int)$db->query("SELECT COUNT(*) FROM shifts")->fetchColumn();
        $totalActivities = (int)$db->query("SELECT COUNT(*) FROM activities")->fetchColumn();
        $totalAssignments = (int)$db->query("SELECT COUNT(*) FROM assignments WHERE status NOT IN ('removed','replaced')")->fetchColumn();

        $this->json([
            'status' => 'online',
            'server' => [
                'php_version' => PHP_VERSION,
                'db_server'   => 'MariaDB 10.4 / MySQL',
                'timezone'    => date_default_timezone_get(),
                'server_time' => date('Y-m-d H:i:s'),
            ],
            'database_counts' => [
                'users'        => $totalUsers,
                'activities'   => $totalActivities,
                'shifts'       => $totalShifts,
                'assignments'  => $totalAssignments,
            ],
            'endpoints' => [
                'backend_inspector_ui' => '/backend-inspect',
                'public_activities'    => '/activities',
                'api_benchmark'        => '/api/benchmark?activity_id=1',
            ]
        ]);
    }

    /**
     * API JSON so sánh thuật toán, chỉ dành cho quản trị viên
     */
    public function apiBenchmark(): void
    {
        $activityId = (int)($_GET['activity_id'] ?? 1);
        $service = new AssignmentService();
        $comparison = $service->compareAlgorithms($activityId);
        $this->json([
            'status'      => 'success',
            'activity_id' => $activityId,
            'comparison'  => $comparison,
        ]);
    }
}
