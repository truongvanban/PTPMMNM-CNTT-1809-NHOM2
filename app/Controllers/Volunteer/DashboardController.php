<?php
/**
 * app/Controllers/Volunteer/DashboardController.php
 * Dashboard và lịch sử của Tình nguyện viên
 */

declare(strict_types=1);

namespace App\Controllers\Volunteer;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ActivityModel;
use App\Models\AssignmentModel;
use App\Models\NotificationModel;
use App\Models\RegistrationModel;
use App\Models\ShiftModel;
use App\Models\SkillModel;

class DashboardController extends Controller
{
    protected string $layout = 'volunteer';

    public function index(): void
    {
        $userId = (int)Session::userId();

        $assignmentModel = new AssignmentModel();
        $registrationModel = new RegistrationModel();
        $notificationModel = new NotificationModel();
        $skillModel = new SkillModel();
        $activityModel = new ActivityModel();

        $myAssignments = $assignmentModel->getByUser($userId);
        $myRegistrations = $registrationModel->getByUser($userId);
        $mySkills = $skillModel->getByUserId($userId);
        $unreadCount = $notificationModel->getUnreadCount($userId);
        $featuredActivities = $activityModel->getAllOpenActivities(1, 3);

        // Thống kê nhanh
        $confirmedCount = 0;
        $upcomingCount = 0;
        $today = date('Y-m-d');

        foreach ($myAssignments as $asgn) {
            if ($asgn['attendance_status'] === 'confirmed') $confirmedCount++;
            if ($asgn['shift_date'] >= $today) $upcomingCount++;
        }

        // Tính điểm cống hiến & Huy hiệu (Gamification)
        $impactPoints = ($confirmedCount * 100) + (count($mySkills) * 50) + (count($myRegistrations) * 20);
        $level = 1;
        $levelName = 'Tân Binh Nhiệt Huyết';
        if ($impactPoints >= 500) {
            $level = 3;
            $levelName = 'Thủ Lĩnh Cộng Đồng';
        } elseif ($impactPoints >= 200) {
            $level = 2;
            $levelName = 'Tình Nguyện Viên Năng Động';
        }

        $badges = [
            [
                'id' => 'starter',
                'name' => 'Nhập Cuộc',
                'icon' => 'bi-flag-fill',
                'color' => 'success',
                'unlocked' => count($mySkills) > 0 || count($myRegistrations) > 0,
                'desc' => 'Đã cập nhật kỹ năng hoặc đăng ký ca đầu tiên'
            ],
            [
                'id' => 'committed',
                'name' => 'Nhiệt Huyết',
                'icon' => 'bi-fire',
                'color' => 'danger',
                'unlocked' => count($myRegistrations) >= 2,
                'desc' => 'Đã đăng ký tham gia từ 2 ca trực trở lên'
            ],
            [
                'id' => 'achiever',
                'name' => 'Cống Hiến Vàng',
                'icon' => 'bi-trophy-fill',
                'color' => 'warning',
                'unlocked' => $confirmedCount >= 1,
                'desc' => 'Đã hoàn thành và xác nhận tham gia ca trực'
            ],
            [
                'id' => 'specialist',
                'name' => 'Chuyên Gia Kỹ Năng',
                'icon' => 'bi-patch-check-fill',
                'color' => 'primary',
                'unlocked' => count($mySkills) >= 2,
                'desc' => 'Sở hữu từ 2 kỹ năng chuyên môn trở lên'
            ],
        ];

        $this->render('volunteer.dashboard', [
            'title'              => 'Bảng Điều Khiển Tình Nguyện Viên | VolunteerHub',
            'assignments'        => array_slice($myAssignments, 0, 5),
            'registrations'      => array_slice($myRegistrations, 0, 5),
            'skills'             => $mySkills,
            'unreadCount'        => $unreadCount,
            'totalRegistered'    => count($myRegistrations),
            'totalAssigned'      => count($myAssignments),
            'confirmedCount'     => $confirmedCount,
            'upcomingCount'      => $upcomingCount,
            'featuredActivities' => $featuredActivities['data'] ?? [],
            'impactPoints'       => $impactPoints,
            'level'              => $level,
            'levelName'          => $levelName,
            'badges'             => $badges,
            'success'            => Session::getFlash('success'),
            'error'              => Session::getFlash('error'),
        ]);
    }

    public function history(): void
    {
        $userId = (int)Session::userId();
        $assignmentModel = new AssignmentModel();
        $page = max(1, (int)$this->get('page', 1));
        $result = $assignmentModel->getByUserPage($userId, $page, $this->getPageSize(15));

        $this->render('volunteer.history', [
            'title'       => 'Lịch Sử Hoạt Động | VolunteerHub',
            'assignments' => $result['data'],
            'pagination'  => $result,
        ]);
    }
}
