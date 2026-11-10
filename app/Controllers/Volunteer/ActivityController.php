<?php
/**
 * app/Controllers/Volunteer/ActivityController.php
 * Duyệt hoạt động tình nguyện và đăng ký ca trực trực tiếp
 */

declare(strict_types=1);

namespace App\Controllers\Volunteer;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ActivityModel;
use App\Models\RegistrationModel;
use App\Models\ShiftModel;

class ActivityController extends Controller
{
    protected string $layout = 'volunteer';
    private ActivityModel $activityModel;

    public function __construct()
    {
        $this->activityModel = new ActivityModel();
    }

    /**
     * Danh sách hoạt động đang mở cho TNV duyệt
     */
    public function index(): void
    {
        $page = max(1, (int)$this->get('page', 1));
        $categories = $this->activityModel->getOpenActivityCategories();
        $requestedCategory = $this->get('category', '');
        $category = is_string($requestedCategory) && in_array($requestedCategory, $categories, true)
            ? $requestedCategory
            : '';
        $requestedSort = $this->get('sort', 'date');
        $sort = $requestedSort === 'category' ? 'category' : 'date';
        $result = $this->activityModel->getAllOpenActivities(
            $page,
            $this->getPageSize(10),
            $category,
            $sort
        );

        $this->render('volunteer.activities', [
            'title'      => 'Khám Phá Hoạt Động Tình Nguyện | VolunteerHub',
            'activities' => $result['data'],
            'pagination' => $result,
            'categories' => $categories,
            'selectedCategory' => $category,
            'sort' => $sort,
            'csrfToken'  => Session::generateCsrf(),
            'success'    => Session::getFlash('success'),
            'error'      => Session::getFlash('error'),
        ]);
    }

    /**
     * Chi tiết hoạt động + danh sách ca trực để đăng ký
     */
    public function detail(string $id): void
    {
        $actId  = (int)$id;
        $userId = (int)Session::userId();

        $activity = $this->activityModel->findWithCoordinator($actId);
        if (!$activity) {
            http_response_code(404);
            $this->render('errors.404', ['title' => 'Không tìm thấy | VolunteerHub']);
            return;
        }

        $page = max(1, (int)$this->get('page', 1));
        $shiftResult = $this->activityModel->getActivityShiftsForVolunteer(
            $actId,
            $userId,
            $page,
            $this->getPageSize(10)
        );

        $this->render('volunteer.activity_detail', [
            'title'     => $activity['title'] . ' | VolunteerHub',
            'activity'  => $activity,
            'requiredSkills' => $this->activityModel->getRequiredSkillsForActivity($actId),
            'attachments' => $this->activityModel->getAttachmentsForActivity($actId),
            'shifts'    => $shiftResult['data'],
            'pagination'=> $shiftResult,
            'csrfToken' => Session::generateCsrf(),
            'success'   => Session::getFlash('success'),
            'error'     => Session::getFlash('error'),
        ]);
    }

    /**
     * Đăng ký ca trực từ trang chi tiết hoạt động
     */
    public function registerShift(): void
    {
        $this->verifyCsrf();
        $userId = (int)Session::userId();
        $shiftId = (int)$this->post('shift_id', 0);
        $postedNote = $this->post('note', '');
        $postedReturnTo = $this->post('return_to', '');
        $note = is_string($postedNote) ? trim($postedNote) : '';

        $shift = (new ShiftModel())->findWithDetails($shiftId);
        if (!$shift) {
            $this->redirect('/volunteer/activities', ['error' => 'Ca trực không tồn tại.']);
            return;
        }

        $activityId = (int)$shift['activity_id'];
        $volunteerPath = "/volunteer/activities/$activityId";
        $publicPath = "/activities/$activityId";
        $returnPath = is_string($postedReturnTo) && in_array($postedReturnTo, [$volunteerPath, $publicPath], true)
            ? $postedReturnTo
            : $volunteerPath;
        $regModel = new RegistrationModel();
        $res = $regModel->register($userId, $shiftId, $note);

        if ($res['success']) {
            $this->redirect($returnPath, ['success' => $res['message']]);
        } else {
            $this->redirect($returnPath, ['error' => $res['message']]);
        }
    }
}
