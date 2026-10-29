<?php
/**
 * app/Controllers/Coordinator/AssignmentController.php
 * Quản lý phân công tự động (thuật toán) và điều chỉnh phân công thủ công
 */

declare(strict_types=1);

namespace App\Controllers\Coordinator;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ActivityModel;
use App\Models\AssignmentModel;
use App\Models\Database;
use App\Models\ShiftModel;
use App\Services\AssignmentService;

class AssignmentController extends Controller
{
    protected string $layout = 'coordinator';
    private AssignmentService $assignmentService;
    private AssignmentModel $assignmentModel;
    private ActivityModel $activityModel;
    private ShiftModel $shiftModel;

    public function __construct()
    {
        $this->assignmentService = new AssignmentService();
        $this->assignmentModel = new AssignmentModel();
        $this->activityModel = new ActivityModel();
        $this->shiftModel = new ShiftModel();
    }

    public function runForm(): void
    {
        $coordId = (int)Session::userId();
        $activities = $this->activityModel->getByCoordinator($coordId);

        $selectedActId = (int)$this->get('activity_id', 0);
        $recentRuns = [];
        if ($selectedActId > 0) {
            $recentRuns = $this->assignmentModel->getRunsByActivity($selectedActId);
        }

        $this->render('coordinator.assignment.run', [
            'title'         => 'Chạy Phân Công Tự Động | VolunteerHub',
            'activities'    => $activities,
            'selectedActId' => $selectedActId,
            'recentRuns'    => $recentRuns,
            'csrfToken'     => Session::generateCsrf(),
            'success'       => Session::getFlash('success'),
            'error'         => Session::getFlash('error'),
        ]);
    }

