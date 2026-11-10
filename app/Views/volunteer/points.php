<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$periodLabels = [
    'weekly' => 'Tuần này',
    'monthly' => 'Tháng này',
    'semester' => 'Học kỳ',
    'all' => 'Toàn thời gian',
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Điểm cống hiến & Thi đua</h1>
        <p class="text-muted mb-0">Điểm chỉ được ghi nhận khi điều phối viên xác nhận kết quả ca trực.</p>
    </div>
    <?php if ($certificateAvailable): ?>
        <a class="btn btn-success" href="<?= $baseUrl ?>/volunteer/points/certificate" target="_blank">
            <i class="bi bi-award me-1"></i> Giấy chứng nhận
        </a>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-5">
            <div class="text-muted small">Tổng điểm</div>
            <div class="display-5 fw-bold text-success"><?= number_format($summary['points']) ?></div>
            <span class="badge text-bg-success"><?= htmlspecialchars($summary['tier_label']) ?></span>
        </div>
        <div class="col-lg-7">
            <?php if ($summary['next_tier_points'] !== null): ?>
                <div class="d-flex justify-content-between small mb-2">
                    <span>Tiến độ đến hạng <?= htmlspecialchars($summary['next_tier_label']) ?></span>
                    <strong>Còn <?= number_format($summary['points_to_next_tier']) ?> điểm</strong>
                </div>
                <div class="progress" role="progressbar" aria-valuenow="<?= $summary['progress'] ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-success" style="width: <?= $summary['progress'] ?>%"></div>
                </div>
                <small class="text-muted mt-2 d-block">
                    <?= number_format($summary['points']) ?> / <?= number_format($summary['next_tier_points']) ?> điểm
                </small>
            <?php else: ?>
                <div class="fw-semibold text-success">Bạn đã đạt hạng cao nhất.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <h2 class="h5 fw-bold mb-3">Huy hiệu</h2>
    <div class="row g-3">
        <?php foreach ($summary['badges'] as $badge): ?>
            <div class="col-6 col-md-3">
                <div class="h-100 text-center rounded-3 border p-3 <?= $badge['unlocked'] ? 'border-success bg-success-subtle shadow-sm points-badge-unlocked' : 'bg-light text-muted points-badge-locked' ?>">
                    <div class="position-relative d-inline-block">
                        <i class="bi <?= htmlspecialchars($badge['icon']) ?> fs-2 <?= $badge['unlocked'] ? 'text-success' : '' ?>"></i>
                        <?php if (!$badge['unlocked']): ?>
                            <i class="bi bi-lock-fill position-absolute top-0 start-100 translate-middle fs-6 text-secondary" aria-hidden="true"></i>
                        <?php endif; ?>
                    </div>
                    <div class="fw-semibold mt-2"><?= htmlspecialchars($badge['name']) ?></div>
                    <small class="<?= $badge['unlocked'] ? 'text-success fw-semibold' : 'text-secondary' ?>">
                        <?= $badge['unlocked'] ? 'Đã đạt' : 'Chưa đạt · ' . number_format((int)$badge['required_points']) . ' điểm' ?>
                    </small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h2 class="h5 fw-bold mb-3">Lịch sử điểm</h2>
            <?php if (empty($transactions)): ?>
                <div class="text-center rounded-3 bg-light p-4">
                    <i class="bi bi-journal-plus fs-2 text-success" aria-hidden="true"></i>
                    <p class="text-muted mt-2 mb-3">Chưa có giao dịch điểm. Đăng ký ca trực để bắt đầu tích lũy điểm cống hiến nhé!</p>
                    <a class="btn btn-outline-success btn-sm" href="<?= $baseUrl ?>/volunteer/shifts">
                        <i class="bi bi-plus-circle me-1"></i> Đăng ký ca trực
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Ngày</th><th>Mô tả</th><th class="text-end">Điểm</th></tr></thead>
                        <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td class="text-nowrap"><?= date('d/m/Y', strtotime($transaction['created_at'])) ?></td>
                                <td><?= htmlspecialchars($transaction['description']) ?></td>
                                <td class="text-end fw-bold <?= (int)$transaction['points'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= (int)$transaction['points'] > 0 ? '+' : '' ?><?= (int)$transaction['points'] ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 fw-bold mb-0">Bảng xếp hạng</h2>
                <form method="GET" action="<?= $baseUrl ?>/volunteer/points" class="d-flex gap-2">
                    <select class="form-select form-select-sm" name="period" aria-label="Kỳ xếp hạng" onchange="this.form.submit()">
                        <?php foreach ($periodLabels as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $period === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($period === 'semester' && !empty($terms)): ?>
                        <select class="form-select form-select-sm" name="term_id" aria-label="Học kỳ" onchange="this.form.submit()">
                            <?php foreach ($terms as $term): ?>
                                <option value="<?= (int)$term['id'] ?>" <?= $termId === (int)$term['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($term['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </form>
            </div>
            <?php if ($period === 'semester' && empty($terms)): ?>
                <p class="text-muted mb-0">Chưa có kỳ học được cấu hình.</p>
            <?php elseif (empty($leaderboard)): ?>
                <div class="text-center rounded-3 bg-light p-4">
                    <i class="bi bi-trophy fs-2 text-warning" aria-hidden="true"></i>
                    <p class="text-muted mt-2 mb-3">Chưa có dữ liệu xếp hạng. Hãy tham gia một ca trực để góp mặt trên bảng thi đua!</p>
                    <a class="btn btn-outline-success btn-sm" href="<?= $baseUrl ?>/volunteer/shifts">
                        <i class="bi bi-compass me-1"></i> Khám phá ca trực
                    </a>
                </div>
            <?php else: ?>
                <ol class="list-group list-group-numbered list-group-flush">
                    <?php foreach ($leaderboard as $leader): ?>
                        <li class="list-group-item d-flex justify-content-between gap-2 px-0">
                            <span><?= htmlspecialchars($leader['full_name']) ?></span>
                            <strong><?= number_format((int)$leader['points']) ?> điểm</strong>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
        <style>
            .points-badge-unlocked {
                box-shadow: 0 0 14px rgba(25, 135, 84, 0.18) !important;
            }

            .points-badge-locked {
                opacity: 0.55;
                filter: grayscale(0.85);
            }
        </style>
    </div>
</div>
