<?php
/**
 * app/Controllers/Coordinator/DashboardController.php
 * Dashboard tổng quan cho Điều phối viên
 */

declare(strict_types=1);

namespace App\Controllers\Coordinator;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ActivityModel;
use App\Models\Database;
use App\Models\ReplacementModel;

class DashboardController extends Controller
{
    protected string $layout = 'coordinator';

    public function index(): void
    {
        $coordId = (int)Session::userId();
        $db = Database::getInstance();

        $activityModel = new ActivityModel();
        $replacementModel = new ReplacementModel();

        $myActivities = $activityModel->getByCoordinator($coordId);
        $pendingReplacements = $replacementModel->getPendingForCoordinator($coordId);
        $pendingUpcomingReplacementCount = $replacementModel->countPendingUpcomingForCoordinator($coordId);

        // Đếm tổng số ca trực thuộc quyền
        $shiftCountStmt = $db->prepare(
            "SELECT COUNT(*) FROM shifts s JOIN activities a ON s.activity_id = a.id WHERE a.coordinator_id = ?"
        );
        $shiftCountStmt->execute([$coordId]);
        $totalShifts = (int)$shiftCountStmt->fetchColumn();

        // Đếm tổng phân công
        $assignStmt = $db->prepare(
            "SELECT COUNT(*) FROM assignments asgn
             JOIN shifts s ON asgn.shift_id = s.id
             JOIN activities a ON s.activity_id = a.id
             WHERE a.coordinator_id = ? AND asgn.status NOT IN ('removed','replaced')"
        );
        $assignStmt->execute([$coordId]);
        $totalAssignments = (int)$assignStmt->fetchColumn();

        // Đếm ca thiếu người
        $shortageShiftsStmt = $db->prepare(
            "SELECT s.id, s.name, s.min_volunteers, s.max_volunteers, s.date, a.title as activity_title,
                    (SELECT COUNT(*) FROM assignments asgn WHERE asgn.shift_id = s.id AND asgn.status NOT IN ('removed','replaced')) as current_count
             FROM shifts s
             JOIN activities a ON s.activity_id = a.id
             WHERE a.coordinator_id = ? AND s.status = 'open'
             HAVING current_count < s.min_volunteers"
        );
        $shortageShiftsStmt->execute([$coordId]);
        $shortageShifts = $shortageShiftsStmt->fetchAll();

        $this->render('coordinator.dashboard', [
            'title'               => 'Bảng Điều Khiển Điều Phối Viên | VolunteerHub',
            'activities'          => array_slice($myActivities, 0, 5),
            'totalActivities'     => count($myActivities),
            'totalShifts'         => $totalShifts,
            'totalAssignments'    => $totalAssignments,
            'shortageShifts'      => $shortageShifts,
            'pendingReplacements' => $pendingReplacements,
            'pendingUpcomingReplacementCount' => $pendingUpcomingReplacementCount,
            'success'             => Session::getFlash('success'),
            'error'               => Session::getFlash('error'),
        ]);
    }
}
