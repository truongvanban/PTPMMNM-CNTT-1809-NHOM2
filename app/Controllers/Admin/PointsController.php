<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\PointsModel;

class PointsController extends Controller
{
    protected string $layout = 'admin';
    private PointsModel $pointsModel;

    public function __construct()
    {
        $this->pointsModel = new PointsModel();
    }

    public function index(): void
    {
        $this->render('admin.points.index', [
            'title' => 'Cấu hình điểm & Thi đua | VolunteerHub',
            'settings' => $this->pointsModel->getSettings(),
            'terms' => $this->pointsModel->getTerms(),
            'activities' => $this->pointsModel->getActivityMultipliers(),
            'csrfToken' => Session::generateCsrf(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
        ]);
    }

    public function saveSettings(): void
    {
        $this->verifyCsrf();
        $limits = [
            'points_per_hour' => [0, 1000],
            'difficult_shift_multiplier' => [0.1, 10],
            'skilled_shift_multiplier' => [0.1, 10],
            'on_time_bonus' => [0, 1000],
            'late_cancel_penalty' => [0, 1000],
            'no_show_penalty' => [0, 1000],
            'daily_points_cap' => [0, 5000],
            'certificate_threshold' => [0, 1000000],
        ];
        $integerSettings = ['on_time_bonus', 'late_cancel_penalty', 'no_show_penalty', 'daily_points_cap', 'certificate_threshold'];
        $settings = [];
        foreach ($limits as $key => [$minimum, $maximum]) {
            $raw = $this->post($key, '');
            if (
                !is_numeric($raw)
                || (float)$raw < $minimum
                || (float)$raw > $maximum
                || (in_array($key, $integerSettings, true) && (float)$raw !== floor((float)$raw))
            ) {
                $this->redirect('/admin/points', ['error' => 'Giá trị cấu hình không hợp lệ: ' . $key]);
                return;
            }
            $settings[$key] = (float)$raw;
        }
        $this->pointsModel->saveSettings($settings);
        $this->redirect('/admin/points', ['success' => 'Đã lưu cấu hình điểm cống hiến.']);
    }

    public function saveTerm(): void
    {
        $this->verifyCsrf();
        $rawTermId = $this->post('term_id', 0);
        $termId = is_scalar($rawTermId) ? (int)$rawTermId : 0;
        $rawName = $this->post('name', '');
        $rawStartsOn = $this->post('starts_on', '');
        $rawEndsOn = $this->post('ends_on', '');
        $name = is_string($rawName) ? trim($rawName) : '';
        $startsOn = is_string($rawStartsOn) ? $rawStartsOn : '';
        $endsOn = is_string($rawEndsOn) ? $rawEndsOn : '';
        $validDate = static fn(string $date): bool =>
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            && \DateTimeImmutable::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') === $date;

        if ($name === '' || strlen($name) > 100 || !$validDate($startsOn) || !$validDate($endsOn) || $endsOn < $startsOn) {
            $this->redirect('/admin/points', ['error' => 'Tên kỳ học hoặc khoảng ngày không hợp lệ.']);
            return;
        }
        $result = $this->pointsModel->saveTerm(
            $termId > 0 ? $termId : null,
            $name,
            $startsOn,
            $endsOn,
            (int)Session::userId()
        );
        $this->redirect('/admin/points', [$result['success'] ? 'success' : 'error' => $result['message']]);
    }

    public function deleteTerm(): void
    {
        $this->verifyCsrf();
        $rawTermId = $this->post('term_id', 0);
        $termId = is_scalar($rawTermId) ? (int)$rawTermId : 0;
        if ($termId <= 0) {
            $this->redirect('/admin/points', ['error' => 'Kỳ học không hợp lệ.']);
            return;
        }
        $this->pointsModel->deleteTerm($termId);
        $this->redirect('/admin/points', ['success' => 'Đã xóa kỳ học.']);
    }

    public function saveActivityMultiplier(): void
    {
        $this->verifyCsrf();
        $rawActivityId = $this->post('activity_id', 0);
        $activityId = is_scalar($rawActivityId) ? (int)$rawActivityId : 0;
        $rawMultiplier = $this->post('points_multiplier', '');
        if ($activityId <= 0 || !is_numeric($rawMultiplier) || (float)$rawMultiplier < 0.1 || (float)$rawMultiplier > 10) {
            $this->redirect('/admin/points', ['error' => 'Hệ số hoạt động phải nằm trong khoảng 0,1 đến 10.']);
            return;
        }
        if (!$this->pointsModel->saveActivityMultiplier($activityId, (float)$rawMultiplier)) {
            $this->redirect('/admin/points', ['error' => 'Không tìm thấy hoạt động hoặc hệ số không thay đổi.']);
            return;
        }
        $this->redirect('/admin/points', ['success' => 'Đã cập nhật hệ số điểm hoạt động.']);
    }
}
