<?php
/**
 * app/Controllers/Coordinator/ReportController.php
 * Báo cáo ca đủ người, thiếu người, thống kê khối lượng công việc và xuất CSV
 */

declare(strict_types=1);

namespace App\Controllers\Coordinator;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ActivityModel;
use App\Models\Database;

class ReportController extends Controller
{
    protected string $layout = 'coordinator';
    private ActivityModel $activityModel;

    public function __construct()
    {
        $this->activityModel = new ActivityModel();
    }

    public function index(): void
    {
        $coordId = (int)Session::userId();
        $db = Database::getInstance();
        $perPage = $this->getPageSize();
        $limitSql = static function (int $page) use ($perPage): string {
            if ($perPage === 0) {
                return '';
            }

            $offset = ($page - 1) * $perPage;
            return ' LIMIT ' . $perPage . ' OFFSET ' . $offset;
        };

        // 1. Danh sách ca và tình trạng đủ/thiếu người
        $shiftCountStmt = $db->prepare(
            "SELECT COUNT(*)
             FROM shifts s
             JOIN activities act ON s.activity_id = act.id
             WHERE act.coordinator_id = ?"
        );
        $shiftCountStmt->execute([$coordId]);
        $shiftTotal = (int)$shiftCountStmt->fetchColumn();
        $shiftLastPage = $perPage === 0 ? 1 : max(1, (int)ceil($shiftTotal / $perPage));
        $shiftPage = min(max(1, (int)$this->get('shifts_page', 1)), $shiftLastPage);

        $shiftsStmt = $db->prepare(
            "SELECT s.*, act.title as activity_title,
                    (SELECT COUNT(*) FROM assignments asgn WHERE asgn.shift_id = s.id AND asgn.status NOT IN ('removed','replaced')) as assigned_count,
                    (SELECT COUNT(*) FROM attendance att WHERE att.shift_id = s.id AND att.status = 'confirmed') as confirmed_count,
                    (SELECT COUNT(*) FROM attendance att2 WHERE att2.shift_id = s.id AND att2.status = 'absent') as absent_count
             FROM shifts s
             JOIN activities act ON s.activity_id = act.id
             WHERE act.coordinator_id = ?
             ORDER BY s.date ASC, s.time_start ASC" . $limitSql($shiftPage)
        );
        $shiftsStmt->execute([$coordId]);
        $shiftsReport = $shiftsStmt->fetchAll();

        // 2. Thống kê khối lượng ca theo từng tình nguyện viên
        $volunteerCountStmt = $db->prepare(
            "SELECT COUNT(*)
             FROM (
                 SELECT u.id
                 FROM users u
                 INNER JOIN user_roles ur ON u.id = ur.user_id AND ur.role_id = 1
                 JOIN assignments asgn ON asgn.user_id = u.id AND asgn.status NOT IN ('removed','replaced')
                 JOIN shifts s ON asgn.shift_id = s.id
                 JOIN activities act ON s.activity_id = act.id
                 WHERE act.coordinator_id = ?
                 GROUP BY u.id
             ) volunteer_totals"
        );
        $volunteerCountStmt->execute([$coordId]);
        $volunteerTotal = (int)$volunteerCountStmt->fetchColumn();
        $volunteerLastPage = $perPage === 0 ? 1 : max(1, (int)ceil($volunteerTotal / $perPage));
        $volunteerPage = min(max(1, (int)$this->get('volunteers_page', 1)), $volunteerLastPage);

        $volWorkloadStmt = $db->prepare(
            "SELECT u.id, u.full_name, u.email, u.phone, vp.student_id, vp.faculty,
                    COUNT(asgn.id) as total_assigned,
                    SUM(CASE WHEN att.status = 'confirmed' THEN 1 ELSE 0 END) as total_confirmed,
                    SUM(CASE WHEN att.status = 'absent' THEN 1 ELSE 0 END) as total_absent
             FROM users u
             INNER JOIN user_roles ur ON u.id = ur.user_id AND ur.role_id = 1
             LEFT JOIN volunteer_profiles vp ON u.id = vp.user_id
             JOIN assignments asgn ON asgn.user_id = u.id AND asgn.status NOT IN ('removed','replaced')
             JOIN shifts s ON asgn.shift_id = s.id
             JOIN activities act ON s.activity_id = act.id
             LEFT JOIN attendance att ON att.assignment_id = asgn.id
             WHERE act.coordinator_id = ?
             GROUP BY u.id
             ORDER BY total_assigned DESC, u.full_name ASC" . $limitSql($volunteerPage)
        );
        $volWorkloadStmt->execute([$coordId]);
        $volunteerWorkload = $volWorkloadStmt->fetchAll();

        $this->render('coordinator.reports.index', [
            'title'             => 'Báo Cáo & Thống Kê Ca Trực | VolunteerHub',
            'shiftsReport'      => $shiftsReport,
            'volunteerWorkload' => $volunteerWorkload,
            'shiftTotal' => $shiftTotal,
            'shiftPage' => $shiftPage,
            'shiftLastPage' => $shiftLastPage,
            'volunteerTotal' => $volunteerTotal,
            'volunteerPage' => $volunteerPage,
            'volunteerLastPage' => $volunteerLastPage,
            'perPage' => $perPage,
        ]);
    }