    public function run(): void
    {
        $this->verifyCsrf();
        $coordId = (int)Session::userId();
        $activityId = (int)$this->post('activity_id', 0);
        $algorithm = $this->post('algorithm', 'improved_greedy');

        if (!in_array($algorithm, ['baseline_greedy', 'improved_greedy'], true)) {
            $algorithm = 'improved_greedy';
        }

        $activity = $this->activityModel->findById($activityId);
        if (!$activity || ((int)$activity['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/coordinator/assignment/run', ['error' => 'Hoạt động không tồn tại hoặc bạn không có quyền.']);
            return;
        }

        $runId = $this->assignmentService->runAlgorithm($activityId, $algorithm, $coordId);

        $this->redirect("/coordinator/assignment/preview/{$runId}", [
            'success' => 'Đã chạy thuật toán thành công! Hãy xem trước phương án và kiểm tra các ca thiếu người.'
        ]);
    }

    public function preview(string $runId): void
    {
        $rId = (int)$runId;
        $runDetails = $this->assignmentModel->getRunDetails($rId);

        if (!$runDetails) {
            $this->redirect('/coordinator/assignment/run', ['error' => 'Không tìm thấy phiên phân công.']);
            return;
        }

        $activity = $this->activityModel->findById((int)$runDetails['activity_id']);
        if (!$activity || ((int)$activity['coordinator_id'] !== (int)Session::userId() && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }

        $metrics = json_decode($runDetails['result_summary'] ?? '{}', true);

        // Lấy thông tin user cho từng ca để hiển thị chi tiết tên TNV
        $db = Database::getInstance();
        $userNames = [];
        foreach ($runDetails['items'] as &$item) {
            $uids = json_decode($item['assigned_users'] ?? '[]', true) ?: [];
            $item['users_list'] = [];
            if (!empty($uids)) {
                $uids = array_values(array_filter(array_map('intval', $uids), static fn(int $id): bool => $id > 0));
                if (!$uids) {
                    continue;
                }
                $placeholders = implode(',', array_fill(0, count($uids), '?'));
                $uStmt = $db->prepare("SELECT id, full_name, email, phone FROM users WHERE id IN ($placeholders)");
                $uStmt->execute($uids);
                $item['users_list'] = $uStmt->fetchAll();
            }
        }
        unset($item);

        $this->render('coordinator.assignment.preview', [
            'title'      => 'Xem Trước Phương Án Phân Công | VolunteerHub',
            'run'        => $runDetails,
            'metrics'    => $metrics,
            'csrfToken'  => Session::generateCsrf(),
            'success'    => Session::getFlash('success'),
            'error'      => Session::getFlash('error'),
        ]);
    }

    public function confirm(string $runId): void
    {
        $this->verifyCsrf();
        $rId = (int)$runId;
        $coordId = (int)Session::userId();

        $runDetails = $this->assignmentModel->getRunDetails($rId);
        $activity = $runDetails
            ? $this->activityModel->findById((int)$runDetails['activity_id'])
            : null;
        if (!$activity) {
            $this->redirect('/coordinator/assignment/run', ['error' => 'Không tìm thấy phiên phân công.']);
            return;
        }
        if ((int)$activity['coordinator_id'] !== $coordId && !Session::hasRole('admin')) {
            $this->redirect('/403');
            return;
        }

        $res = $this->assignmentService->confirmRun($rId, $coordId);
        if ($res['success']) {
            $this->redirect("/coordinator/assignment/preview/{$rId}", ['success' => $res['message']]);
        } else {
            $this->redirect("/coordinator/assignment/preview/{$rId}", ['error' => $res['message']]);
        }
    }

    public function manualForm(string $shiftId): void
    {
        $sId = (int)$shiftId;
        $coordId = (int)Session::userId();
        $shift = $this->shiftModel->findWithDetails($sId);

        if (!$shift || ((int)$shift['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }

        $assignedList = $this->assignmentModel->getByShift($sId);
        $requiredSkills = $this->shiftModel->getRequiredSkills($sId);

        $availableVolunteers = $this->assignmentModel->getApprovedRegisteredVolunteersForShift($sId);

        $this->render('coordinator.assignment.manual', [
            'title'               => 'Điều Chỉnh Phân Công: ' . $shift['name'],
            'shift'               => $shift,
            'assignedList'        => $assignedList,
            'requiredSkills'      => $requiredSkills,
            'availableVolunteers' => $availableVolunteers,
            'csrfToken'           => Session::generateCsrf(),
            'success'             => Session::getFlash('success'),
            'error'               => Session::getFlash('error'),
        ]);
    }

    public function manualAdd(): void
    {
        $this->verifyCsrf();
        $coordId = (int)Session::userId();
        $shiftId = (int)$this->post('shift_id', 0);
        $userId  = (int)$this->post('user_id', 0);

        if ($shiftId <= 0 || $userId <= 0) {
            $this->redirect("/coordinator/assignment/manual/{$shiftId}", ['error' => 'Dữ liệu không hợp lệ.']);
            return;
        }

        $shift = $this->shiftModel->findWithDetails($shiftId);
        if (!$shift || ((int)$shift['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }

        $res = $this->assignmentModel->manualAdd($shiftId, $userId, $coordId);
        if ($res['success']) {
            $this->redirect("/coordinator/assignment/manual/{$shiftId}", ['success' => $res['message']]);
        } else {
            $this->redirect("/coordinator/assignment/manual/{$shiftId}", ['error' => $res['message']]);
        }
    }

    public function manualRemove(): void
    {
        $this->verifyCsrf();
        $coordId = (int)Session::userId();
        $assignmentId = (int)$this->post('assignment_id', 0);
        $shiftId      = (int)$this->post('shift_id', 0);
        $note         = trim($this->post('note', ''));

        $assignment = $assignmentId > 0 ? $this->assignmentModel->findById($assignmentId) : null;
        $shift = $assignment ? $this->shiftModel->findWithDetails((int)$assignment['shift_id']) : null;
        if (!$shift || ((int)$shift['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }

        $res = $this->assignmentModel->manualRemove($assignmentId, $coordId, $note);
        if ($res['success']) {
            $this->redirect("/coordinator/assignment/manual/{$shiftId}", ['success' => $res['message']]);
        } else {
            $this->redirect("/coordinator/assignment/manual/{$shiftId}", ['error' => $res['message']]);
        }
    }
}
