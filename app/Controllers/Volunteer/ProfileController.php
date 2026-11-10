<?php
/**
 * app/Controllers/Volunteer/ProfileController.php
 * Quản lý thông tin cá nhân, kỹ năng và đổi mật khẩu của Tình nguyện viên
 */

declare(strict_types=1);

namespace App\Controllers\Volunteer;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Database;
use App\Models\PointsModel;
use App\Models\SkillModel;
use App\Models\UserModel;

class ProfileController extends Controller
{
    protected string $layout = 'volunteer';
    private UserModel $userModel;
    private SkillModel $skillModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->skillModel = new SkillModel();
    }

    public function index(): void
    {
        $userId = (int)Session::userId();
        $user = $this->userModel->findWithRoles($userId);

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM volunteer_profiles WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch() ?: [];

        $this->render('volunteer.profile', [
            'title'     => 'Hồ Sơ Cá Nhân | VolunteerHub',
            'user'      => $user,
            'profile'   => $profile,
            'pointsSummary' => (new PointsModel())->getSummary($userId),
            'csrfToken' => Session::generateCsrf(),
            'success'   => Session::getFlash('success'),
            'error'     => Session::getFlash('error'),
        ]);
    }

    public function update(): void
    {
        $this->verifyCsrf();
        $userId = (int)Session::userId();

        $fullName   = trim($this->post('full_name', ''));
        $phone      = trim($this->post('phone', ''));
        $studentId  = trim($this->post('student_id', ''));
        $faculty    = trim($this->post('faculty', ''));
        $className  = trim($this->post('class_name', ''));
        $gender     = $this->post('gender', '');
        $dob        = $this->post('date_of_birth', '');
        $address    = trim($this->post('address', ''));
        $bio        = trim($this->post('bio', ''));

        if (empty($fullName)) {
            $this->redirect('/volunteer/profile', ['error' => 'Vui lòng nhập họ và tên.']);
            return;
        }

        // Cập nhật users
        $this->userModel->updateProfile($userId, ['full_name' => $fullName, 'phone' => $phone]);
        $_SESSION['user_name'] = $fullName;

        // Cập nhật volunteer_profiles
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO volunteer_profiles (user_id, student_id, faculty, class_name, gender, date_of_birth, address, bio, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
             student_id = VALUES(student_id), faculty = VALUES(faculty), class_name = VALUES(class_name),
             gender = VALUES(gender), date_of_birth = VALUES(date_of_birth), address = VALUES(address),
             bio = VALUES(bio), updated_at = NOW()"
        );
        $stmt->execute([
            $userId, $studentId, $faculty, $className,
            $gender ?: null, $dob ?: null, $address, $bio
        ]);

        $this->redirect('/volunteer/profile', ['success' => 'Cập nhật hồ sơ thành công!']);
    }

    public function skills(): void
    {
        $userId = (int)Session::userId();
        $allSkills = $this->skillModel->getActive();
        $mySkills = $this->skillModel->getByUserId($userId);

        $mySkillsMap = [];
        foreach ($mySkills as $ms) {
            $mySkillsMap[(int)$ms['id']] = $ms['level'];
        }

        $this->render('volunteer.skills', [
            'title'       => 'Quản Lý Kỹ Năng | VolunteerHub',
            'allSkills'   => $allSkills,
            'mySkillsMap' => $mySkillsMap,
            'csrfToken'   => Session::generateCsrf(),
            'success'     => Session::getFlash('success'),
            'error'       => Session::getFlash('error'),
        ]);
    }

    public function updateSkills(): void
    {
        $this->verifyCsrf();
        $userId = (int)Session::userId();

        $selectedSkills = $this->post('skills', []);
        $levels = $this->post('levels', []);

        $skillsWithLevel = [];
        if (is_array($selectedSkills)) {
            foreach ($selectedSkills as $skillId) {
                $skillId = (int)$skillId;
                $level = $levels[$skillId] ?? 'beginner';
                $skillsWithLevel[$skillId] = $level;
            }
        }

        $this->skillModel->syncVolunteerSkills($userId, $skillsWithLevel);

        $this->redirect('/volunteer/skills', ['success' => 'Cập nhật kỹ năng thành công!']);
    }

    public function changePasswordForm(): void
    {
        $this->render('volunteer.change_password', [
            'title'     => 'Đổi Mật Khẩu | VolunteerHub',
            'csrfToken' => Session::generateCsrf(),
            'success'   => Session::getFlash('success'),
            'error'     => Session::getFlash('error'),
        ]);
    }

    public function changePassword(): void
    {
        $this->verifyCsrf();
        $userId = (int)Session::userId();

        $currentPass = $this->post('current_password', '');
        $newPass     = $this->post('new_password', '');
        $confirmPass = $this->post('confirm_password', '');

        $user = $this->userModel->findById($userId);
        if (!$user || !password_verify($currentPass, $user['password_hash'])) {
            $this->redirect('/volunteer/change-password', ['error' => 'Mật khẩu hiện tại không đúng.']);
            return;
        }

        if (strlen($newPass) < 8) {
            $this->redirect('/volunteer/change-password', ['error' => 'Mật khẩu mới phải có ít nhất 8 ký tự.']);
            return;
        }

        if ($newPass !== $confirmPass) {
            $this->redirect('/volunteer/change-password', ['error' => 'Xác nhận mật khẩu mới không khớp.']);
            return;
        }

        $this->userModel->changePassword($userId, $newPass);
        $this->redirect('/volunteer/change-password', ['success' => 'Đổi mật khẩu thành công!']);
    }
}
