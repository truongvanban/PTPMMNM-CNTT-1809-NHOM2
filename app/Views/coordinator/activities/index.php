<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$status = $status ?? '';
$activityStats = $activityStats ?? ['total' => count($activities), 'open' => 0, 'draft' => 0, 'closed' => 0, 'ongoing' => 0, 'upcoming' => 0, 'completed' => 0, 'cancelled' => 0, 'shifts' => 0, 'registrations' => 0];
$statusFilters = [
    '' => ['label' => 'Tất cả', 'count' => $activityStats['total']],
    'ongoing' => ['label' => 'Đang diễn ra', 'count' => $activityStats['ongoing']],
    'open' => ['label' => 'Đang mở', 'count' => $activityStats['open']],
    'completed' => ['label' => 'Đã kết thúc', 'count' => $activityStats['completed']],
    'draft' => ['label' => 'Bản nháp', 'count' => $activityStats['draft']],
    'closed' => ['label' => 'Đã đóng', 'count' => $activityStats['closed']],
    'cancelled' => ['label' => 'Đã hủy', 'count' => $activityStats['cancelled']],
];
$perPageValue = $perPage === 0 ? 'all' : $perPage;
?>
<section class="coordinator-activities">
    <header class="activity-page-header">
        <div class="activity-management-heading">
            <div class="activity-management-title">
                <div class="activity-page-icon" aria-hidden="true"><i class="bi bi-calendar2-heart"></i></div>
                <div>
                    <h1 class="h3 fw-bold mb-1">Quản lý hoạt động</h1>
                    <p class="text-secondary mb-0">Tạo, chỉnh sửa và quản lý các hoạt động tình nguyện</p>
                </div>
            </div>
            <a href="<?= $baseUrl ?>/coordinator/activities/create" class="btn btn-primary px-3 py-2 activity-management-create">
                <i class="bi bi-plus-lg me-2"></i>Tạo hoạt động
            </a>
        </div>
    </header>

    <div class="activity-management-stats">
        <article class="activity-management-stat is-blue">
            <span class="activity-management-stat-icon"><i class="bi bi-calendar-event-fill"></i></span>
            <div><strong><?= (int)$activityStats['total'] ?></strong><span>Tổng hoạt động</span></div>
            <i class="bi bi-graph-up-arrow activity-management-stat-trend"></i>
        </article>
        <article class="activity-management-stat is-green">
            <span class="activity-management-stat-icon"><i class="bi bi-check-circle-fill"></i></span>
            <div><strong><?= (int)$activityStats['ongoing'] ?></strong><span>Đang diễn ra</span></div>
            <i class="bi bi-graph-up-arrow activity-management-stat-trend"></i>
        </article>
        <article class="activity-management-stat is-orange">
            <span class="activity-management-stat-icon"><i class="bi bi-clock-fill"></i></span>
            <div><strong><?= (int)$activityStats['upcoming'] ?></strong><span>Sắp diễn ra</span></div>
            <i class="bi bi-arrow-right activity-management-stat-trend"></i>
        </article>
        <article class="activity-management-stat is-purple">
            <span class="activity-management-stat-icon"><i class="bi bi-flag-fill"></i></span>
            <div><strong><?= (int)$activityStats['completed'] ?></strong><span>Đã kết thúc</span></div>
            <i class="bi bi-graph-up-arrow activity-management-stat-trend"></i>
        </article>
    </div>

    <section class="activity-list-panel" aria-labelledby="activity-list-heading">
        <form action="<?= $baseUrl ?>/coordinator/activities" method="GET" class="activity-management-filters">
            <label class="activity-management-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <span class="visually-hidden">Tìm tên hoặc địa điểm</span>
                <input type="search" name="keyword" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Nhập tên hoạt động..." autocomplete="off">
            </label>
            <label class="activity-management-select">
                <span class="visually-hidden">Lọc theo trạng thái</span>
                <select name="status">
                    <?php foreach ($statusFilters as $filterStatus => $filter): ?>
                        <option value="<?= htmlspecialchars($filterStatus, ENT_QUOTES, 'UTF-8') ?>" <?= $status === $filterStatus ? 'selected' : '' ?>>
                            <?= htmlspecialchars($filter['label']) ?><?= $filterStatus !== '' ? ' (' . (int)$filter['count'] . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </label>
            <input type="hidden" name="per_page" value="<?= htmlspecialchars((string)$perPageValue, ENT_QUOTES, 'UTF-8') ?>">
            <a href="<?= $baseUrl ?>/coordinator/activities" class="btn btn-outline-secondary activity-management-reset">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Đặt lại
            </a>
            <button type="submit" class="btn btn-primary activity-management-submit">
                <i class="bi bi-search me-1"></i>Tìm kiếm
            </button>
        </form>

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
                            <th scope="col">Thời gian</th>
                            <th scope="col">Địa điểm</th>
                            <th scope="col">Ca trực</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Người tạo</th>
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
                            $rowIconClass = ['open' => 'is-green', 'draft' => 'is-purple', 'closed' => 'is-gray', 'ongoing' => 'is-green', 'completed' => 'is-blue', 'cancelled' => 'is-red'][$act['status']] ?? 'is-blue';
                            ?>
                            <tr>
                                <td data-label="Hoạt động">
                                    <div class="activity-title-cell">
                                        <span class="activity-row-icon <?= $rowIconClass ?>"><i class="bi bi-calendar-event"></i></span>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($act['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <small class="text-secondary"><?= htmlspecialchars(mb_strimwidth($act['description'] ?? '', 0, 90, '...'), ENT_QUOTES, 'UTF-8') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Thời gian">
                                    <span class="fw-semibold"><?= $startDate ?></span>
                                    <small class="d-block text-secondary"><?= date('H:i', strtotime($act['date_start'])) ?>–<?= date('H:i', strtotime($act['date_end'])) ?></small>
                                </td>
                                <td data-label="Địa điểm"><span class="activity-location"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($act['location'] ?? 'Chưa cập nhật', ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td data-label="Ca trực">
                                    <a href="<?= $baseUrl ?>/coordinator/activities/<?= (int)$act['id'] ?>/shifts" class="activity-shift-link">
                                        <i class="bi bi-list-check me-1"></i><?= (int)$act['shift_count'] ?> ca
                                    </a>
                                </td>
                                <td data-label="Trạng thái"><span class="activity-status <?= $statusClass ?>"><span></span><?= htmlspecialchars($statusLabel) ?></span></td>
                                <td data-label="Người tạo">
                                    <span class="activity-management-creator"><i class="bi bi-person-circle"></i><?= htmlspecialchars($coordinatorName ?? 'Bạn', ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td data-label="Thao tác">
                                    <div class="d-flex justify-content-md-end gap-2">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-light activity-management-menu" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Thao tác với <?= htmlspecialchars($act['title'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="<?= $baseUrl ?>/coordinator/activities/<?= (int)$act['id'] ?>/shifts"><i class="bi bi-list-check me-2"></i>Quản lý ca trực</a></li>
                                                <li><a class="dropdown-item" href="<?= $baseUrl ?>/coordinator/activities/<?= (int)$act['id'] ?>/edit"><i class="bi bi-pencil-square me-2"></i>Chỉnh sửa</a></li>
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
            <div class="activity-list-footer">
                <span><i class="bi bi-info-circle me-1"></i>Chọn <strong>Ca trực</strong> để xem hoặc thêm ca cho hoạt động.</span>
            </div>
        <?php endif; ?>
    </section>
</section>
