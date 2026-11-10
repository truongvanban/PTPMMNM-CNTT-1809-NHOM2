<?php
/**
 * Dashboard Tình nguyện viên - VolunteerHub Modern UI
 */
use App\Core\Session;
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$userName = Session::userName();
$unlockedBadges = array_filter($badges ?? [], fn($b) => !empty($b['unlocked']));
$unlockedCount = count($unlockedBadges);
$totalBadges = count($badges ?? []);

// Progress percent towards next level
$nextLevelPoints = 200;
if ($level === 2) {
    $nextLevelPoints = 500;
} elseif ($level >= 3) {
    $nextLevelPoints = 1000;
}
$progressPercent = min(100, max(10, round(($impactPoints / $nextLevelPoints) * 100)));
?>

<!-- Welcome Banner -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm flex-shrink-0" style="width: 54px; height: 54px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); font-size: 1.6rem;">
                <i class="bi bi-person-heart"></i>
            </div>
            <div>
                <h1 class="h3 fw-bold mb-1 text-dark">Xin chào, <?= htmlspecialchars($userName) ?>! 👋</h1>
                <p class="text-muted small mb-0">Cảm ơn bạn đã luôn sẵn sàng cống hiến vì một cộng đồng tử tế và gắn kết.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $baseUrl ?>/volunteer/activities" class="btn btn-outline-success btn-sm rounded-pill px-3 py-2 fw-semibold">
                <i class="bi bi-compass me-1"></i> Khám phá hoạt động
            </a>
            <a href="<?= $baseUrl ?>/volunteer/shifts" class="btn btn-success btn-sm rounded-pill px-3 py-2 fw-semibold text-white shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                <i class="bi bi-plus-circle me-1"></i> Đăng ký ca trực
            </a>
        </div>
    </div>
</div>

