<?php
/**
 * app/Controllers/Admin/UserController.php
 * Quản lý người dùng, vai trò và trạng thái tài khoản
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Database;
use App\Models\UserModel;

class UserController extends Controller
{
    protected string $layout = 'admin';
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index(): void
    {
        $keyword = trim($this->get('keyword', ''));
        $role    = trim($this->get('role', ''));
        $status  = trim($this->get('status', ''));
        $page    = max(1, (int)$this->get('page', 1));
        $perPage = $this->getPageSize(15);
        $sortBy  = trim($this->get('sort_by', 'created_at'));
        $sortDirection = strtoupper(trim($this->get('sort_direction', 'DESC')));
        if (!in_array($sortBy, ['name', 'email', 'status', 'created_at', 'last_login_at'], true)) {
            $sortBy = 'created_at';
        }
        if (!in_array($sortDirection, ['ASC', 'DESC'], true)) {
            $sortDirection = 'DESC';
        }

        $result = $this->userModel->search(
            $keyword,
            $role,
            $status,
            $page,
            $perPage,
            $sortBy,
            $sortDirection
        );
        $page = $result['current_page'];

        $db = Database::getInstance();
        $roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

        $this->render('admin.users.index', [
            'title'     => 'Quản Lý Tài Khoản | VolunteerHub',
            'users'     => $result['data'],
            'total'     => $result['total'],
            'page'      => $page,
            'lastPage'  => $result['last_page'],
            'perPage'   => $result['per_page'],
            'keyword'   => $keyword,
            'role'      => $role,
            'status'    => $status,
            'sortBy'    => $sortBy,
            'sortDirection' => $sortDirection,
            'currentAdminId' => (int)Session::userId(),
            'roles'     => $roles,
            'csrfToken' => Session::generateCsrf(),
            'success'   => Session::getFlash('success'),
            'error'     => Session::getFlash('error'),
        ]);
    }

    public function editForm(string $id): void
    {
        $userId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $user = $userId === false ? null : $this->userModel->findById($userId);
        if (!$user) {
            http_response_code(404);
            $this->render('errors.404', ['title' => 'Không tìm thấy | VolunteerHub']);
            return;
        }

        $this->render('admin.users.edit', [
            'title'     => 'Chỉnh Sửa Tài Khoản | VolunteerHub',
            'user'      => $user,
            'csrfToken' => Session::generateCsrf(),
            'success'   => Session::getFlash('success'),
            'error'     => Session::getFlash('error'),
        ]);
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();

        $userId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $user = $userId === false ? null : $this->userModel->findById($userId);
        if (!$user) {
            $this->redirect('/admin/users', ['error' => 'Không tìm thấy tài khoản cần cập nhật.']);
            return;
        }

        $fullName = $this->post('full_name', '');
        $email = $this->post('email', '');
        $phone = $this->post('phone', '');
        if (!is_string($fullName) || !is_string($email) || !is_string($phone)) {
            $this->redirect("/admin/users/{$userId}/edit", ['error' => 'Thông tin tài khoản không hợp lệ.']);
            return;
        }

        $fullName = trim($fullName);
        $email = strtolower(trim($email));
        $phone = trim($phone);
        if (strlen($fullName) < 2 || strlen($fullName) > 200
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 191
            || strlen($phone) > 20) {
            $this->redirect("/admin/users/{$userId}/edit", ['error' => 'Vui lòng kiểm tra họ tên, email và số điện thoại.']);
            return;
        }

        $existing = $this->userModel->findByEmail($email);
        if ($existing && (int)$existing['id'] !== $userId) {
            $this->redirect("/admin/users/{$userId}/edit", ['error' => 'Email này đã được sử dụng bởi tài khoản khác.']);
            return;
        }

        try {
            $this->userModel->updateAccountDetails($userId, $email, $fullName, $phone);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->redirect("/admin/users/{$userId}/edit", ['error' => 'Email này đã được sử dụng bởi tài khoản khác.']);
                return;
            }
            error_log('[Admin User Update Error] ' . $e->getMessage());
            $this->redirect("/admin/users/{$userId}/edit", ['error' => 'Không thể cập nhật tài khoản lúc này. Vui lòng thử lại.']);
            return;
        }

        $this->redirect('/admin/users', ['success' => 'Đã cập nhật thông tin tài khoản.']);
    }

    public function updateStatus(string $id): void
    {
        $this->verifyCsrf();
        $userId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($userId === false) {
            $this->redirect('/admin/users', ['error' => 'Tài khoản không hợp lệ.']);
        }
        $currentAdminId = (int)Session::userId();

        if ($userId === $currentAdminId) {
            $this->redirect('/admin/users', ['error' => 'Bạn không thể thay đổi trạng thái tài khoản của chính mình.']);
            return;
        }

        $status = $this->post('status', 'active');
        if (!is_string($status) || !in_array($status, ['active', 'inactive', 'locked'], true)) {
            $this->redirect('/admin/users', ['error' => 'Trạng thái tài khoản không hợp lệ.']);
        }
        if (!$this->userModel->updateStatus($userId, $status)) {
            $this->redirect('/admin/users', ['error' => 'Không thể cập nhật trạng thái tài khoản.']);
        }

        $statusLabels = ['active' => 'Hoạt động', 'inactive' => 'Ngừng hoạt động', 'locked' => 'Đã khóa'];
        $this->redirect('/admin/users', ['success' => "Đã chuyển tài khoản sang trạng thái {$statusLabels[$status]}."]);
    }

    public function deleteMultiple(): void
    {
        $this->verifyCsrf();

        $userIds = $this->post('user_ids', []);
        if (!is_array($userIds) || !$userIds) {
            $this->redirect('/admin/users', ['error' => 'Vui lòng chọn ít nhất một tài khoản để xóa.']);
        }

        $ids = [];
        foreach ($userIds as $userId) {
            if (!is_string($userId) && !is_int($userId)) {
                $this->redirect('/admin/users', ['error' => 'Danh sách tài khoản cần xóa không hợp lệ.']);
            }
            $validatedId = filter_var($userId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($validatedId === false) {
                $this->redirect('/admin/users', ['error' => 'Danh sách tài khoản cần xóa không hợp lệ.']);
            }
            $ids[] = $validatedId;
        }

        try {
            $deletedCount = $this->userModel->deleteUsers($ids, (int)Session::userId());
        } catch (\DomainException $e) {
            $this->redirect('/admin/users', ['error' => $e->getMessage()]);
        }

        if ($deletedCount === 0) {
            $this->redirect('/admin/users', ['error' => 'Không tìm thấy tài khoản nào để xóa.']);
        }

        $this->redirect('/admin/users', ['success' => "Đã xóa $deletedCount tài khoản và dữ liệu liên quan."]);
    }

    public function deleteAll(): void
    {
        $this->verifyCsrf();
        $confirmation = $this->post('confirm_text', '');
        if (!is_string($confirmation) || $confirmation !== 'DELETE') {
            $this->redirect('/admin/users', ['error' => 'Xác nhận không hợp lệ. Hãy nhập chính xác DELETE để tiếp tục.']);
        }

        try {
            $deletedCount = $this->userModel->deleteAllUsers((int)Session::userId());
        } catch (\DomainException $e) {
            $this->redirect('/admin/users', ['error' => $e->getMessage()]);
        }

        if ($deletedCount === 0) {
            $this->redirect('/admin/users', ['error' => 'Không có tài khoản nào khác để xóa.']);
        }

        $this->redirect('/admin/users', ['success' => "Đã xóa tất cả $deletedCount tài khoản (giữ lại tài khoản đang đăng nhập)."]);
    }

    public function updateRole(string $id): void
    {
        $this->verifyCsrf();
        $userId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $roleId = filter_var($this->post('role_id', 0), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $action = $this->post('action', '');

        if ($userId === false || $roleId === false || !is_string($action)
            || !in_array($action, ['add', 'remove'], true)) {
            $this->redirect('/admin/users', ['error' => 'Vai trò không hợp lệ.']);
        }

        if ($userId === (int)Session::userId()) {
            $this->redirect('/admin/users', ['error' => 'Bạn không thể thay đổi vai trò của chính mình.']);
        }

        try {
            $changed = $this->userModel->changeRole($userId, $roleId, $action);
        } catch (\DomainException $e) {
            $this->redirect('/admin/users', ['error' => $e->getMessage()]);
        }

        if (!$changed) {
            $this->redirect('/admin/users', ['error' => 'Không thể cập nhật vai trò. Kiểm tra tài khoản và vai trò đã chọn.']);
        }

        $this->redirect('/admin/users', [
            'success' => $action === 'add' ? 'Đã thêm vai trò cho tài khoản.' : 'Đã gỡ vai trò khỏi tài khoản.',
        ]);
    }
}
