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
use App\Models\PointsModel;
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
            if (in_array($asgn['attendance_status'], ['confirmed', 'completed'], true)) $confirmedCount++;
            if ($asgn['shift_date'] >= $today) $upcomingCount++;
        }

        $pointsSummary = (new PointsModel())->getSummary($userId);

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
            'pointsSummary'      => $pointsSummary,
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