    public function exportCsv(): void
    {
        $coordId = (int)Session::userId();
        $db = Database::getInstance();

        $stmt = $db->prepare(
            "SELECT s.name as shift_name, s.date as shift_date, s.time_start, s.time_end,
                    act.title as activity_title,
                    u.full_name, u.email, u.phone, vp.student_id,
                    asgn.status as assignment_status,
                    COALESCE(att.status, 'pending') as attendance_status
             FROM assignments asgn
             JOIN shifts s ON asgn.shift_id = s.id
             JOIN activities act ON s.activity_id = act.id
             JOIN users u ON asgn.user_id = u.id
             LEFT JOIN volunteer_profiles vp ON u.id = vp.user_id
             LEFT JOIN attendance att ON att.assignment_id = asgn.id
             WHERE act.coordinator_id = ? AND asgn.status NOT IN ('removed','replaced')
             ORDER BY s.date ASC, s.time_start ASC, u.full_name ASC"
        );
        $stmt->execute([$coordId]);
        $rows = $stmt->fetchAll();

        // Xuất file CSV UTF-8 với BOM để Excel tiếng Việt không bị lỗi font
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="volunteerhub_phan_cong_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM
        fputs($out, "\xEF\xBB\xBF");

        // Header
        fputcsv($out, [
            'Hoạt động', 'Tên ca trực', 'Ngày', 'Bắt đầu', 'Kết thúc',
            'Họ và tên TNV', 'Email', 'Điện thoại', 'MSSV',
            'Trạng thái phân công', 'Xác nhận tham gia'
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['activity_title'],
                $r['shift_name'],
                $r['shift_date'],
                substr($r['time_start'], 0, 5),
                substr($r['time_end'], 0, 5),
                $r['full_name'],
                $r['email'],
                $r['phone'],
                $r['student_id'] ?? '',
                $r['assignment_status'],
                $r['attendance_status'],
            ]);
        }

        fclose($out);
        exit;
    }

    public function history(): void
    {
        $coordId = (int)Session::userId();
        $db = Database::getInstance();

        $countStmt = $db->prepare(
            "SELECT COUNT(*)
             FROM assignment_history ah
             JOIN assignments asgn ON ah.assignment_id = asgn.id
             JOIN shifts s ON asgn.shift_id = s.id
             JOIN activities act ON s.activity_id = act.id
             WHERE act.coordinator_id = ?"
        );
        $countStmt->execute([$coordId]);
        $total = (int)$countStmt->fetchColumn();
        $perPage = $this->getPageSize();
        $lastPage = $perPage === 0 ? 1 : max(1, (int)ceil($total / $perPage));
        $page = max(1, (int)$this->get('page', 1));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;
        $limitSql = $perPage === 0 ? '' : ' LIMIT ' . $perPage . ' OFFSET ' . $offset;

        $stmt = $db->prepare(
            "SELECT ah.*, u.full_name as changed_by_name,
                    s.name as shift_name, s.date as shift_date,
                    asgn_u.full_name as volunteer_name
             FROM assignment_history ah
             JOIN users u ON ah.changed_by = u.id
             JOIN assignments asgn ON ah.assignment_id = asgn.id
             JOIN shifts s ON asgn.shift_id = s.id
             JOIN activities act ON s.activity_id = act.id
             JOIN users asgn_u ON asgn.user_id = asgn_u.id
             WHERE act.coordinator_id = ?
             ORDER BY ah.created_at DESC" . $limitSql
        );
        $stmt->execute([$coordId]);
        $history = $stmt->fetchAll();

        $this->render('coordinator.reports.history', [
            'title'   => 'Lịch Sử Thay Đổi Phân Công | VolunteerHub',
            'history' => $history,
            'total' => $total,
            'page' => $page,
            'lastPage' => $lastPage,
            'perPage' => $perPage,
        ]);
    }
}
