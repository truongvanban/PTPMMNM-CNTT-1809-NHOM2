<?php
/**
 * app/Controllers/Volunteer/NotificationController.php
 * Xem và đánh dấu đã đọc thông báo
 */

declare(strict_types=1);

namespace App\Controllers\Volunteer;

use App\Core\Controller;
use App\Core\Session;
use App\Models\NotificationModel;

class NotificationController extends Controller
{
    protected string $layout = 'volunteer';
    private NotificationModel $notifModel;

    public function __construct()
    {
        $this->notifModel = new NotificationModel();
    }

    public function index(): void
    {
        $userId = (int)Session::userId();
        $page = max(1, (int)$this->get('page', 1));
        $result = $this->notifModel->getByUserPage($userId, $page, $this->getPageSize(15));

        $this->render('volunteer.notifications', [
            'title'         => 'Thông Báo Hệ Thống | VolunteerHub',
            'notifications' => $result['data'],
            'pagination'    => $result,
            'csrfToken'     => Session::generateCsrf(),
            'success'       => Session::getFlash('success'),
            'error'         => Session::getFlash('error'),
        ]);
    }

    public function markRead(): void
    {
        $this->verifyCsrf();
        $userId = (int)Session::userId();
        $notifId = (int)$this->post('id', 0);

        if ($notifId > 0) {
            $this->notifModel->markAsRead($notifId, $userId);
        } else {
            $this->notifModel->markAllAsRead($userId);
        }

        $page = max(1, (int)$this->post('page', 1));
        $this->redirect('/volunteer/notifications?' . http_build_query(['page' => $page]), ['success' => 'Đã cập nhật trạng thái thông báo.']);
    }

    public function view(): void
    {
        $this->verifyCsrf();
        $userId = (int)Session::userId();
        $notificationId = filter_var($this->post('id', 0), FILTER_VALIDATE_INT);
        $notification = $notificationId !== false && $notificationId > 0
            ? $this->notifModel->getByIdForUser($notificationId, $userId)
            : null;

        if ($notification === null) {
            $this->redirect('/volunteer/notifications', ['error' => 'Không tìm thấy thông báo.']);
        }

        if (!$this->notifModel->markAsRead((int)$notification['id'], $userId)) {
            $this->redirect('/volunteer/notifications', ['error' => 'Không thể cập nhật trạng thái thông báo.']);
        }

        $link = $notification['link'] ?? '';
        if (is_string($link) && str_starts_with($link, '/') && !str_starts_with($link, '//') && !str_contains($link, '\\')) {
            $this->redirect($link);
        }

        $this->redirect('/volunteer/notifications', ['error' => 'Thông báo không có liên kết xem hợp lệ.']);
    }
}
