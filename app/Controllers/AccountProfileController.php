<?php
/**
 * Quản lý hồ sơ và mật khẩu tài khoản nội bộ.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\UserModel;

abstract class AccountProfileController extends Controller
{
    protected string $section;
    protected string $roleLabel;
    private UserModel $userModel;

    protected function initializeProfile(string $section, string $roleLabel): void
    {
        $this->section = $section;
        $this->layout = $section;
        $this->roleLabel = $roleLabel;
        $this->userModel = new UserModel();
    }

    public function index(): void
    {
        $user = $this->userModel->findWithRoles((int)Session::userId());
        if (!$user) {
            $this->redirect('/login', ['error' => 'Không tìm thấy tài khoản. Vui lòng đăng nhập lại.']);
            return;
        }

        $this->render('account.profile', [
            'title' => 'Hồ sơ cá nhân | VolunteerHub',
            'user' => $user,
            'section' => $this->section,
            'roleLabel' => $this->roleLabel,
            'csrfToken' => Session::generateCsrf(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
        ]);
    }

    public function update(): void
    {
        $this->verifyCsrf();
        $fullName = $this->post('full_name', '');
        $phone = $this->post('phone', '');
        if (!is_string($fullName) || !is_string($phone)) {
            $this->redirect($this->profilePath(), ['error' => 'Thông tin hồ sơ không hợp lệ.']);
            return;
        }

        $fullName = trim($fullName);
        $phone = trim($phone);
        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 200 || mb_strlen($phone) > 20) {
            $this->redirect($this->profilePath(), ['error' => 'Họ tên phải từ 2 đến 200 ký tự và số điện thoại không quá 20 ký tự.']);
            return;
        }

        $this->userModel->updateProfile((int)Session::userId(), [
            'full_name' => $fullName,
            'phone' => $phone,
        ]);
        $_SESSION['user_name'] = $fullName;

        $this->redirect($this->profilePath(), ['success' => 'Cập nhật hồ sơ thành công.']);
    }

    public function changePassword(): void
    {
        $this->verifyCsrf();
        $currentPassword = $this->post('current_password', '');
        $newPassword = $this->post('new_password', '');
        $confirmPassword = $this->post('confirm_password', '');
        if (!is_string($currentPassword) || !is_string($newPassword) || !is_string($confirmPassword)) {
            $this->redirect($this->profilePath(), ['error' => 'Thông tin mật khẩu không hợp lệ.']);
            return;
        }

        $user = $this->userModel->findById((int)Session::userId());
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            $this->redirect($this->profilePath(), ['error' => 'Mật khẩu hiện tại không đúng.']);
            return;
        }

        if (strlen($newPassword) < 8) {
            $this->redirect($this->profilePath(), ['error' => 'Mật khẩu mới phải có ít nhất 8 ký tự.']);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->redirect($this->profilePath(), ['error' => 'Xác nhận mật khẩu mới không khớp.']);
            return;
        }

        $this->userModel->changePassword((int)Session::userId(), $newPassword);
        $this->redirect($this->profilePath(), ['success' => 'Đổi mật khẩu thành công.']);
    }

    private function profilePath(): string
    {
        return '/' . $this->section . '/profile';
    }
}
