<?php
/**
 * app/Controllers/Coordinator/ShiftController.php
 * Quản lý ca trực thuộc hoạt động
 */

declare(strict_types=1);

namespace App\Controllers\Coordinator;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ActivityModel;
use App\Models\ShiftModel;
use App\Models\SkillModel;

class ShiftController extends Controller
{
    protected string $layout = 'coordinator';
    private ShiftModel $shiftModel;
    private ActivityModel $activityModel;
    private SkillModel $skillModel;

    public function __construct()
    {
        $this->shiftModel = new ShiftModel();
        $this->activityModel = new ActivityModel();
        $this->skillModel = new SkillModel();
    }

    public function index(string $activityId): void
    {
        $actId = (int)$activityId;
        $coordId = (int)Session::userId();
        $activity = $this->activityModel->findById($actId);

        if (!$activity || ((int)$activity['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }

        $page = max(1, (int)$this->get('page', 1));
        $result = $this->shiftModel->getByActivityPage($actId, $page, $this->getPageSize(15));

        $this->render('coordinator.shifts.index', [
            'title'     => 'Quản Lý Ca Trực: ' . $activity['title'],
            'activity'  => $activity,
            'shifts'    => $result['data'],
            'total'     => $result['total'],
            'page'      => $result['current_page'],
            'lastPage'  => $result['last_page'],
            'perPage'   => $result['per_page'],
            'csrfToken' => Session::generateCsrf(),
            'success'   => Session::getFlash('success'),
            'error'     => Session::getFlash('error'),
        ]);
    }

    public function createForm(): void
    {
        $actId = (int)$this->get('activity_id', 0);
        $coordId = (int)Session::userId();
        $activity = $this->activityModel->findById($actId);

        if (!$activity || ((int)$activity['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }

        $skills = $this->skillModel->getActive();

        $this->render('coordinator.shifts.create', [
            'title'     => 'Thêm Ca Trực Mới | VolunteerHub',
            'activity'  => $activity,
            'skills'    => $skills,
            'csrfToken' => Session::generateCsrf(),
            'error'     => Session::getFlash('error'),
        ]);
    }

    public function create(): void
    {
        $this->verifyCsrf();
        $coordId = (int)Session::userId();
        $actId = (int)$this->post('activity_id', 0);
        $activity = $this->activityModel->findById($actId);

        if (!$activity || ((int)$activity['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }

        $name       = trim($this->post('name', ''));
        $description = trim($this->post('description', ''));
        $date       = $this->post('date', '');
        $timeStart  = $this->post('time_start', '');
        $timeEnd    = $this->post('time_end', '');
        $minVol     = max(1, (int)$this->post('min_volunteers', 1));
        $maxVol     = max($minVol, (int)$this->post('max_volunteers', 10));
        $location   = trim($this->post('location', ''));

        if (empty($name) || empty($date) || empty($timeStart) || empty($timeEnd)) {
            $this->redirect("/coordinator/shifts/create?activity_id={$actId}", ['error' => 'Vui lòng điền đủ thông tin tên ca, ngày và giờ.']);
            return;
        }

        if ($timeEnd <= $timeStart) {
            $this->redirect("/coordinator/shifts/create?activity_id={$actId}", ['error' => 'Giờ kết thúc phải lớn hơn giờ bắt đầu.']);
            return;
        }

        $shiftId = $this->shiftModel->create([
            'activity_id'    => $actId,
            'name'           => $name,
            'description'    => $description,
            'date'           => $date,
            'time_start'     => $timeStart,
            'time_end'       => $timeEnd,
            'min_volunteers' => $minVol,
            'max_volunteers' => $maxVol,
            'location'       => $location,
        ]);

        // Xử lý kỹ năng yêu cầu
        $selectedSkills = $this->post('skills', []);
        $mandatorySkills = $this->post('mandatory', []);
        $skillsData = [];
        if (is_array($selectedSkills)) {
            foreach ($selectedSkills as $skId) {
                $skId = (int)$skId;
                $isMandatory = isset($mandatorySkills[$skId]) ? 1 : 0;
                $skillsData[] = ['skill_id' => $skId, 'is_mandatory' => $isMandatory];
            }
        }
        $this->shiftModel->setRequiredSkills($shiftId, $skillsData);

        $this->redirect("/coordinator/activities/{$actId}/shifts", ['success' => 'Đã tạo ca trực thành công!']);
    }

    public function editForm(string $id): void
    {
        $shiftId = (int)$id;
        $coordId = (int)Session::userId();
        $shift = $this->shiftModel->findWithDetails($shiftId);

        if (!$shift || ((int)$shift['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }

        $skills = $this->skillModel->getActive();
        $currentSkills = $this->shiftModel->getRequiredSkills($shiftId);

        $currentSkillMap = [];
        foreach ($currentSkills as $cs) {
            $currentSkillMap[(int)$cs['skill_id']] = (int)$cs['is_mandatory'];
        }

        $this->render('coordinator.shifts.edit', [
            'title'           => 'Chỉnh Sửa Ca: ' . $shift['name'],
            'shift'           => $shift,
            'skills'          => $skills,
            'currentSkillMap' => $currentSkillMap,
            'csrfToken'       => Session::generateCsrf(),
            'error'           => Session::getFlash('error'),
            'success'         => Session::getFlash('success'),
        ]);
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $shiftId = (int)$id;
        $coordId = (int)Session::userId();
        $shift = $this->shiftModel->findWithDetails($shiftId);

        if (!$shift || ((int)$shift['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }

        $name        = trim($this->post('name', ''));
        $description = trim($this->post('description', ''));
        $date        = $this->post('date', '');
        $timeStart   = $this->post('time_start', '');
        $timeEnd     = $this->post('time_end', '');
        $minVol      = max(1, (int)$this->post('min_volunteers', 1));
        $maxVol      = max($minVol, (int)$this->post('max_volunteers', 10));
        $location    = trim($this->post('location', ''));
        $status      = $this->post('status', $shift['status']);

        if (empty($name) || empty($date) || empty($timeStart) || empty($timeEnd)) {
            $this->redirect("/coordinator/shifts/{$shiftId}/edit", ['error' => 'Vui lòng điền đủ thông tin.']);
            return;
        }

        if ($timeEnd <= $timeStart) {
            $this->redirect("/coordinator/shifts/{$shiftId}/edit", ['error' => 'Giờ kết thúc phải lớn hơn giờ bắt đầu.']);
            return;
        }

        $this->shiftModel->update($shiftId, [
            'name'           => $name,
            'description'    => $description,
            'date'           => $date,
            'time_start'     => $timeStart,
            'time_end'       => $timeEnd,
            'min_volunteers' => $minVol,
            'max_volunteers' => $maxVol,
            'location'       => $location,
            'status'         => in_array($status, ['open','closed','full','cancelled'], true) ? $status : 'open',
        ]);

        // Cập nhật kỹ năng
        $selectedSkills = $this->post('skills', []);
        $mandatorySkills = $this->post('mandatory', []);
        $skillsData = [];
        if (is_array($selectedSkills)) {
            foreach ($selectedSkills as $skId) {
                $skId = (int)$skId;
                $isMandatory = isset($mandatorySkills[$skId]) ? 1 : 0;
                $skillsData[] = ['skill_id' => $skId, 'is_mandatory' => $isMandatory];
            }
        }
        $this->shiftModel->setRequiredSkills($shiftId, $skillsData);

        $this->redirect("/coordinator/activities/{$shift['activity_id']}/shifts", ['success' => 'Cập nhật ca trực thành công!']);
    }

    public function close(string $id): void
    {
        $this->verifyCsrf();
        $shiftId = (int)$id;
        $coordId = (int)Session::userId();
        $shift = $this->shiftModel->findWithDetails($shiftId);

        if (!$shift || ((int)$shift['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }

        if (!$this->shiftModel->close($shiftId)) {
            $this->redirect(
                "/coordinator/activities/{$shift['activity_id']}/shifts",
                ['error' => 'Ca trực đã đóng hoặc không còn ở trạng thái có thể đóng.']
            );
            return;
        }

        $this->redirect(
            "/coordinator/activities/{$shift['activity_id']}/shifts",
            ['success' => 'Đã đóng ca trực. Tình nguyện viên không thể đăng ký ca này nữa.']
        );
    }
}
