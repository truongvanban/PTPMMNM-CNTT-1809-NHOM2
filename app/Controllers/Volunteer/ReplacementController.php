<?php
/**
 * app/Controllers/Volunteer/ReplacementController.php
 * Gửi yêu cầu báo vắng và theo dõi trạng thái thay người
 */

declare(strict_types=1);

namespace App\Controllers\Volunteer;

use App\Core\Controller;
use App\Core\Session;
use App\Models\AssignmentModel;
use App\Models\ReplacementModel;

class ReplacementController extends Controller
{
    protected string $layout = 'volunteer';
    private ReplacementModel $replacementModel;
    private AssignmentModel $asgnModel;

    public function __construct()
    {
        $this->replacementModel = new ReplacementModel();
        $this->asgnModel = new AssignmentModel();
    }

    public function index(): void
    {
        $userId = (int)Session::userId();
        $page = max(1, (int)$this->get('page', 1));
        $result = $this->replacementModel->getByUserPage($userId, $page, $this->getPageSize(15));
        $myAssignments = $this->asgnModel->getByUser($userId);

        // Ca trực được chọn sẵn khi vào từ nút "Báo vắng" ở trang Lịch (?assignment_id=...)
        // Chỉ chấp nhận ID thuộc về chính TNV đang đăng nhập để tránh chọn nhầm ca của người khác.
        $requestedAssignmentId = (int)$this->get('assignment_id', 0);
        $selectedAssignmentId = 0;
        if ($requestedAssignmentId > 0) {
            foreach ($myAssignments as $myAssignment) {
                if ((int)($myAssignment['id'] ?? 0) === $requestedAssignmentId) {
                    $selectedAssignmentId = $requestedAssignmentId;
                    break;
                }
            }
        }

        $this->render('volunteer.replacement', [
            'title'         => 'Báo Vắng & Thay Người | VolunteerHub',
            'requests'      => $result['data'],
            'pagination'    => $result,
            'myAssignments' => $myAssignments,
            'selectedAssignmentId' => $selectedAssignmentId,
            'csrfToken'     => Session::generateCsrf(),
            'success'       => Session::getFlash('success'),
            'error'         => Session::getFlash('error'),
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $userId = (int)Session::userId();
        $asgnId = (int)$this->post('assignment_id', 0);
        $reason = trim($this->post('reason', ''));

        if (empty($reason)) {
            $this->redirect('/volunteer/replacement', ['error' => 'Vui lòng cung cấp lý do báo vắng.']);
            return;
        }

        $res = $this->replacementModel->createRequest($asgnId, $userId, $reason);
        if ($res['success']) {
            $this->redirect('/volunteer/replacement', ['success' => $res['message']]);
        } else {
            $this->redirect('/volunteer/replacement', ['error' => $res['message']]);
        }
    }
}
