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

class SkillController extends Controller
{
    protected string $layout = 'admin';
    private SkillModel $skillModel;

    public function __construct()
    {
        $this->skillModel = new SkillModel();
    }

    public function index(): void
    {
        $page = max(1, (int)$this->get('page', 1));
        $result = $this->skillModel->getAdminPage($page, $this->getPageSize(15));

        $this->render('admin.skills.index', [
            'title'     => 'Quản Lý Danh Mục Kỹ Năng | VolunteerHub',
            'skills'    => $result['data'],
            'total'     => $result['total'],
            'page'      => $result['current_page'],
            'lastPage'  => $result['last_page'],
            'perPage'   => $result['per_page'],
            'csrfToken' => Session::generateCsrf(),
            'success'   => Session::getFlash('success'),
            'error'     => Session::getFlash('error'),
        ]);
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
