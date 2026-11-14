<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$status = $status ?? '';
$keyword = $keyword ?? '';
$activityStats = $activityStats ?? ['total' => count($activities), 'open' => 0, 'draft' => 0, 'closed' => 0, 'ongoing' => 0, 'upcoming' => 0, 'completed' => 0, 'cancelled' => 0, 'shifts' => 0, 'registrations' => 0];
$perPageValue = $perPage === 0 ? 'all' : (string)$perPage;

$statusFilters = [
    '' => ['label' => 'Tất cả', 'count' => (int)$activityStats['total'], 'color' => 'is-slate'],
    'ongoing' => ['label' => 'Đang diễn ra', 'count' => (int)$activityStats['ongoing'], 'color' => 'is-blue'],
    'open' => ['label' => 'Đang mở', 'count' => (int)$activityStats['open'], 'color' => 'is-green'],
    'completed' => ['label' => 'Đã kết thúc', 'count' => (int)$activityStats['completed'], 'color' => 'is-purple'],
    'draft' => ['label' => 'Bản nháp', 'count' => (int)$activityStats['draft'], 'color' => 'is-gray'],
    'closed' => ['label' => 'Đã đóng', 'count' => (int)$activityStats['closed'], 'color' => 'is-gray'],
    'cancelled' => ['label' => 'Đã hủy', 'count' => (int)$activityStats['cancelled'], 'color' => 'is-red'],
];
?>
<section class="coordinator-activities activity-v2">
    <!-- ===== PAGE HEADER ===== -->
    <header class="activity-v2-header">
        <div class="activity-v2-header-inner">
            <div class="activity-v2-brand">
                <div class="activity-v2-icon"><i class="bi bi-calendar2-heart"></i></div>
                <div class="activity-v2-text">
                    <h1 class="activity-v2-title">Quản lý hoạt động</h1>
                    <p class="activity-v2-subtitle">Tạo, chỉnh sửa và quản lý các hoạt động tình nguyện</p>
                </div>
            </div>
            <div class="activity-v2-controls">
                <div class="activity-v2-stat-chip">
                    <span class="activity-v2-chip-label">Tổng: <strong><?= (int)$activityStats['total'] ?></strong> hoạt động</span>
                </div>
                <a href="<?= $baseUrl ?>/coordinator/activities/create" class="btn btn-primary activity-v2-create-btn">
                    <i class="bi bi-plus-lg me-2"></i>Tạo hoạt động
                </a>
            </div>
        </div>
    </header>

    <!-- ===== STAT CARDS ===== -->
    <div class="activity-v2-stats">
        <div class="activity-v2-stat activity-v2-stat-total">
            <div class="activity-v2-stat-body">
                <span class="activity-v2-stat-icon is-slate"><i class="bi bi-calendar-event-fill"></i></span>
                <div class="activity-v2-stat-info">
                    <strong class="activity-v2-stat-value"><?= (int)$activityStats['total'] ?></strong>
                    <span class="activity-v2-stat-label">Tổng hoạt động</span>
                </div>
            </div>
        </div>
        <div class="activity-v2-stat activity-v2-stat-open">
            <div class="activity-v2-stat-body">
                <span class="activity-v2-stat-icon is-green"><i class="bi bi-check2"></i></span>
                <div class="activity-v2-stat-info">
                    <strong class="activity-v2-stat-value"><?= (int)$activityStats['open'] ?></strong>
                    <span class="activity-v2-stat-label">Đang mở đăng ký</span>
                </div>
            </div>
        </div>
        <div class="activity-v2-stat activity-v2-stat-ongoing">
            <div class="activity-v2-stat-body">
                <span class="activity-v2-stat-icon is-blue"><i class="bi bi-play-circle"></i></span>
                <div class="activity-v2-stat-info">
                    <strong class="activity-v2-stat-value"><?= (int)$activityStats['ongoing'] ?></strong>
                    <span class="activity-v2-stat-label">Đang diễn ra</span>
                </div>
            </div>
        </div>
        <div class="activity-v2-stat activity-v2-stat-upcoming">
            <div class="activity-v2-stat-body">
                <span class="activity-v2-stat-icon is-blue"><i class="bi bi-clock"></i></span>
                <div class="activity-v2-stat-info">
                    <strong class="activity-v2-stat-value"><?= (int)$activityStats['upcoming'] ?></strong>
                    <span class="activity-v2-stat-label">Sắp diễn ra</span>
                </div>
            </div>
        </div>
        <div class="activity-v2-stat activity-v2-stat-completed">
            <div class="activity-v2-stat-body">
                <span class="activity-v2-stat-icon is-purple"><i class="bi bi-check-circle"></i></span>
                <div class="activity-v2-stat-info">
                    <strong class="activity-v2-stat-value"><?= (int)$activityStats['completed'] ?></strong>
                    <span class="activity-v2-stat-label">Đã kết thúc</span>
                </div>
            </div>
        </div>
        <div class="activity-v2-stat activity-v2-stat-cancelled">
            <div class="activity-v2-stat-body">
                <span class="activity-v2-stat-icon is-red"><i class="bi bi-x-circle"></i></span>
                <div class="activity-v2-stat-info">
                    <strong class="activity-v2-stat-value"><?= (int)$activityStats['cancelled'] ?></strong>
                    <span class="activity-v2-stat-label">Đã hủy</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FILTERS ===== -->
    <div class="activity-v2-filters">
        <form action="<?= $baseUrl ?>/coordinator/activities" method="GET" class="activity-v2-filter-form">
            <input type="hidden" name="per_page" value="<?= htmlspecialchars($perPageValue, ENT_QUOTES, 'UTF-8') ?>">

            <div class="activity-v2-search">
                <i class="bi bi-search"></i>
                <input type="search" name="keyword" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>" placeholder="Tìm tên hoặc địa điểm..." autocomplete="off">
            </div>

            <div class="activity-v2-status-chips">
                <span class="activity-v2-chip-title">Trạng thái</span>
                <?php foreach ($statusFilters as $filterStatus => $filter): ?>
                    <a href="<?= $baseUrl ?>/coordinator/activities?<?= http_build_query(['status' => $filterStatus, 'keyword' => $keyword, 'per_page' => $perPageValue]) ?>"
                       class="activity-v2-chip <?= $status === $filterStatus ? 'active' : '' ?> <?= $filter['color'] ?>"
                       title="<?= htmlspecialchars($filter['label'], ENT_QUOTES, 'UTF-8') ?>">
                        <span class="activity-v2-chip-dot"></span>
                        <span class="activity-v2-chip-text"><?= htmlspecialchars($filter['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="activity-v2-chip-count"><?= (int)$filter['count'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="activity-v2-filter-actions">
                <?php if ($keyword !== '' || $status !== ''): ?>
                    <a href="<?= $baseUrl ?>/coordinator/activities?per_page=<?= htmlspecialchars($perPageValue, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm activity-v2-reset">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Đặt lại
                    </a>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary btn-sm activity-v2-apply">
                    <i class="bi bi-funnel me-1"></i>Áp dụng
                </button>
            </div>
        </form>
    </div>

    <!-- ===== RESULTS ===== -->
    <?php if (empty($activities)): ?>
        <div class="activity-v2-empty">
            <div class="activity-v2-empty-icon"><i class="bi bi-calendar2-plus"></i></div>
            <?php if ($keyword !== '' || $status !== ''): ?>
                <h3 class="activity-v2-empty-title">Không tìm thấy hoạt động phù hợp</h3>
                <p class="activity-v2-empty-desc">Thử thay đổi từ khóa tìm kiếm hoặc bộ lọc trạng thái.</p>
                <a href="<?= $baseUrl ?>/coordinator/activities?per_page=<?= htmlspecialchars($perPageValue, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Xóa bộ lọc</a>
            <?php else: ?>
                <h3 class="activity-v2-empty-title">Chưa có hoạt động nào</h3>
                <p class="activity-v2-empty-desc">Tạo hoạt động đầu tiên để bắt đầu quản lý ca trực và đăng ký tình nguyện viên.</p>
                <a href="<?= $baseUrl ?>/coordinator/activities/create" class="btn btn-success activity-v2-empty-cta">
                    <i class="bi bi-plus-lg me-2"></i>Tạo hoạt động mới
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="activity-v2-table-wrap">
            <div class="table-responsive">
                <table class="table align-middle mb-0 activity-v2-table">
                    <thead>
                        <tr>
                            <th scope="col">Hoạt động</th>
                            <th scope="col">Thời gian</th>
                            <th scope="col">Địa điểm</th>
                            <th scope="col" class="text-end">Ca trực / Đăng ký</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col" class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="activity-table-body">
                        <?php foreach ($activities as $act): ?>
                            <?php
                            $startDate = date('d/m/Y', strtotime($act['date_start']));
                            $endDate = date('d/m/Y', strtotime($act['date_end']));
                            // Ưu tiên trạng thái hiệu lực (theo ngày thực tế) để widget và danh sách luôn đồng bộ.
                            $displayStatus = $act['effective_status'] ?? $act['status'];
                            $statusLabel = ['open' => 'Đang mở', 'draft' => 'Bản nháp', 'closed' => 'Đã đóng', 'ongoing' => 'Đang diễn ra', 'completed' => 'Đã kết thúc', 'cancelled' => 'Đã hủy'][$displayStatus] ?? $displayStatus;
                            $statusClass = ['open' => 'open', 'draft' => 'draft', 'closed' => 'closed', 'ongoing' => 'ongoing', 'completed' => 'completed', 'cancelled' => 'cancelled'][$displayStatus] ?? 'other';
                            $rowIconClass = ['open' => 'is-green', 'draft' => 'is-purple', 'closed' => 'is-gray', 'ongoing' => 'is-green', 'completed' => 'is-blue', 'cancelled' => 'is-red'][$displayStatus] ?? 'is-blue';
                            ?>
                            <tr>
                                <td data-label="Hoạt động">
                                    <div class="activity-v2-row">
                                        <span class="activity-v2-row-icon <?= $rowIconClass ?>"><i class="bi bi-calendar-event"></i></span>
                                        <div class="activity-v2-row-info">
                                            <div class="fw-bold text-dark activity-v2-row-title"><?= htmlspecialchars($act['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <small class="text-secondary activity-v2-row-desc"><?= htmlspecialchars(mb_strimwidth($act['description'] ?? '', 0, 90, '...'), ENT_QUOTES, 'UTF-8') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Thời gian">
                                    <span class="fw-semibold activity-v2-row-date"><?= $startDate ?></span>
                                    <small class="d-block text-secondary activity-v2-row-time"><?= date('H:i', strtotime($act['date_start'])) ?> – <?= date('H:i', strtotime($act['date_end'])) ?></small>
                                </td>
                                <td data-label="Địa điểm">
                                    <span class="activity-v2-location"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($act['location'] ?? 'Chưa cập nhật', ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td data-label="Ca / Đăng ký" class="text-end">
                                    <div class="activity-v2-meta">
                                        <span class="activity-v2-meta-item"><i class="bi bi-list-check me-1"></i><?= (int)$act['shift_count'] ?> ca</span>
                                        <?php if ((int)$act['shift_count'] === 0 && in_array($displayStatus, ['open', 'ongoing'], true)): ?>
                                        <span class="activity-v2-meta-item text-danger fw-semibold" style="font-size:0.72rem;"><i class="bi bi-exclamation-triangle me-1"></i> Chưa có ca trực</span>
                                        <?php endif; ?>
                                        <span class="activity-v2-meta-item"><i class="bi bi-person-plus me-1"></i><?= (int)($act['reg_count'] ?? 0) ?> đăng ký</span>
                                    </div>
                                </td>
                                <td data-label="Trạng thái">
                                    <span class="activity-v2-status <?= $statusClass ?>"><span class="activity-v2-status-dot"></span><?= htmlspecialchars($statusLabel) ?></span>
                                </td>
                                <td data-label="Thao tác" class="text-end">
                                    <div class="d-flex justify-content-md-end gap-2">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-light activity-v2-menu" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Thao tác với <?= htmlspecialchars($act['title'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            <?php $actEnded = !empty($act['date_end']) && strtotime($act['date_end']) < time(); ?>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item activity-v2-action" href="<?= $baseUrl ?>/coordinator/activities/<?= (int)$act['id'] ?>/shifts"><i class="bi bi-list-check me-2 text-success"></i>Quản lý ca trực</a></li>
                                                <?php if (!$actEnded): ?>
                                                <li><a class="dropdown-item activity-v2-action" href="<?= $baseUrl ?>/coordinator/activities/<?= (int)$act['id'] ?>/edit"><i class="bi bi-pencil-square me-2 text-primary"></i>Chỉnh sửa</a></li>
                                                <?php else: ?>
                                                <li><span class="dropdown-item text-muted" style="cursor: not-allowed; pointer-events: none;"><i class="bi bi-pencil-square me-2 text-muted opacity-50"></i>Chỉnh sửa (đã kết thúc)</span></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $pagination = [
                'url' => $baseUrl . '/coordinator/activities',
                'params' => ['status' => $status, 'keyword' => $keyword],
                'current_page' => $page,
                'last_page' => $lastPage,
                'total' => $total,
                'per_page' => $perPage,
                'visible_count' => count($activities),
                'item_label' => 'hoạt động',
            ];
            require VIEW_PATH . '/components/pagination.php';
            ?>

            <div class="activity-v2-footer">
                <span><i class="bi bi-info-circle me-1"></i>Chọn <strong>Ca trực</strong> để xem hoặc thêm ca cho hoạt động.</span>
            </div>
        </div>
    <?php endif; ?>
</section>
