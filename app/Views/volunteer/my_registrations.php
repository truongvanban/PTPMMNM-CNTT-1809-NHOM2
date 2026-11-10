<?php
/**
 * Quản lý đơn đăng ký của tình nguyện viên - VolunteerHub Modern UI
 */
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$perPageValue = (int)($pagination['per_page'] ?? 15);
$perPageQuery = $perPageValue === 0 ? 'all' : $perPageValue;
$statusCounts = $statusCounts ?? [];
$statusTabs = [
    '' => ['label' => 'Tất cả đơn', 'icon' => 'bi-grid-fill', 'class' => 'all'],
    'pending' => ['label' => 'Chờ duyệt', 'icon' => 'bi-clock-fill', 'class' => 'pending'],
    'approved' => ['label' => 'Đã duyệt đơn', 'icon' => 'bi-check-circle-fill', 'class' => 'approved'],
    'rejected' => ['label' => 'Từ chối', 'icon' => 'bi-x-circle-fill', 'class' => 'rejected'],
];
$tabUrl = static function (string $tabStatus) use ($baseUrl, $keyword, $sort, $perPageQuery): string {
    return $baseUrl . '/volunteer/my-registrations?' . http_build_query([
        'status' => $tabStatus,
        'q' => $keyword,
        'sort' => $sort,
        'per_page' => $perPageQuery,
        'page' => 1,
    ]);
};
?>
<section class="volunteer-my-registrations-section">
    <!-- Header Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm flex-shrink-0" style="width: 52px; height: 52px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); font-size: 1.5rem;">
                    <i class="bi bi-card-checklist"></i>
                </div>
                <div>
                    <span class="vh-section-badge mb-1">Hồ Sơ Đăng Ký</span>
                    <h1 class="h3 fw-bold mb-1 text-dark">Quản Lý Đơn Đăng Ký</h1>
                    <p class="text-muted small mb-0">Theo dõi tiến độ duyệt và trạng thái phân công các ca trực bạn đã đăng ký</p>
                </div>
            </div>
            <a href="<?= $baseUrl ?>/volunteer/shifts" class="btn btn-success btn-sm rounded-pill px-3 py-2 fw-semibold text-white shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                <i class="bi bi-plus-circle me-1"></i> Đăng ký thêm ca trực
            </a>
        </div>
    </div>

    <!-- Main Panel Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <!-- Status Filter Tabs -->
        <div class="d-flex flex-wrap gap-2 mb-3 pb-3 border-bottom">
            <?php foreach ($statusTabs as $tabStatus => $tab): ?>
                <?php
                $countKey = $tabStatus === '' ? 'all_count' : $tabStatus . '_count';
                $tabCount = (int)($statusCounts[$countKey] ?? 0);
                $tabActive = $status === $tabStatus;
                ?>
                <a href="<?= htmlspecialchars($tabUrl($tabStatus), ENT_QUOTES, 'UTF-8') ?>"
                   class="btn btn-sm rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 <?= $tabActive ? 'btn-success text-white shadow-sm' : 'btn-outline-secondary' ?>"
                   style="<?= $tabActive ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;' : '' ?>"
                   <?= $tabActive ? 'aria-current="page"' : '' ?>>
                    <i class="bi <?= $tab['icon'] ?>"></i>
                    <span><?= $tab['label'] ?></span>
                    <span class="badge rounded-pill <?= $tabActive ? 'bg-white text-success' : 'bg-secondary text-white' ?>" style="font-size: 0.72rem;">
                        <?= $tabCount ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Search and Sort Filter Form -->
        <form method="GET" action="<?= $baseUrl ?>/volunteer/my-registrations" class="row g-2 align-items-center mb-4">
            <input type="hidden" name="page" value="1">
            <input type="hidden" name="per_page" value="<?= htmlspecialchars((string)$perPageQuery, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
            
            <div class="col-md-6 col-lg-7">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start-3 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="search" name="q" class="form-control bg-light border-start-0 rounded-end-3"
                           value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Tìm theo tên hoạt động, ca trực, địa điểm...">
                </div>
            </div>

            <div class="col-sm-8 col-md-4 col-lg-3">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start-3 text-muted">
                        <i class="bi bi-calendar3"></i>
                    </span>
                    <select name="sort" class="form-select bg-light border-start-0 rounded-end-3">
                        <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Mới đăng ký trước</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Cũ đăng ký trước</option>
                        <option value="event" <?= $sort === 'event' ? 'selected' : '' ?>>Ngày hoạt động</option>
                    </select>
                </div>
            </div>

            <div class="col-sm-4 col-md-2 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-success flex-grow-1 rounded-3 fw-semibold" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                    <i class="bi bi-funnel-fill me-1"></i> Lọc
                </button>
                <a href="<?= htmlspecialchars($tabUrl($status), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary rounded-3" title="Làm mới bộ lọc">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
        </form>

        <!-- Registrations Content -->
        <?php if (empty($registrations)): ?>
            <div class="p-5 text-center text-muted border rounded-3 bg-light">
                <i class="bi bi-inbox fs-1 text-secondary opacity-50 mb-2 d-block"></i>
                <h2 class="h5 fw-bold text-dark"><?= $keyword !== '' || $status !== '' ? 'Không tìm thấy đơn đăng ký phù hợp' : 'Bạn chưa đăng ký ca trực nào' ?></h2>
                <p class="small text-muted mb-3"><?= $keyword !== '' || $status !== ''
                    ? 'Thử thay đổi từ khóa hoặc bộ lọc trạng thái để xem kết quả.'
                    : 'Khám phá các ca trực đang mở và lựa chọn hoạt động cộng đồng phù hợp với bạn.' ?></p>
                <?php if ($keyword !== '' || $status !== ''): ?>
                    <a href="<?= $baseUrl ?>/volunteer/my-registrations" class="btn btn-outline-success btn-sm rounded-pill px-4">
                        <i class="bi bi-arrow-repeat me-1"></i> Xóa bộ lọc
                    </a>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>/volunteer/shifts" class="btn btn-success btn-sm rounded-pill px-4 text-white" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                        <i class="bi bi-compass me-1"></i> Khám phá ca trực ngay
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-custom mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Hoạt động</th>
                            <th>Ca trực</th>
                            <th>Thời gian ca</th>
                            <th>Ngày đăng ký</th>
                            <th>Trạng thái đơn</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $index => $r): ?>
                            <?php
                            $rowNumber = (($pagination['current_page'] - 1) * ($pagination['per_page'] ?: count($registrations))) + $index + 1;
                            $statusInfo = match ($r['status']) {
                                'approved' => ['Đã duyệt đơn', 'bg-success-subtle text-success border border-success-subtle', 'bi-check-circle-fill'],
                                'pending' => ['Chờ duyệt', 'bg-warning-subtle text-warning border border-warning-subtle', 'bi-clock-fill'],
                                'rejected' => ['Từ chối', 'bg-danger-subtle text-danger border border-danger-subtle', 'bi-x-circle-fill'],
                                'withdrawn' => ['Đã rút', 'bg-secondary-subtle text-secondary border border-secondary-subtle', 'bi-dash-circle-fill'],
                                'cancelled' => ['Đã hủy', 'bg-secondary-subtle text-secondary border border-secondary-subtle', 'bi-slash-circle-fill'],
                                default => [ucfirst((string)$r['status']), 'bg-secondary-subtle text-secondary border border-secondary-subtle', 'bi-info-circle-fill'],
                            };
                            $assignmentLabel = match ($r['assignment_status']) {
                                'assigned' => 'Chờ bạn xác nhận',
                                'confirmed' => 'Đã xác nhận tham gia',
                                'absent' => 'Vắng mặt',
                                default => ($r['status'] === 'approved' ? 'Chờ phân công' : 'Chưa phân công'),
                            };
                            $canWithdraw = $r['status'] === 'pending'
                                && empty($r['assignment_status']);
                            ?>
                            <tr>
                                <td class="text-muted small fw-semibold"><?= (int)$rowNumber ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-3 bg-light d-flex align-items-center justify-content-center text-success flex-shrink-0" style="width: 36px; height: 36px; font-size: 1.1rem;">
                                            <i class="bi bi-flag-fill"></i>
                                        </div>
                                        <div>
                                            <a href="<?= $baseUrl ?>/volunteer/activities/<?= (int)$r['activity_id'] ?>" class="fw-bold text-dark text-decoration-none">
                                                <?= htmlspecialchars($r['activity_title'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                            <div class="small text-muted">
                                                <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($r['location'] ?? 'Chưa rõ địa điểm', ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border rounded-pill px-2 py-1">
                                        <?= htmlspecialchars($r['shift_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <i class="bi bi-calendar3 text-primary me-1"></i><?= date('d/m/Y', strtotime($r['shift_date'])) ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i><?= htmlspecialchars(substr($r['time_start'], 0, 5), ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(substr($r['time_end'], 0, 5), ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                </td>
                                <td>
                                    <div><?= date('d/m/Y', strtotime($r['registered_at'])) ?></div>
                                    <small class="text-muted"><?= date('H:i', strtotime($r['registered_at'])) ?></small>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-2 py-1 <?= $statusInfo[1] ?>">
                                        <i class="bi <?= $statusInfo[2] ?> me-1"></i><?= $statusInfo[0] ?>
                                    </span>
                                    <?php if ($r['status'] === 'approved'): ?>
                                        <small class="d-block text-muted mt-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($assignmentLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= $baseUrl ?>/volunteer/activities/<?= (int)$r['activity_id'] ?>"
                                           class="btn btn-sm btn-outline-success rounded-pill px-2 py-1" style="font-size: 0.78rem;" title="Xem hoạt động">
                                            <i class="bi bi-eye me-1"></i> Xem
                                        </a>
                                        <?php if ($canWithdraw): ?>
                                            <form action="<?= $baseUrl ?>/volunteer/unregister" method="POST" class="d-inline m-0">
                                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="shift_id" value="<?= (int)$r['shift_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" style="font-size: 0.78rem;" title="Rút đơn đăng ký"
                                                        data-confirm="Bạn có chắc chắn muốn rút đăng ký ca này?">
                                                    <i class="bi bi-x-circle me-1"></i> Rút đơn
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php
    $pagination = array_merge($pagination ?? [], [
        'url' => $baseUrl . '/volunteer/my-registrations',
        'params' => ['status' => $status, 'q' => $keyword, 'sort' => $sort],
        'visible_count' => count($registrations),
        'item_label' => 'đơn đăng ký',
    ]);
    require VIEW_PATH . '/components/pagination.php';
    ?>
</section>
