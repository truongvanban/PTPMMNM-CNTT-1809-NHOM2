<?php
/**
 * app/Controllers/Admin/SkillController.php
 * Quản lý danh mục kỹ năng của toàn hệ thống
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\SkillModel;
use App\Models\SkillRequestModel;

class SkillController extends Controller
{
    protected string $layout = 'admin';
    private SkillModel $skillModel;
    private SkillRequestModel $skillRequestModel;

    public function __construct()
    {
        $this->skillModel = new SkillModel();
        $this->skillRequestModel = new SkillRequestModel();
    }

    public function index(): void
    {
        $page = max(1, (int)$this->get('page', 1));
        $result = $this->skillModel->getAdminPage($page, $this->getPageSize(15));
        $requestPage = max(1, (int)$this->get('requests_page', 1));
        $requestPerPage = $this->getPageSize(15);
        $requests = $this->skillRequestModel->getAdminPage($requestPage, $requestPerPage);

        $this->render('admin.skills.index', [
            'title'     => 'Quản Lý Danh Mục Kỹ Năng | VolunteerHub',
            'skills'    => $result['data'],
            'total'     => $result['total'],
            'page'      => $result['current_page'],
            'lastPage'  => $result['last_page'],
            'perPage'   => $result['per_page'],
            'skillRequests' => $requests['data'],
            'requestsTotal' => $requests['total'],
            'requestsPage' => $requests['current_page'],
            'requestsLastPage' => $requests['last_page'],
            'requestPerPage' => $requests['per_page'],
            'pendingSkillRequestCount' => $requests['pending'],
            'csrfToken' => Session::generateCsrf(),
            'success'   => Session::getFlash('success'),
            'error'     => Session::getFlash('error'),
        ]);
    }

    public function coordinatorRequests(): void
    {
        $page = max(1, (int)$this->get('page', 1));
        $requests = $this->skillRequestModel->getAdminPage($page, $this->getPageSize(15));
        $this->layout = 'coordinator';
        $this->render('coordinator.skill_requests', [
            'title' => 'Duyệt yêu cầu kỹ năng | VolunteerHub',
            'skillRequests' => $requests['data'],
            'total' => $requests['total'],
            'page' => $requests['current_page'],
            'lastPage' => $requests['last_page'],
            'perPage' => $requests['per_page'],
            'csrfToken' => Session::generateCsrf(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
        ]);
    }

    public function approveRequest(string $id): void
    {
        $this->verifyCsrf();
        $requestId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($requestId === false) {
            $this->redirect($this->requestListPath(), ['error' => 'Yêu cầu kỹ năng không hợp lệ.']);
            return;
        }

        $result = $this->skillRequestModel->approve($requestId);
        $this->redirect($this->requestListPath(), [
            $result['success'] ? 'success' : 'error' => $result['message'],
        ]);
    }

    public function rejectRequest(string $id): void
    {
        $this->verifyCsrf();
        $requestId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $postedNote = $this->post('admin_note', '');
        $note = is_string($postedNote) ? $postedNote : '';
        if ($requestId === false) {
            $this->redirect($this->requestListPath(), ['error' => 'Yêu cầu kỹ năng không hợp lệ.']);
            return;
        }

        $result = $this->skillRequestModel->reject($requestId, $note);
        $this->redirect($this->requestListPath(), [
            $result['success'] ? 'success' : 'error' => $result['message'],
        ]);
    }

    private function requestListPath(): string
    {
        return Session::hasRole('admin') ? '/admin/skills' : '/coordinator/skill-requests';
    }

    public function create(): void
    {
        $this->verifyCsrf();
        $name        = trim($this->post('name', ''));
        $category    = trim($this->post('category', ''));
        $description = trim($this->post('description', ''));

        if (empty($name)) {
            $this->redirect('/admin/skills', ['error' => 'Tên kỹ năng không được để trống.']);
            return;
        }

        $this->skillModel->create($name, $category, $description);
        $this->redirect('/admin/skills', ['success' => "Đã thêm kỹ năng mới: $name"]);
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $skillId     = (int)$id;
        $name        = trim($this->post('name', ''));
        $category    = trim($this->post('category', ''));
        $description = trim($this->post('description', ''));
        $isActive    = (int)$this->post('is_active', 1);

        if (empty($name)) {
            $this->redirect('/admin/skills', ['error' => 'Tên kỹ năng không được để trống.']);
            return;
        }

        $this->skillModel->update($skillId, $name, $category, $description, $isActive);
        $this->redirect('/admin/skills', ['success' => 'Cập nhật kỹ năng thành công!']);
    }

    public function delete(string $id): void
    {
        $this->verifyCsrf();
        $skillId = (int)$id;
        $this->skillModel->deleteById($skillId);
        $this->redirect('/admin/skills', ['success' => 'Đã xóa kỹ năng khỏi hệ thống.']);
    }
}
