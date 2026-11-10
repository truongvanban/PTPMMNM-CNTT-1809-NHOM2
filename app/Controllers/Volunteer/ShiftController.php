<?php
/**
 * app/Controllers/Volunteer/ShiftController.php
 * Quản lý ca trực: xem ca có thể đăng ký, đăng ký, hủy đăng ký, xem lịch cá nhân, xác nhận tham gia
 */

declare(strict_types=1);

namespace App\Controllers\Volunteer;

use App\Core\Controller;
use App\Core\Session;
use App\Models\AssignmentModel;
use App\Models\RegistrationModel;
use App\Models\ShiftModel;

class ShiftController extends Controller
{
    protected string $layout = 'volunteer';
    private ShiftModel $shiftModel;
    private RegistrationModel $regModel;
    private AssignmentModel $asgnModel;

    public function __construct()
    {
        $this->shiftModel = new ShiftModel();
        $this->regModel = new RegistrationModel();
        $this->asgnModel = new AssignmentModel();
    }

    public function available(): void
    {
        $userId = (int)Session::userId();
        $page = max(1, (int)$this->get('page', 1));
        $activityId = filter_var($this->get('activity_id', 0), FILTER_VALIDATE_INT);
        $skillId = filter_var($this->get('skill_id', 0), FILTER_VALIDATE_INT);
        $activityId = $activityId !== false && $activityId > 0 ? $activityId : 0;
        $skillId = $skillId !== false && $skillId > 0 ? $skillId : 0;
        $result = $this->shiftModel->getAvailableForVolunteer(
            $userId,
            $page,
            $this->getPageSize(15),
            $activityId,
            $skillId
        );

        $this->render('volunteer.shifts', [
            'title'     => 'Ca Trực Có Thể Đăng Ký | VolunteerHub',
            'shifts'    => $result['data'],
            'pagination'=> array_merge($result, [
                'params' => [
                    'activity_id' => $activityId ?: '',
                    'skill_id' => $skillId ?: '',
                ],
            ]),
            'activities' => $this->shiftModel->getAvailableActivityFilters(),
            'requiredSkills' => $this->shiftModel->getAvailableRequiredSkillFilters(),
            'activityId' => $activityId,
            'skillId' => $skillId,
            'csrfToken' => Session::generateCsrf(),
            'success'   => Session::getFlash('success'),
            'error'     => Session::getFlash('error'),
        ]);
    }

    public function register(): void
    {
        $this->verifyCsrf();
        $userId = (int)Session::userId();
        $shiftId = (int)$this->post('shift_id', 0);
        $postedNote = $this->post('note', '');
        $note = is_string($postedNote) ? trim($postedNote) : '';

        $res = $this->regModel->register($userId, $shiftId, $note);
        if ($res['success']) {
            $this->redirect('/volunteer/shifts', ['success' => $res['message']]);
        } else {
            $this->redirect('/volunteer/shifts', ['error' => $res['message']]);
        }
    }

    public function unregister(): void
    {
        $this->verifyCsrf();
        $userId = (int)Session::userId();
        $shiftId = (int)$this->post('shift_id', 0);

        $res = $this->regModel->withdraw($userId, $shiftId);
        if ($res['success']) {
            $this->redirect('/volunteer/my-registrations', ['success' => $res['message']]);
        } else {
            $this->redirect('/volunteer/my-registrations', ['error' => $res['message']]);
        }
    }

    public function myRegistrations(): void
    {
        $userId = (int)Session::userId();
        $page = max(1, (int)$this->get('page', 1));
        $allowedStatuses = ['', 'pending', 'approved', 'rejected'];
        $requestedStatus = $this->get('status', '');
        $status = is_string($requestedStatus) && in_array($requestedStatus, $allowedStatuses, true)
            ? $requestedStatus
            : '';
        $requestedKeyword = $this->get('q', '');
        $keyword = is_string($requestedKeyword) ? trim(mb_substr($requestedKeyword, 0, 100)) : '';
        $requestedSort = $this->get('sort', 'recent');
        $sort = is_string($requestedSort) && in_array($requestedSort, ['recent', 'oldest', 'event'], true)
            ? $requestedSort
            : 'recent';
        $result = $this->regModel->getByUserPage(
            $userId,
            $page,
            $this->getPageSize(15),
            $status,
            $keyword,
            $sort
        );

        $this->render('volunteer.my_registrations', [
            'title'         => 'Ca Đã Đăng Ký | VolunteerHub',
            'registrations' => $result['data'],
            'pagination'    => $result,
            'status'        => $status,
            'keyword'       => $keyword,
            'sort'          => $sort,
            'statusCounts'  => $result['status_counts'],
            'csrfToken'     => Session::generateCsrf(),
            'success'       => Session::getFlash('success'),
            'error'         => Session::getFlash('error'),
        ]);
    }

    public function schedule(): void
    {
        $userId = (int)Session::userId();
        $requestedMonth = $this->get('month', '');
        $month = is_string($requestedMonth) && preg_match('/^\d{4}-\d{2}$/', $requestedMonth)
            ? \DateTimeImmutable::createFromFormat('!Y-m', $requestedMonth)
            : false;
        if ($month === false || $month->format('Y-m') !== $requestedMonth) {
            $month = new \DateTimeImmutable('first day of this month');
        }

        $this->render('volunteer.schedule', [
            'title'       => 'Lịch Phân Công Cá Nhân | VolunteerHub',
            'assignments' => $this->asgnModel->getByUser($userId, true),
            'selectedMonth' => $month->format('Y-m'),
            'csrfToken'   => Session::generateCsrf(),
            'success'     => Session::getFlash('success'),
            'error'       => Session::getFlash('error'),
        ]);
    }

    public function confirm(): void
    {
        $this->verifyCsrf();
        $userId = (int)Session::userId();
        $asgnId = (int)$this->post('assignment_id', 0);

        $res = $this->asgnModel->confirmAttendance($asgnId, $userId);
        if ($res['success']) {
            $this->redirect('/volunteer/schedule', ['success' => $res['message']]);
        } else {
            $this->redirect('/volunteer/schedule', ['error' => $res['message']]);
        }
    }
}
