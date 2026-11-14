<?php
/**
 * app/Controllers/Admin/ReportController.php
 * Báo cáo tổng quan và Báo cáo Hiệu quả Thuật toán Phân công
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Database;
use App\Services\AssignmentService;

class ReportController extends Controller
{
    protected string $layout = 'admin';
    private AssignmentService $assignmentService;

    public function __construct()
    {
        $this->assignmentService = new AssignmentService();
    }

    public function index(): void
    {
        $this->redirect('/admin/reports/algorithm');
    }

    public function algorithmComparison(): void
    {
        $db = Database::getInstance();
        $activities = $db->query("SELECT id, title FROM activities ORDER BY id ASC")->fetchAll();

        // Mặc định chọn hoạt động đầu tiên hoặc theo query string
        $selectedActId = (int)$this->get('activity_id', $activities[0]['id'] ?? 1);

        $report = null;
        if ($selectedActId > 0) {
            $report = $this->assignmentService->getAlgorithmReport($selectedActId);
        }

        $this->render('admin.reports.algorithm', [
            'title'         => 'Hiệu Quả Thuật Toán Phân Công | VolunteerHub',
            'activities'    => $activities,
            'selectedActId' => $selectedActId,
            'report'        => $report,
        ]);
    }
}
