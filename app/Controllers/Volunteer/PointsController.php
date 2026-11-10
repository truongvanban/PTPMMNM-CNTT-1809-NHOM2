<?php

declare(strict_types=1);

namespace App\Controllers\Volunteer;

use App\Core\Controller;
use App\Core\Session;
use App\Models\PointsModel;

class PointsController extends Controller
{
    protected string $layout = 'volunteer';

    public function index(): void
    {
        $points = new PointsModel();
        $period = $this->get('period', 'monthly');
        if (!is_string($period) || !in_array($period, ['weekly', 'monthly', 'semester', 'all'], true)) {
            $period = 'monthly';
        }
        $terms = $points->getTerms();
        $termId = max(0, (int)$this->get('term_id', 0));
        if ($period === 'semester' && $termId === 0 && !empty($terms)) {
            $termId = (int)$terms[0]['id'];
        }

        $this->render('volunteer.points', [
            'title' => 'Điểm cống hiến & Thi đua | VolunteerHub',
            'summary' => $points->getSummary((int)Session::userId()),
            'transactions' => $points->getRecentTransactions((int)Session::userId(), 50),
            'leaderboard' => $points->getLeaderboard($period, $termId),
            'period' => $period,
            'terms' => $terms,
            'termId' => $termId,
            'certificateAvailable' => $points->getCertificateData((int)Session::userId()) !== null,
        ]);
    }

    public function certificate(): void
    {
        $certificate = (new PointsModel())->getCertificateData((int)Session::userId());
        if ($certificate === null) {
            $this->redirect('/volunteer/points', ['error' => 'Bạn chưa đạt mốc điểm để nhận giấy chứng nhận.']);
            return;
        }
        $this->render('volunteer.certificate', [
            'certificate' => $certificate,
        ], 'none');
    }
}
