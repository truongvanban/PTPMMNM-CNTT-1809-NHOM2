<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$status = $status ?? '';
$activityStats = $activityStats ?? ['total' => count($activities), 'open' => 0, 'draft' => 0, 'closed' => 0, 'shifts' => 0, 'registrations' => 0];
$statusFilters = [
    '' => ['label' => 'Tất cả', 'count' => $activityStats['total']],
    'open' => ['label' => 'Đang mở', 'count' => $activityStats['open']],
    'draft' => ['label' => 'Bản nháp', 'count' => $activityStats['draft']],
    'closed' => ['label' => 'Đã đóng', 'count' => $activityStats['closed']],
];
$perPageValue = $perPage === 0 ? 'all' : $perPage;
?>
<section class="coordinator-activities">
    <header class="activity-page-header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-start gap-3">
                <div class="activity-page-icon" aria-hidden="true"><i class="bi bi-calendar2-heart"></i></div>
                <div>
                    <span class="activity-eyebrow">KHÔNG GIAN ĐIỀU PHỐI</span>
                    <h1 class="h3 fw-bold mb-1">Hoạt động của tôi</h1>
                    <p class="text-secondary mb-0">Theo dõi chương trình, ca trực và tình nguyện viên đăng ký.</p>
                </div>
            </div>
            <a href="<?= $baseUrl ?>/coordinator/activities/create" class="btn btn-success px-3 py-2 align-self-md-center">
                <i class="bi bi-plus-lg me-2"></i>Tạo hoạt động
            </a>
        </div>
    </header>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="activity-stat-card">
                <span class="activity-stat-icon text-primary bg-primary-subtle"><i class="bi bi-calendar-event"></i></span>
                <div><span class="activity-stat-label">Tổng hoạt động</span><strong><?= (int)$activityStats['total'] ?></strong></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="activity-stat-card">
                <span class="activity-stat-icon text-success bg-success-subtle"><i class="bi bi-broadcast"></i></span>
                <div><span class="activity-stat-label">Đang mở</span><strong><?= (int)$activityStats['open'] ?></strong></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="activity-stat-card">
                <span class="activity-stat-icon text-info bg-info-subtle"><i class="bi bi-list-task"></i></span>
                <div><span class="activity-stat-label">Ca trực</span><strong><?= (int)$activityStats['shifts'] ?></strong></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="activity-stat-card">
                <span class="activity-stat-icon text-warning bg-warning-subtle"><i class="bi bi-people"></i></span>
                <div><span class="activity-stat-label">Lượt đăng ký đã duyệt</span><strong><?= (int)$activityStats['registrations'] ?></strong></div>
            </div>
        </div>
    </div>

    <section class="activity-list-panel" aria-labelledby="activity-list-heading">
        <div class="activity-list-toolbar">
            <div>
                <h2 id="activity-list-heading" class="h5 fw-bold mb-1">Danh sách hoạt động</h2>
                <p class="text-secondary small mb-0">Chọn một hoạt động để xem ca trực hoặc cập nhật thông tin.</p>
            </div>
            <form action="<?= $baseUrl ?>/coordinator/activities" method="GET" class="activity-search-wrap">
                <label class="visually-hidden" for="activity-search">Tìm hoạt động</label>
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" class="form-control" id="activity-search" name="keyword"
                       value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Tìm tên hoặc địa điểm..." autocomplete="off">
                <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="per_page" value="<?= htmlspecialchars((string)$perPageValue, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="visually-hidden">Tìm</button>
            </form>
        </div>

        <nav class="activity-filters" aria-label="Lọc theo trạng thái">
            <?php foreach ($statusFilters as $filterStatus => $filter): ?>
                <a href="<?= $baseUrl ?>/coordinator/activities?<?= http_build_query(array_filter([
                    'status' => $filterStatus,
                    'keyword' => $keyword,
                    'per_page' => $perPageValue,
                ], static fn($value): bool => $value !== '')) ?>"
                   class="activity-filter <?= $status === $filterStatus ? 'active' : '' ?>"
                   <?= $status === $filterStatus ? 'aria-current="page"' : '' ?>>
                    <?= htmlspecialchars($filter['label']) ?><span><?= (int)$filter['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if (empty($activities)): ?>
            <div class="activity-empty-state">
                <span class="activity-empty-icon"><i class="bi bi-calendar2-plus"></i></span>
                <?php if ($keyword !== '' || $status !== ''): ?>
                    <h3 class="h5 fw-bold mt-3">Không tìm thấy hoạt động phù hợp</h3>
                    <p class="text-secondary mb-3">Thử thay đổi từ khóa tìm kiếm hoặc bộ lọc trạng thái.</p>
                    <a href="<?= $baseUrl ?>/coordinator/activities" class="btn btn-outline-secondary">Xóa bộ lọc</a>
                <?php else: ?>
                    <h3 class="h5 fw-bold mt-3">Chưa có hoạt động nào</h3>
                    <p class="text-secondary mb-3">Tạo hoạt động đầu tiên để bắt đầu quản lý ca trực và đăng ký tình nguyện viên.</p>
                    <a href="<?= $baseUrl ?>/coordinator/activities/create" class="btn btn-success">
                        <i class="bi bi-plus-lg me-2"></i>Tạo hoạt động mới
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="activity-table-scroll">
                <table class="table align-middle mb-0 activity-table">
                    <thead>
                        <tr>
                            <th scope="col">Hoạt động</th>
                            <th scope="col">Địa điểm</th>
                            <th scope="col">Thời gian</th>
                            <th scope="col">Hạn đăng ký</th>
                            <th scope="col">Ca trực</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col" class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="activity-table-body">
                        <?php foreach ($activities as $act): ?>
                            <?php
                            $startDate = date('d/m/Y', strtotime($act['date_start']));
                            $endDate = date('d/m/Y', strtotime($act['date_end']));
                            $statusLabel = ['open' => 'Đang mở', 'draft' => 'Bản nháp', 'closed' => 'Đã đóng', 'ongoing' => 'Đang diễn ra', 'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy'][$act['status']] ?? $act['status'];
                            $statusClass = ['open' => 'open', 'draft' => 'draft', 'closed' => 'closed', 'ongoing' => 'ongoing', 'completed' => 'completed', 'cancelled' => 'cancelled'][$act['status']] ?? 'other';
                            ?>
                            <tr>
                                <td data-label="Hoạt động">
                                    <div class="activity-title-cell">
                                        <span class="activity-row-icon"><i class="bi bi-calendar-event"></i></span>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($act['title']) ?></div>
                                            <small class="text-secondary"><?= htmlspecialchars(mb_strimwidth($act['description'] ?? '', 0, 90, '...')) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Địa điểm"><span class="activity-location"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($act['location'] ?? 'Chưa cập nhật') ?></span></td>
                                <td data-label="Thời gian">
                                    <span class="fw-semibold"><?= $startDate ?></span>
                                    <?php if ($endDate !== $startDate): ?><small class="d-block text-secondary">đến <?= $endDate ?></small><?php endif; ?>
                                </td>
                                <td data-label="Hạn đăng ký">
                                    <?php if ($act['registration_deadline']): ?>
                                        <span><?= date('d/m/Y', strtotime($act['registration_deadline'])) ?></span>
                                        <small class="d-block text-secondary"><?= date('H:i', strtotime($act['registration_deadline'])) ?></small>
                                    <?php else: ?>
                                        <span class="text-secondary">Không giới hạn</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Ca trực">
                                    <a href="<?= $baseUrl ?>/coordinator/activities/<?= (int)$act['id'] ?>/shifts" class="activity-shift-link">
                                        <i class="bi bi-list-check me-1"></i><?= (int)$act['shift_count'] ?> ca
                                    </a>
                                </td>
                                <td data-label="Trạng thái"><span class="activity-status <?= $statusClass ?>"><span></span><?= htmlspecialchars($statusLabel) ?></span></td>
                                <td data-label="Thao tác">
                                    <div class="d-flex justify-content-md-end gap-2">
                                        <a href="<?= $baseUrl ?>/coordinator/activities/<?= (int)$act['id'] ?>/shifts" class="btn btn-sm btn-outline-primary activity-action" aria-label="Quản lý ca trực: <?= htmlspecialchars($act['title'], ENT_QUOTES) ?>" title="Quản lý ca trực">
                                            <i class="bi bi-list-check me-sm-1"></i><span class="d-none d-sm-inline">Ca trực</span>
                                        </a>
                                        <a href="<?= $baseUrl ?>/coordinator/activities/<?= (int)$act['id'] ?>/edit" class="btn btn-sm btn-outline-secondary activity-action" aria-label="Chỉnh sửa: <?= htmlspecialchars($act['title'], ENT_QUOTES) ?>" title="Chỉnh sửa hoạt động">
                                            <i class="bi bi-pencil-square me-sm-1"></i><span class="d-none d-sm-inline">Sửa</span>
                                        </a>
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
            <div class="activity-list-footer">
                <span><i class="bi bi-info-circle me-1"></i>Chọn <strong>Ca trực</strong> để xem hoặc thêm ca cho hoạt động.</span>
            </div>
        <?php endif; ?>
    </section>
</section>
