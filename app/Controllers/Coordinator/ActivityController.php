<?php
/**
 * app/Controllers/Coordinator/ActivityController.php
 * Quản lý hoạt động tình nguyện của Điều phối viên
 */

declare(strict_types=1);

namespace App\Controllers\Coordinator;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ActivityModel;

class ActivityController extends Controller
{
    protected string $layout = 'coordinator';
    private ActivityModel $activityModel;

    public function __construct()
    {
        $this->activityModel = new ActivityModel();
    }

    public function index(): void
    {
        $coordId = (int)Session::userId();
        $status = $this->get('status', '');
        $keyword = trim($this->get('keyword', ''));
        $page = max(1, (int)$this->get('page', 1));
        $result = $this->activityModel->getByCoordinatorPage(
            $coordId,
            $status,
            $keyword,
            $page,
            $this->getPageSize(15)
        );
        $allActivities = $this->activityModel->getByCoordinator($coordId);
        $activityStats = [
            'total' => count($allActivities),
            'open' => 0,
            'draft' => 0,
            'closed' => 0,
            'shifts' => 0,
            'registrations' => 0,
        ];
        foreach ($allActivities as $activity) {
            if (isset($activityStats[$activity['status']])) {
                $activityStats[$activity['status']]++;
            }
            $activityStats['shifts'] += (int)$activity['shift_count'];
            $activityStats['registrations'] += (int)$activity['reg_count'];
        }

        $this->render('coordinator.activities.index', [
            'title'      => 'Quản Lý Hoạt Động | VolunteerHub',
            'activities' => $result['data'],
            'total' => $result['total'],
            'page' => $result['current_page'],
            'lastPage' => $result['last_page'],
            'perPage' => $result['per_page'],
            'keyword' => $keyword,
            'status'     => $status,
            'activityStats' => $activityStats,
            'csrfToken'  => Session::generateCsrf(),
            'success'    => Session::getFlash('success'),
            'error'      => Session::getFlash('error'),
        ]);
    }

    public function createForm(): void
    {
        $this->render('coordinator.activities.create', [
            'title'     => 'Tạo Hoạt Động Mới | VolunteerHub',
            'csrfToken' => Session::generateCsrf(),
            'error'     => Session::getFlash('error'),
        ]);
    }

    public function create(): void
    {
        $this->verifyCsrf();
        $coordId = (int)Session::userId();

        $title       = trim($this->post('title', ''));
        $description = trim($this->post('description', ''));
        $location    = trim($this->post('location', ''));
        $dateStart   = $this->post('date_start', '');
        $dateEnd     = $this->post('date_end', '');
        $deadline    = $this->post('registration_deadline', '');
        $maxVol      = (int)$this->post('max_volunteers', 0);
        $status      = $this->post('status', 'draft');

        if (empty($title) || empty($dateStart) || empty($dateEnd)) {
            $this->redirect('/coordinator/activities/create', ['error' => 'Vui lòng điền các trường bắt buộc (tiêu đề, ngày bắt đầu, ngày kết thúc).']);
            return;
        }

        if (strtotime($dateEnd) <= strtotime($dateStart)) {
            $this->redirect('/coordinator/activities/create', ['error' => 'Thời gian kết thúc phải sau thời gian bắt đầu.']);
            return;
        }

        $actId = $this->activityModel->create([
            'coordinator_id'        => $coordId,
            'title'                 => $title,
            'description'           => $description,
            'location'              => $location,
            'date_start'            => $dateStart,
            'date_end'              => $dateEnd,
            'registration_deadline' => $deadline ?: null,
            'status'                => in_array($status, ['draft','open','closed'], true) ? $status : 'draft',
            'max_volunteers'        => $maxVol > 0 ? $maxVol : null,
        ]);

        $this->redirect("/coordinator/activities/{$actId}/shifts", ['success' => 'Đã tạo hoạt động! Hãy thêm các ca trực cho hoạt động này.']);
    }

    public function editForm(string $id): void
    {
        $actId = (int)$id;
        $coordId = (int)Session::userId();
        $activity = $this->activityModel->findById($actId);

        if (!$activity || ((int)$activity['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }

        $this->render('coordinator.activities.edit', [
            'title'     => 'Chỉnh Sửa Hoạt Động | VolunteerHub',
            'activity'  => $activity,
            'csrfToken' => Session::generateCsrf(),
            'error'     => Session::getFlash('error'),
            'success'   => Session::getFlash('success'),
        ]);
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $actId = (int)$id;
        $coordId = (int)Session::userId();
        $activity = $this->activityModel->findById($actId);

        if (!$activity || ((int)$activity['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }

        $title       = trim($this->post('title', ''));
        $description = trim($this->post('description', ''));
        $location    = trim($this->post('location', ''));
        $dateStart   = $this->post('date_start', '');
        $dateEnd     = $this->post('date_end', '');
        $deadline    = $this->post('registration_deadline', '');
        $maxVol      = (int)$this->post('max_volunteers', 0);
        $status      = $this->post('status', $activity['status']);

        if (empty($title) || empty($dateStart) || empty($dateEnd)) {
            $this->redirect("/coordinator/activities/{$actId}/edit", ['error' => 'Vui lòng điền đủ các thông tin bắt buộc.']);
            return;
        }

        if (strtotime($dateEnd) <= strtotime($dateStart)) {
            $this->redirect("/coordinator/activities/{$actId}/edit", ['error' => 'Thời gian kết thúc phải sau thời gian bắt đầu.']);
            return;
        }

        $this->activityModel->update($actId, [
            'title'                 => $title,
            'description'           => $description,
            'location'              => $location,
            'date_start'            => $dateStart,
            'date_end'              => $dateEnd,
            'registration_deadline' => $deadline ?: null,
            'status'                => $status,
            'max_volunteers'        => $maxVol > 0 ? $maxVol : null,
        ]);

        $this->redirect("/coordinator/activities", ['success' => 'Cập nhật hoạt động thành công!']);
    }

    public function updateStatus(string $id): void
    {
        $this->verifyCsrf();
        $actId = (int)$id;
        $coordId = (int)Session::userId();
        $activity = $this->activityModel->findById($actId);

        if (!$activity || ((int)$activity['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }

        $status = $this->post('status', '');
        $allowed = ['draft', 'open', 'closed', 'ongoing', 'completed', 'cancelled'];
        if (in_array($status, $allowed, true)) {
            $this->activityModel->updateStatus($actId, $status);
            $this->redirect('/coordinator/activities', ['success' => "Đã chuyển trạng thái hoạt động sang: $status"]);
        } else {
            $this->redirect('/coordinator/activities', ['error' => 'Trạng thái không hợp lệ.']);
        }
    }
}
