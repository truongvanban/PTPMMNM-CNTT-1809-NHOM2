<?php
/**
 * app/Controllers/Coordinator/ReplacementController.php
 * Quản lý và xử lý các yêu cầu báo vắng/thay người từ tình nguyện viên
 */

declare(strict_types=1);

namespace App\Controllers\Coordinator;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ReplacementModel;

class ReplacementController extends Controller
{
    protected string $layout = 'coordinator';
    private ReplacementModel $replacementModel;

    public function __construct()
    {
        $this->replacementModel = new ReplacementModel();
    }

    public function index(): void
    {
        $coordId = (int)Session::userId();
        $page = max(1, (int)$this->get('page', 1));
        $result = $this->replacementModel->getPendingForCoordinatorPage(
            $coordId,
            $page,
            $this->getPageSize(15)
        );
        $requests = $result['data'];

        // Với mỗi request, tìm các ứng viên thay thế phù hợp
        foreach ($requests as &$req) {
            $req['candidates'] = $this->replacementModel->findEligibleReplacements(
                (int)$req['shift_id'],
                (int)$req['requester_id']
            );
        }
        unset($req);

        $this->render('coordinator.replacement.index', [
            'title'     => 'Quản Lý Báo Vắng & Thay Người | VolunteerHub',
            'requests'  => $requests,
            'total'     => $result['total'],
            'page'      => $result['current_page'],
            'lastPage'  => $result['last_page'],
            'perPage'   => $result['per_page'],
            'csrfToken' => Session::generateCsrf(),
            'success'   => Session::getFlash('success'),
            'error'     => Session::getFlash('error'),
        ]);
    }

    public function approve(): void
    {
        $this->verifyCsrf();
        $coordId = (int)Session::userId();

        $requestId = (int)$this->post('request_id', 0);
        $replacementUserId = (int)$this->post('replacement_user_id', 0);
        $note = trim($this->post('coordinator_note', ''));

        $res = $this->replacementModel->approveReplacement(
            $requestId,
            $replacementUserId > 0 ? $replacementUserId : null,
            $coordId,
            $note,
            Session::hasRole('admin')
        );

        if ($res['success']) {
            $this->redirect('/coordinator/replacement', ['success' => $res['message']]);
        } else {
            $this->redirect('/coordinator/replacement', ['error' => $res['message']]);
        }
    }

    public function reject(): void
    {
        $this->verifyCsrf();
        $coordId = (int)Session::userId();

        $requestId = (int)$this->post('request_id', 0);
        $note = trim($this->post('coordinator_note', ''));

        $res = $this->replacementModel->rejectReplacement($requestId, $coordId, $note, Session::hasRole('admin'));
        if ($res['success']) {
            $this->redirect('/coordinator/replacement', ['success' => $res['message']]);
        } else {
            $this->redirect('/coordinator/replacement', ['error' => $res['message']]);
        }
    }
}
