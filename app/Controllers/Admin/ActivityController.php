<?php
/**
 * app/Controllers/Admin/ActivityController.php
 * Quản lý tất cả hoạt động và ca trực trên toàn hệ thống
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\ActivityModel;

class ActivityController extends Controller
{
    protected string $layout = 'admin';
    private ActivityModel $activityModel;

    public function __construct()
    {
        $this->activityModel = new ActivityModel();
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
}
