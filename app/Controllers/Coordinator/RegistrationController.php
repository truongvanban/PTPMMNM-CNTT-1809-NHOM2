<?php
/**
 * app/Controllers/Coordinator/RegistrationController.php
 * Quản lý và duyệt đơn đăng ký tham gia ca trực
 */

declare(strict_types=1);

namespace App\Controllers\Coordinator;

use App\Core\Controller;
use App\Core\Session;
use App\Models\RegistrationModel;

class RegistrationController extends Controller
{
    protected string $layout = 'coordinator';
    private RegistrationModel $regModel;

    public function __construct()
    {
        $this->regModel = new RegistrationModel();
    }

    public function index(): void
    {
        $coordId = (int)Session::userId();
        $status = $this->get('status', '');
        $page = max(1, (int)$this->get('page', 1));
        $result = $this->regModel->getByCoordinator($coordId, $status, $page, $this->getPageSize(15));

        $this->render('coordinator.registrations.index', [
            'title'         => 'Quản Lý Đăng Ký | VolunteerHub',
            'registrations' => $result['data'],
            'total'         => $result['total'],
            'page'          => $result['current_page'],
            'lastPage'      => $result['last_page'],
            'perPage'       => $result['per_page'],
            'status'        => $status,
            'csrfToken'     => Session::generateCsrf(),
            'success'       => Session::getFlash('success'),
            'error'         => Session::getFlash('error'),
        ]);
    }

    public function approve(): void
    {
        $this->verifyCsrf();
        $regId = (int)$this->post('id', 0);
        $updated = $this->regModel->updateStatusForCoordinator(
            $regId,
            (int)Session::userId(),
            'approved',
            Session::hasRole('admin')
        );
        $this->redirect('/coordinator/registrations', [
            ($updated ? 'success' : 'error') => $updated ? 'Đã duyệt đơn đăng ký.' : 'Đơn không tồn tại, đã xử lý hoặc không thuộc hoạt động của bạn.',
        ]);
    }

    public function reject(): void
    {
        $this->verifyCsrf();
        $regId = (int)$this->post('id', 0);
        $updated = $this->regModel->updateStatusForCoordinator(
            $regId,
            (int)Session::userId(),
            'rejected',
            Session::hasRole('admin')
        );
        $this->redirect('/coordinator/registrations', [
            ($updated ? 'success' : 'error') => $updated ? 'Đã từ chối đơn đăng ký.' : 'Đơn không tồn tại, đã xử lý hoặc không thuộc hoạt động của bạn.',
        ]);
    }
}
