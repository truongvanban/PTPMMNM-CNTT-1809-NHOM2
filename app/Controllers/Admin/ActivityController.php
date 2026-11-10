<?php
/**
 * app/Controllers/Admin/ActivityController.php
 * Quản lý tất cả hoạt động và ca trực trên toàn hệ thống
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\ActivityModel;
use App\Models\ShiftModel;

class ActivityController extends Controller
{
    protected string $layout = 'admin';
    private ActivityModel $activityModel;
    private ShiftModel $shiftModel;

    public function __construct()
    {
        $this->activityModel = new ActivityModel();
        $this->shiftModel = new ShiftModel();
    }

    public function index(): void
    {
        $search = trim($this->get('search', ''));
        $status = trim($this->get('status', ''));
        $page   = max(1, (int)$this->get('page', 1));
        $perPage = $this->getPageSize(15);

        $result = $this->activityModel->getAllForAdmin($search, $status, $page, $perPage);

        $this->render('admin.activities.index', [
            'title'      => 'Quản Lý Toàn Bộ Hoạt Động | VolunteerHub',
            'activities' => $result['data'],
            'total'      => $result['total'],
            'page'       => $result['current_page'],
            'lastPage'   => $result['last_page'],
            'perPage'    => $result['per_page'],
            'search'     => $search,
            'status'     => $status,
        ]);
    }

    public function shifts(string $activityId): void
    {
        $id = (int)$activityId;
        $activity = $this->activityModel->findWithCoordinator($id);
        if (!$activity) {
            $this->redirect('/404');
            return;
        }

        $page = max(1, (int)$this->get('page', 1));
        $result = $this->shiftModel->getByActivityPage($id, $page, $this->getPageSize(15));

        $this->render('admin.activities.shifts', [
            'title' => 'Ca Trực: ' . $activity['title'] . ' | Quản Trị VolunteerHub',
            'activity' => $activity,
            'shifts' => $result['data'],
            'total' => $result['total'],
            'page' => $result['current_page'],
            'lastPage' => $result['last_page'],
            'perPage' => $result['per_page'],
        ]);
    }
}
