<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$periodLabels = [
    'weekly'  => 'Tuần này',
    'monthly' => 'Tháng này',
    'semester'=> 'Học kỳ',
    'all'     => 'Toàn thời gian',
];
$userPoints   = (int)($summary['points'] ?? 0);
$userTier     = htmlspecialchars($summary['tier_label'] ?? 'Chưa xếp hạng', ENT_QUOTES, 'UTF-8');
$userTierIcon = $summary['tier_icon'] ?? 'bi-award';
$nextTier     = $summary['next_tier_label'] ?? null;
$pointsToNext = (int)($summary['points_to_next_tier'] ?? 0);
$progressPct  = (int)($summary['progress'] ?? 0);
$totalPoints  = number_format($userPoints);
?>
<div class="volunteer-points-page">

    <!-- ===== HERO HEADER ===== -->
    <header class="points-hero">
        <div class="points-hero-badge">
            <i class="bi <?= $userTierIcon ?>"></i>
            <span class="points-tier-label"><?= $userTier ?></span>
        </div>
        <div class="points-hero-content">
            <h1 class="points-hero-title">Điểm cống hiến & Thi đua</h1>
            <p class="points-hero-subtitle">Điểm chỉ được ghi nhận khi điều phối viên xác nhận kết quả ca trực.</p>
        </div>
        <div class="points-hero-actions">
            <?php if ($certificateAvailable): ?>
                <a class="points-btn-certificate" href="<?= $baseUrl ?>/volunteer/points/certificate" target="_blank">
                    <i class="bi bi-award me-1"></i> Giấy chứng nhận
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- ===== SUMMARY CARDS ===== -->
    <div class="points-summary-grid row g-3">
        <!-- Total Points -->
        <div class="col-md-6 col-lg-3">
            <div class="points-card points-card-points">
                <div class="points-card-header d-flex justify-content-between align-items-start">
                    <div class="points-summary-card-heading">
                        <span class="points-card-label">Tổng điểm</span>
                        <span class="points-card-icon"><i class="bi bi-star-fill"></i></span>
                    </div>
                </div>
                <div class="points-card-value"><?= $totalPoints ?></div>
                <div class="points-card-sub">điểm cống hiến đã tích lũy</div>
            </div>
        </div>
        <!-- Tier / Hạng -->
        <div class="col-md-6 col-lg-3">
            <div class="points-card points-card-tier">
                <div class="points-card-header d-flex justify-content-between align-items-start">
                    <div class="points-summary-card-heading">
                        <span class="points-card-label">Hạng hiện tại</span>
                        <span class="points-card-icon"><i class="bi bi-shield-fill"></i></span>
                    </div>
                </div>
                <div class="points-card-value fw-bold"><?= $userTier ?></div>
                <div class="points-card-sub">Hạng cống hiến của bạn</div>
            </div>
        </div>
        <!-- Next Tier Progress -->
        <div class="col-md-6 col-lg-3">
            <div class="points-card points-card-progress">
                <div class="points-card-header d-flex justify-content-between align-items-start">
                    <div class="points-summary-card-heading">
                        <span class="points-card-label">Tiến độ hạng tiếp theo</span>
                        <span class="points-card-icon"><i class="bi bi-arrow-bar-up"></i></span>
                    </div>
                </div>
                <div class="points-card-value fw-bold"><?= $progressPct ?>%</div>
                <div class="points-card-sub"><?= $nextTier ? 'Đến ' . htmlspecialchars($nextTier) : 'Đã đạt hạng cao nhất' ?></div>
            </div>
        </div>
        <!-- Badges Count -->
        <div class="col-md-6 col-lg-3">
            <div class="points-card points-card-badges">
                <div class="points-card-header d-flex justify-content-between align-items-start">
                    <div class="points-summary-card-heading">
                        <span class="points-card-label">Huy hiệu</span>
                        <span class="points-card-icon"><i class="bi bi-patch-check"></i></span>
                    </div>
                </div>
                <div class="points-card-value fw-bold"><?= count($summary['badges'] ?? []) ?></div>
                <div class="points-card-sub">huy hiệu đã sưu tập</div>
            </div>
        </div>
    </div>

    <!-- ===== MAIN CONTENT GRID ===== -->
    <div class="row g-4">
        <!-- Left: Progress + Badges -->
        <div class="col-lg-8">
            <!-- Tier Progress Card -->
            <div class="points-card points-card-detail mb-4">
                <div class="points-card-header">
                    <h3 class="points-card-title">
                        <i class="bi bi-gauge me-2 text-success"></i> Tiến độ hạng cống hiến
                    </h3>
                </div>
                <div class="points-card-body">
                    <?php if ($nextTier): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="points-progress-label">Đến hạng <strong><?= htmlspecialchars($nextTier) ?></strong></span>
                            <span class="points-progress-value fw-bold"><?= $progressPct ?>%</span>
                        </div>
                        <div class="points-progress-bar" style="height: 10px; border-radius: 5px; background: #e2e8f0; overflow: hidden;">
                            <div class="points-progress-fill" style="width: <?= $progressPct ?>%; height: 100%; background: linear-gradient(90deg, #10b981 0%, #059669 100%); border-radius: 5px; transition: width 0.8s ease;"></div>
                        </div>
                        <div class="points-progress-meta mt-2 d-flex justify-content-between small">
                            <span class="text-muted"><?= $totalPoints ?> / <?= number_format((int)($summary['next_tier_points'] ?? 0)) ?> điểm</span>
                            <span class="text-success fw-semibold">Còn <?= number_format($pointsToNext) ?> điểm</span>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-trophy-fill text-warning fs-1 mb-2"></i>
                            <div class="fw-bold text-success">Bạn đã đạt hạng cao nhất.</div>
                            <p class="text-muted small mb-0">Chúc mừng! Bạn là cùng loại cống hiến xuất sắc.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Badges Grid -->
            <div class="points-card points-card-detail mb-4">
                <div class="points-card-header">
                    <h3 class="points-card-title">
                        <i class="bi bi-patch-check me-2 text-success"></i> Huy hiệu cống hiến
                    </h3>
                    <span class="points-badge-count"><?= count($summary['badges'] ?? []) ?> huy hiệu</span>
                </div>
                <div class="points-card-body">
                    <div class="points-badges-grid">
                        <?php foreach ($summary['badges'] as $badge): ?>
                            <?php $unlocked = !empty($badge['unlocked']); ?>
                            <div class="points-badge-item <?= $unlocked ? 'points-badge-unlocked' : 'points-badge-locked' ?>">
                                <div class="points-badge-icon">
                                    <i class="bi <?= htmlspecialchars($badge['icon'] ?? 'bi-award') ?> <?= $unlocked ? 'text-success' : 'text-muted' ?>"></i>
                                    <?php if (!$unlocked): ?>
                                        <span class="points-badge-lock">🔒</span>
                                    <?php endif; ?>
                                </div>
                                <div class="points-badge-name"><?= htmlspecialchars($badge['name']) ?></div>
                                <div class="points-badge-status">
                                    <?php if ($unlocked): ?>
                                        <span class="points-badge-unlocked-tag">Đã đạt</span>
                                    <?php else: ?>
                                        <span class="points-badge-locked-tag">Cần <?= number_format((int)$badge['required_points']) ?> điểm</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Leaderboard -->
        <div class="col-lg-4">
            <div class="points-card points-card-detail h-100">
                <div class="points-card-header d-flex justify-content-between align-items-center">
                    <h3 class="points-card-title mb-0">
                        <i class="bi bi-trophy me-2 text-warning"></i> Bảng xếp hạng
                    </h3>
                    <form method="GET" action="<?= $baseUrl ?>/volunteer/points" class="points-period-filter d-flex gap-2">
                        <select class="points-period-select" name="period" aria-label="Kỳ xếp hạng" onchange="this.form.submit()">
                            <?php foreach ($periodLabels as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $period === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($period === 'semester' && !empty($terms)): ?>
                            <select class="points-period-select" name="term_id" aria-label="Học kỳ" onchange="this.form.submit()">
                                <?php foreach ($terms as $term): ?>
                                    <option value="<?= (int)$term['id'] ?>" <?= $termId === (int)$term['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($term['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="points-card-body">
                    <?php if ($period === 'semester' && empty($terms)): ?>
                        <div class="points-empty-state">
                            <div class="points-empty-icon"><i class="bi bi-calendar-x text-muted"></i></div>
                            <p class="points-empty-title">Chưa có kỳ học được cấu hình.</p>
                        </div>
                    <?php elseif (empty($leaderboard)): ?>
                        <div class="points-empty-state">
                            <div class="points-empty-icon"><i class="bi bi-trophy text-warning"></i></div>
                            <p class="points-empty-title">Chưa có dữ liệu xếp hạng.</p>
                            <p class="points-empty-desc small">Hãy tham gia một ca trực để góp mặt trên bảng thi đua!</p>
                            <a class="points-empty-btn" href="<?= $baseUrl ?>/volunteer/shifts">
                                <i class="bi bi-compass me-1"></i> Khám phá ca trực
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="points-leaderboard-list">
                            <?php $userRank = null; foreach ($leaderboard as $index => $leader):
                                if (!empty($leader['user_id']) && (int)$leader['user_id'] === (int)Session::userId()) { $userRank = $index + 1; }
                            ?>
                                <?php $isTop3 = $index < 3; ?>
                                <?php $rankFrame = $isTop3 ? ['avatar-frame-gold', 'avatar-frame-silver', 'avatar-frame-bronze'][$index] : ''; ?>
                                <?php $isCurrentUser = !empty($leader['user_id']) && (int)$leader['user_id'] === (int)Session::userId(); ?>
                                <div class="points-leader-item <?= $isCurrentUser ? 'points-leader-current' : '' ?> <?= $isTop3 ? 'points-leader-top3' : '' ?>">
                                    <div class="points-leader-rank">
                                        <?php if ($isTop3): ?>
                                            <i class="bi bi-<?= ['trophy-fill','medal-silver','medal-bronze'][$index] ?> points-rank-medal points-rank-medal-<?= $index ?>"></i>
                                        <?php else: ?>
                                            <span class="points-rank-number"><?= $index + 1 ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="points-leader-info">
                                        <div class="points-leader-avatar-wrap">
                                            <?php if (!empty($leader['avatar_url'])): ?>
                                                <img src="<?= htmlspecialchars($baseUrl . '/' . ltrim($leader['avatar_url'], '/'), ENT_QUOTES, 'UTF-8') ?>" alt="" class="points-leader-avatar <?= $rankFrame ?>">
                                            <?php else: ?>
                                                <span class="points-leader-avatar points-leader-avatar-initial <?= $rankFrame ?>">
                                                    <?= htmlspecialchars(mb_strtoupper(mb_substr($leader['full_name'] ?? '', 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="points-leader-meta">
                                            <div class="points-leader-name"><?= htmlspecialchars($leader['full_name']) ?></div>
                                            <?php if ($isCurrentUser): ?>
                                                <span class="points-leader-you">● Bạn</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="points-leader-points">
                                        <strong><?= number_format((int)$leader['points']) ?></strong>
                                        <span class="points-leader-points-unit">điểm</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($userRank !== null && $userRank > 3): ?>
                            <div class="points-user-rank-footer mt-3">
                                <span class="points-user-rank-label">Bạn đang xếp hạng</span>
                                <span class="points-user-rank-number fw-bold text-success">#<?= $userRank ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== TRANSACTION HISTORY ===== -->
    <div class="points-card points-card-detail mb-4">
        <div class="points-card-header d-flex justify-content-between align-items-center">
            <h3 class="points-card-title mb-0">
                <i class="bi bi-journal me-2 text-success"></i> Lịch sử điểm
            </h3>
            <a class="points-history-link" href="<?= $baseUrl ?>/volunteer/shifts">
                <i class="bi bi-arrow-right me-1"></i> Đăng ký ca trực
            </a>
        </div>
        <div class="points-card-body">
            <?php if (empty($transactions)): ?>
                <div class="points-empty-state text-center">
                    <div class="points-empty-icon"><i class="bi bi-journal-plus text-success"></i></div>
                    <p class="points-empty-title">Chưa có giao dịch điểm.</p>
                    <p class="points-empty-desc small">Đăng ký ca trực để bắt đầu tích lũy điểm cống hiến nhé!</p>
                    <a class="points-empty-btn" href="<?= $baseUrl ?>/volunteer/shifts">
                        <i class="bi bi-plus-circle me-1"></i> Đăng ký ca trực
                    </a>
                </div>
            <?php else: ?>
                <div class="points-history-list">
                    <?php foreach ($transactions as $transaction): ?>
                        <?php $pts = (int)$transaction['points']; $isPositive = $pts >= 0; ?>
                        <div class="points-history-item">
                            <div class="points-history-date">
                                <span class="points-history-day"><?= date('d', strtotime($transaction['created_at'])) ?></span>
                                <span class="points-history-month"><?= date('m/Y', strtotime($transaction['created_at'])) ?></span>
                            </div>
                            <div class="points-history-content flex-grow-1">
                                <div class="points-history-desc"><?= htmlspecialchars($transaction['description']) ?></div>
                                <div class="points-history-meta small text-muted">
                                    <i class="bi bi-clock me-1"></i> <?= date('H:i d/m/Y', strtotime($transaction['created_at'])) ?>
                                </div>
                            </div>
                            <div class="points-history-points fw-bold <?= $isPositive ? 'text-success' : 'text-danger' ?>">
                                <?= $isPositive ? '+' : '' ?><?= $pts ?> điểm
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
