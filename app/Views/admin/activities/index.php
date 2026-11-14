<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="mb-4">
        <h1 class="h4 fw-bold mb-1"><i class="bi bi-calendar3 text-danger me-2"></i>Quản Lý Toàn Bộ Hoạt Động Hệ Thống</h1>
        <p class="text-muted small mb-0">Theo dõi toàn bộ các chương trình do tất cả Điều phối viên tổ chức</p>
    </div>

    <!-- Filter form -->
    <form action="<?= $baseUrl ?>/admin/activities" method="GET" class="row g-2 mb-4">
        <div class="col-md-7">
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Tìm kiếm theo tiêu đề hoạt động hoặc địa điểm...">
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Đang mở</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Bản nháp</option>
                <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Đã đóng</option>
                <option value="ongoing" <?= $status === 'ongoing' ? 'selected' : '' ?>>Đang diễn ra</option>
                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Đã hoàn thành</option>
            </select>
        </div>
        <input type="hidden" name="per_page" value="<?= $perPage === 0 ? 'all' : (int)$perPage ?>">
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Lọc</button>
            <a href="<?= $baseUrl ?>/admin/activities" class="btn btn-outline-secondary btn-sm">Đặt lại</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle table-custom mb-0">
            <thead>
                <tr>
                    <th>Tiêu đề</th>
                    <th>Điều phối viên phụ trách</th>
                    <th>Địa điểm</th>
                    <th>Thời gian</th>
                    <th>Số ca</th>
                    <th>Trạng thái</th>
                    <th class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activities)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Không tìm thấy hoạt động nào phù hợp.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($activities as $act): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($act['title']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($act['description'] ?? '', 0, 80, '...')) ?></small>
                        </td>
                        <td>
                            <div class="fw-semibold text-primary"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($act['coordinator_name']) ?></div>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($act['location'] ?? 'Chưa rõ') ?></td>
                        <td class="small">
                            <div><?= date('d/m/Y', strtotime($act['date_start'])) ?></div>
                            <small class="text-muted">đến <?= date('d/m/Y', strtotime($act['date_end'])) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= (int)$act['shift_count'] ?> ca</span>
                        </td>
                        <td>
                            <span class="badge bg-success-soft text-success"><?= htmlspecialchars($act['status']) ?></span>
                        </td>
                        <td class="text-center">
                            <div class="admin-activity-actions">
                                <a href="<?= $baseUrl ?>/admin/activities/<?= (int)$act['id'] ?>/shifts" class="btn btn-outline-primary btn-sm" title="Xem danh sách ca trực">
                                    <i class="bi bi-grid-3x3 me-1"></i>Ca trực
                                </a>
                                <?php $actEnded = !empty($act['date_end']) && strtotime($act['date_end']) < time(); ?>
                                <?php if (!$actEnded): ?>
                                <a href="<?= $baseUrl ?>/coordinator/activities/<?= (int)$act['id'] ?>/edit" class="btn btn-outline-secondary btn-sm" title="Chỉnh sửa hoạt động">
                                    <i class="bi bi-pencil me-1"></i>Sửa
                                </a>
                                <?php else: ?>
                                <span class="btn btn-outline-secondary btn-sm disabled" style="cursor: not-allowed; pointer-events: none; opacity: 0.6;" title="Hoạt động đã kết thúc, không thể chỉnh sửa.">
                                    <i class="bi bi-pencil me-1 text-muted"></i>Sửa
                                </span>
                                <?php endif; ?>
                                <a href="<?= $baseUrl ?>/activities/<?= (int)$act['id'] ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-info btn-sm" title="Xem trang công khai">
                                    <i class="bi bi-eye me-1"></i>Xem
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
        'url' => $baseUrl . '/admin/activities',
        'params' => ['search' => $search, 'status' => $status],
        'current_page' => $page,
        'last_page' => $lastPage,
        'total' => $total,
        'per_page' => $perPage,
        'visible_count' => count($activities),
        'item_label' => 'hoạt động',
    ];
    require VIEW_PATH . '/components/pagination.php';
    ?>
</div>
