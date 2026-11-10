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
        $allowedStatuses = ['', 'pending', 'approved', 'rejected'];
        $requestedStatus = $this->get('status', '');
        $status = is_string($requestedStatus) && in_array($requestedStatus, $allowedStatuses, true)
            ? $requestedStatus
            : '';
        $page = max(1, (int)$this->get('page', 1));
        $requestedKeyword = $this->get('q', '');
        $keyword = is_string($requestedKeyword) ? trim(mb_substr($requestedKeyword, 0, 100)) : '';
        $requestedSort = $this->get('sort', 'recent');
        $sort = is_string($requestedSort) && in_array($requestedSort, ['recent', 'oldest', 'shift_date'], true)
            ? $requestedSort
            : 'recent';
        $result = $this->regModel->getByCoordinator(
            $coordId,
            $status,
            $page,
            $this->getPageSize(15),
            $keyword,
            $sort
        );

        $this->render('coordinator.registrations.index', [
            'title'         => 'Quản Lý Đăng Ký | VolunteerHub',
            'registrations' => $result['data'],
            'total'         => $result['total'],
            'page'          => $result['current_page'],
            'lastPage'      => $result['last_page'],
            'perPage'       => $result['per_page'],
            'status'        => $status,
            'keyword'       => $keyword,
            'sort'          => $sort,
            'statusCounts'  => $result['status_counts'],
            'csrfToken'     => Session::generateCsrf(),
            'success'       => Session::getFlash('success'),
            'error'         => Session::getFlash('error'),
        ]);
    }

    public function approve(): void
    {
        $this->verifyCsrf();
        $regId = (int)$this->post('id', 0);
        $result = $this->regModel->updateStatusForCoordinator(
            $regId,
            (int)Session::userId(),
            'approved',
            Session::hasRole('admin')
        );
        $this->redirect($this->registrationListPath(), [$result['success'] ? 'success' : 'error' => $result['message']]);
    }

    public function reject(): void
    {
        $this->verifyCsrf();
        $regId = (int)$this->post('id', 0);
        $result = $this->regModel->updateStatusForCoordinator(
            $regId,
            (int)Session::userId(),
            'rejected',
            Session::hasRole('admin')
        );
        $this->redirect($this->registrationListPath(), [$result['success'] ? 'success' : 'error' => $result['message']]);
    }

    private function registrationListPath(): string
    {
        $allowedStatuses = ['', 'pending', 'approved', 'rejected'];
        $status = $this->post('return_status', '');
        if (!is_string($status) || !in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        $page = filter_var($this->post('return_page', 1), FILTER_VALIDATE_INT);
        $page = $page !== false ? max(1, $page) : 1;

        $perPage = $this->post('return_per_page', '15');
        if (
            (!is_string($perPage) && !is_int($perPage))
            || !in_array((string)$perPage, ['5', '10', '15', '20', '50', 'all'], true)
        ) {
            $perPage = '15';
        }

        $keyword = $this->post('return_keyword', '');
        $keyword = is_string($keyword) ? mb_substr(trim($keyword), 0, 100) : '';
        $sort = $this->post('return_sort', 'recent');
        if (!is_string($sort) || !in_array($sort, ['recent', 'oldest', 'shift_date'], true)) {
            $sort = 'recent';
        }

        $query = array_filter([
            'status' => $status,
            'page' => $page,
            'per_page' => (string)$perPage,
            'q' => $keyword,
            'sort' => $sort,
        ], static fn (string|int $value): bool => $value !== '');

        return '/coordinator/registrations?' . http_build_query($query);
    }
}