<!-- Gamification & Level Widget -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4" style="background: radial-gradient(circle at 85% 20%, rgba(20, 184, 166, 0.35) 0%, transparent 50%), linear-gradient(135deg, #064e3b 0%, #065f46 55%, #047857 100%); color: white;">
    <div class="row align-items-center g-4">
        <!-- Level & Points -->
        <div class="col-lg-6">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-circle bg-white text-success d-flex align-items-center justify-content-center shadow flex-shrink-0" style="width: 65px; height: 65px; font-size: 2rem;">
                    <i class="bi bi-award-fill text-warning"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-warning text-dark fw-bold rounded-pill px-3 py-1">
                            <i class="bi bi-shield-fill-check me-1"></i> Cấp độ <?= $level ?>
                        </span>
                        <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-2 py-1 small">
                            <?= htmlspecialchars($levelName) ?>
                        </span>
                    </div>
                    <h2 class="h4 fw-bold mb-0 text-white">
                        <?= number_format($impactPoints) ?> <span class="fs-6 fw-normal text-white-50">điểm cống hiến</span>
                    </h2>
                </div>
            </div>

            <!-- Progress to next level -->
            <div>
                <div class="d-flex justify-content-between small text-white-50 mb-1">
                    <span>Tiến độ cấp độ tiếp theo</span>
                    <span><?= $impactPoints ?> / <?= $nextLevelPoints ?> pts (<?= $progressPercent ?>%)</span>
                </div>
                <div class="progress rounded-pill" style="height: 8px; background: rgba(255, 255, 255, 0.2);">
                    <div class="progress-bar bg-warning rounded-pill" role="progressbar" style="width: <?= $progressPercent ?>%;" aria-valuenow="<?= $progressPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>

        <!-- Badges Showcase -->
        <div class="col-lg-6">
            <div class="d-flex justify-content-between align-items-center mb-2 text-white-50 small">
                <span class="fw-semibold text-white"><i class="bi bi-patch-check-fill text-warning me-1"></i> Bộ sưu tập huy hiệu</span>
                <span class="badge bg-white bg-opacity-20 text-white rounded-pill"><?= $unlockedCount ?> / <?= $totalBadges ?> mở khóa</span>
            </div>
            <div class="row g-2">
                <?php foreach ($badges as $badge): ?>
                    <div class="col-6 col-sm-3">
                        <div class="p-2 rounded-3 text-center h-100 d-flex flex-column align-items-center justify-content-center <?= $badge['unlocked'] ? 'bg-white bg-opacity-25 text-white border border-white border-opacity-30' : 'bg-black bg-opacity-20 text-white-50 border border-transparent' ?>" 
                             data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($badge['desc']) ?>"
                             style="cursor: pointer; transition: transform 0.2s;">
                            <div class="mb-1" style="font-size: 1.45rem;">
                                <i class="bi <?= $badge['icon'] ?> <?= $badge['unlocked'] ? 'text-warning' : 'opacity-50' ?>"></i>
                            </div>
                            <div style="font-size: 0.72rem; line-height: 1.2;" class="fw-semibold text-truncate w-100">
                                <?= htmlspecialchars($badge['name']) ?>
                            </div>
                            <span class="badge rounded-pill mt-1 <?= $badge['unlocked'] ? 'bg-success text-white' : 'bg-secondary text-white-50' ?>" style="font-size: 0.58rem; padding: 0.15rem 0.4rem;">
                                <?= $badge['unlocked'] ? 'Đã đạt' : 'Chưa đạt' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Stat Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card p-3 border-0">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">Ca đã phân công</div>
                    <div class="h3 fw-bold text-dark mt-1 mb-0"><?= $totalAssigned ?></div>
                    <small class="text-muted" style="font-size: 0.75rem;">Theo lịch trực hệ thống</small>
                </div>
                <div class="stat-icon" style="background-color: rgba(16, 185, 129, 0.12); color: #10b981;">
                    <i class="bi bi-calendar2-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card p-3 border-0">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">Đã xác nhận</div>
                    <div class="h3 fw-bold text-success mt-1 mb-0"><?= $confirmedCount ?></div>
                    <small class="text-success" style="font-size: 0.75rem;">Sẵn sàng tham gia</small>
                </div>
                <div class="stat-icon" style="background-color: rgba(32, 201, 151, 0.15); color: #20c997;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card p-3 border-0">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">Ca sắp diễn ra</div>
                    <div class="h3 fw-bold text-info mt-1 mb-0"><?= $upcomingCount ?></div>
                    <small class="text-info" style="font-size: 0.75rem;">Từ hôm nay trở đi</small>
                </div>
                <div class="stat-icon" style="background-color: rgba(6, 182, 212, 0.15); color: #06b6d4;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card p-3 border-0">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">Kỹ năng khai báo</div>
                    <div class="h3 fw-bold text-warning mt-1 mb-0"><?= count($skills) ?></div>
                    <small class="text-muted" style="font-size: 0.75rem;">Ưu tiên khớp ca</small>
                </div>
                <div class="stat-icon" style="background-color: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Phân công gần đây -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0 text-dark">
                    <i class="bi bi-calendar-event text-success me-2"></i>Ca Trực Được Phân Công Gần Nhất
                </h2>
                <a href="<?= $baseUrl ?>/volunteer/schedule" class="text-success small fw-semibold text-decoration-none">
                    Xem toàn bộ lịch <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <?php if (empty($assignments)): ?>
                <div class="p-5 text-center text-muted border rounded-3 bg-light">
                    <i class="bi bi-inbox fs-1 text-secondary opacity-50 mb-2 d-block"></i>
                    <h3 class="h6 fw-bold text-dark">Bạn chưa có ca trực nào được phân công</h3>
                    <p class="small text-muted mb-3">Hãy khám phá các hoạt động và đăng ký ca trực bạn yêu thích nhé!</p>
                    <a href="<?= $baseUrl ?>/volunteer/activities" class="btn btn-outline-success btn-sm rounded-pill px-4">
                        Khám phá hoạt động ngay
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-custom mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ca trực & Hoạt động</th>
                                <th>Thời gian</th>
                                <th>Trạng thái</th>
                                <th class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $asgn): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($asgn['shift_name']) ?></div>
                                        <small class="text-muted"><i class="bi bi-flag-fill text-success me-1"></i><?= htmlspecialchars($asgn['activity_title']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><i class="bi bi-calendar3 text-primary me-1"></i><?= date('d/m/Y', strtotime($asgn['shift_date'])) ?></div>
                                        <small class="text-muted"><i class="bi bi-clock me-1"></i><?= substr($asgn['time_start'], 0, 5) ?> - <?= substr($asgn['time_end'], 0, 5) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($asgn['attendance_status'] === 'confirmed'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                                <i class="bi bi-check-circle-fill me-1"></i> Đã xác nhận
                                            </span>
                                        <?php elseif ($asgn['attendance_status'] === 'absent'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">
                                                <i class="bi bi-x-circle-fill me-1"></i> Báo vắng
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1">
                                                <i class="bi bi-clock-fill me-1"></i> Chờ xác nhận
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($asgn['attendance_status'] === 'assigned'): ?>
                                            <form action="<?= $baseUrl ?>/volunteer/confirm" method="POST" class="d-inline">
                                                <input type="hidden" name="_csrf_token" value="<?= Session::generateCsrf() ?>">
                                                <input type="hidden" name="assignment_id" value="<?= $asgn['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 py-1" style="font-size: 0.78rem;">
                                                    <i class="bi bi-check2"></i> Xác nhận
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <a href="<?= $baseUrl ?>/volunteer/schedule" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1" style="font-size: 0.78rem;">
                                                Chi tiết
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cột Kỹ năng & Thao tác nhanh -->
    <div class="col-lg-4">
        <!-- Kỹ năng của tôi -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 fw-bold mb-0 text-uppercase text-dark">
                    <i class="bi bi-patch-check text-success me-1"></i> Kỹ năng đã khai báo
                </h2>
                <a href="<?= $baseUrl ?>/volunteer/skills" class="small text-success text-decoration-none fw-semibold">
                    Cập nhật <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <?php if (empty($skills)): ?>
                <div class="alert alert-info border-0 rounded-3 small mb-0 py-2" style="background-color: #ecfdf5; color: #065f46;">
                    <i class="bi bi-info-circle me-1"></i> Chưa khai báo kỹ năng. Cập nhật kỹ năng để được thuật toán MCF ưu tiên xếp ca phù hợp!
                </div>
            <?php else: ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($skills as $sk): ?>
                        <span class="badge bg-light text-dark border p-2 rounded-3 d-inline-flex align-items-center gap-1">
                            <i class="bi bi-star-fill text-warning" style="font-size: 0.75rem;"></i>
                            <strong><?= htmlspecialchars($sk['name']) ?></strong>
                            <small class="text-success ms-1">(<?= htmlspecialchars($sk['level']) ?>)</small>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Thao tác nhanh -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h2 class="h6 fw-bold mb-3 text-uppercase text-dark">
                <i class="bi bi-lightning-charge text-warning me-1"></i> Thao tác nhanh
            </h2>
            <div class="d-grid gap-2">
                <a href="<?= $baseUrl ?>/volunteer/activities" class="btn btn-outline-success btn-sm text-start py-2 rounded-3 d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-compass me-2"></i> Khám phá hoạt động</span>
                    <i class="bi bi-chevron-right small text-muted"></i>
                </a>
                <a href="<?= $baseUrl ?>/volunteer/availability" class="btn btn-outline-primary btn-sm text-start py-2 rounded-3 d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-clock-history me-2"></i> Khai báo lịch rảnh</span>
                    <i class="bi bi-chevron-right small text-muted"></i>
                </a>
                <a href="<?= $baseUrl ?>/volunteer/replacement" class="btn btn-outline-danger btn-sm text-start py-2 rounded-3 d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-person-x me-2"></i> Báo vắng ca đột xuất</span>
                    <i class="bi bi-chevron-right small text-muted"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Hoạt Động Mới Nhất Đang Tuyển -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h2 class="h5 fw-bold mb-1 text-dark">
                <i class="bi bi-fire text-danger me-2"></i>Hoạt Động Mới Nhất Đang Tuyển Tình Nguyện Viên
            </h2>
            <p class="text-muted small mb-0">Lựa chọn hoạt động ý nghĩa và đăng ký tham gia ngay</p>
        </div>
        <a href="<?= $baseUrl ?>/volunteer/activities" class="btn btn-outline-success btn-sm rounded-pill px-3">
            Xem tất cả hoạt động <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <?php if (empty($featuredActivities)): ?>
        <div class="p-4 text-center text-muted">
            <p class="small mb-0">Hiện tại chưa có hoạt động nào đang mở tuyển.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($featuredActivities as $act): ?>
                <div class="col-md-4">
                    <div class="vh-activity-card h-100">
                        <div class="vh-activity-cover-fallback" style="height: 110px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="vh-activity-status-chip">
                                    <i class="bi bi-record-circle-fill me-1 text-success"></i> Đang tuyển
                                </span>
                                <?php if (!empty($act['category'])): ?>
                                    <span class="badge bg-white bg-opacity-25 text-white" style="font-size: 0.7rem; border: 1px solid rgba(255,255,255,0.3);">
                                        <?= htmlspecialchars($act['category']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="vh-activity-date-chip">
                                    <i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($act['date_start'])) ?>
                                </span>
                                <span class="badge bg-dark bg-opacity-50 text-white" style="font-size: 0.7rem;">
                                    <i class="bi bi-grid-fill me-1"></i> <?= (int)$act['shift_count'] ?> ca
                                </span>
                            </div>
                        </div>

                        <div class="vh-activity-body p-3">
                            <h3 class="vh-activity-title h6 mb-1 text-truncate">
                                <a href="<?= $baseUrl ?>/volunteer/activities/<?= $act['id'] ?>" class="text-dark text-decoration-none">
                                    <?= htmlspecialchars($act['title']) ?>
                                </a>
                            </h3>
                            <p class="vh-activity-desc small text-muted mb-2 flex-grow-1">
                                <?= htmlspecialchars(mb_strimwidth($act['description'] ?? 'Chưa có mô tả.', 0, 80, '...')) ?>
                            </p>
                            <div class="small text-muted mb-3">
                                <div><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($act['location'] ?? 'Chưa rõ') ?></div>
                            </div>
                            <a href="<?= $baseUrl ?>/volunteer/activities/<?= $act['id'] ?>" class="vh-activity-btn py-2">
                                <span>Xem chi tiết & Đăng ký</span>
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
