<?php
/**
 * app/Controllers/Volunteer/AvailabilityController.php
 * Quản lý lịch rảnh của tình nguyện viên
 */

declare(strict_types=1);

namespace App\Controllers\Volunteer;

use App\Core\Controller;
use App\Core\Session;
use App\Models\AvailabilityModel;

class AvailabilityController extends Controller
{
    protected string $layout = 'volunteer';
    private AvailabilityModel $availModel;

    public function __construct()
    {
        $this->availModel = new AvailabilityModel();
    }

    public function index(): void
    {
        $userId = (int)Session::userId();
        $page = max(1, (int)$this->get('page', 1));
        $result = $this->availModel->getByUserPage($userId, $page, $this->getPageSize(15));

        $this->render('volunteer.availability', [
            'title'          => 'Khai Báo Lịch Rảnh | VolunteerHub',
            'availabilities' => $result['data'],
            'pagination'     => $result,
            'csrfToken'      => Session::generateCsrf(),
            'success'        => Session::getFlash('success'),
            'error'          => Session::getFlash('error'),
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $userId = (int)Session::userId();

        $date      = $this->post('date', '');
        $timeStart = $this->post('time_start', '');
        $timeEnd   = $this->post('time_end', '');
        $note      = trim($this->post('note', ''));

        if (empty($date) || empty($timeStart) || empty($timeEnd)) {
            $this->redirect('/volunteer/availability', ['error' => 'Vui lòng nhập đầy đủ ngày và khoảng thời gian.']);
            return;
        }

        $res = $this->availModel->add($userId, $date, $timeStart, $timeEnd, $note);
        if ($res['success']) {
            $this->redirect('/volunteer/availability', ['success' => $res['message']]);
        } else {
            $this->redirect('/volunteer/availability', ['error' => $res['message']]);
        }
    }

    public function delete(): void
    {
        $this->verifyCsrf();
        $userId = (int)Session::userId();
        $id = (int)$this->post('id', 0);

        if ($this->availModel->deleteForUser($id, $userId)) {
            $this->redirect('/volunteer/availability', ['success' => 'Đã xóa lịch rảnh.']);
        } else {
            $this->redirect('/volunteer/availability', ['error' => 'Không thể xóa lịch này.']);
        }
    }
}
