<?php
/**
 * app/Controllers/Admin/LogController.php
 * Nhật ký hệ thống (Audit Logs)
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AuditLogModel;

class LogController extends Controller
{
    protected string $layout = 'admin';
    private AuditLogModel $logModel;

    public function __construct()
    {
        $this->logModel = new AuditLogModel();
    }

    public function index(): void
    {
        $action = trim($this->get('action', ''));
        $entity = trim($this->get('entity', ''));
        $page   = max(1, (int)$this->get('page', 1));
        $perPage = $this->getPageSize(20);

        $result = $this->logModel->search($action, $entity, $page, $perPage);

        $this->render('admin.logs.index', [
            'title'    => 'Nhật Ký Hệ Thống (Audit Logs) | VolunteerHub',
            'logs'     => $result['data'],
            'total'    => $result['total'],
            'page'     => $result['current_page'],
            'lastPage' => $result['last_page'],
            'perPage'  => $result['per_page'],
            'action'   => $action,
            'entity'   => $entity,
        ]);
    }
}
