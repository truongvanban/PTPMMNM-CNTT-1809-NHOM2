<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Bảng Điều Khiển Điều Phối Viên</h1>
        <p class="text-muted small mb-0">Theo dõi tiến độ phân công, kiểm soát ca thiếu người và duyệt yêu cầu báo vắng</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $baseUrl ?>/coordinator/activities/create" class="btn btn-outline-success btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Tạo hoạt động
        </a>
        <a href="<?= $baseUrl ?>/coordinator/assignment/run" class="btn btn-primary btn-sm">
            <i class="bi bi-cpu me-1"></i> Chạy phân công tự động
        </a>
    </div>
</div>

<!-- Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">Hoạt động quản lý</div>
                    <div class="h3 fw-bold text-dark mt-1 mb-0"><?= $totalActivities ?></div>
                </div>
                <div class="stat-icon bg-primary-soft">
                    <i class="bi bi-calendar-event"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">Tổng số ca trực</div>
                    <div class="h3 fw-bold text-info mt-1 mb-0"><?= $totalShifts ?></div>
                </div>
                <div class="stat-icon bg-info-soft">
                    <i class="bi bi-grid-3x3-gap"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">Tổng phân công</div>
                    <div class="h3 fw-bold text-success mt-1 mb-0"><?= $totalAssignments ?></div>
                </div>
                <div class="stat-icon bg-success-soft">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold">Ca còn thiếu người</div>
                    <div class="h3 fw-bold text-danger mt-1 mb-0"><?= count($shortageShifts) ?></div>
                </div>
                <div class="stat-icon bg-danger-soft">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ca Thiếu Người Cần Xử Lý (Cực kỳ quan trọng) -->
<?php if (!empty($shortageShifts)): ?>
    <div class="card border-danger border-opacity-25 shadow-sm rounded-4 p-4 mb-4" style="background-color: #fffdfd;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h5 fw-bold text-danger mb-0">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i>Cảnh Báo: Các Ca Chưa Đạt Số Lượng Tối Thiểu
                </h2>
                <small class="text-muted">Các ca dưới đây chưa đủ số người tối thiểu quy định</small>
            </div>
            <a href="<?= $baseUrl ?>/coordinator/assignment/run" class="btn btn-sm btn-danger">
                <i class="bi bi-cpu me-1"></i> Chạy phân công lấp ca
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle table-custom mb-0">
                <thead>
                    <tr>
                        <th>Tên ca & Hoạt động</th>
                        <th>Ngày diễn ra</th>
                        <th>Hiện tại / Tối thiểu</th>
                        <th>Số lượng còn thiếu</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shortageShifts as $ss): ?>
                        <?php $short = (int)$ss['min_volunteers'] - (int)$ss['current_count']; ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($ss['name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($ss['activity_title']) ?></small>
                            </td>
                            <td><?= date('d/m/Y', strtotime($ss['date'])) ?></td>
                            <td>
                                <span class="badge bg-warning text-dark">
                                    <?= (int)$ss['current_count'] ?> / <?= (int)$ss['min_volunteers'] ?> người
                                </span>
                            </td>
                            <td class="text-danger fw-bold">
                                <?= $short ?> người
                            </td>
                            <td class="text-center">
                                <a href="<?= $baseUrl ?>/coordinator/assignment/manual/<?= $ss['id'] ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-person-plus me-1"></i> Thêm thủ công
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Hoạt động gần đây -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0"><i class="bi bi-calendar-check text-primary me-2"></i>Hoạt Động Quản Lý Gần Nhất</h2>
                <a href="<?= $baseUrl ?>/coordinator/activities" class="text-primary small text-decoration-none fw-semibold">Xem tất cả</a>
            </div>

            <?php if (empty($activities)): ?>
                <div class="p-4 text-center text-muted">
                    <p class="small mb-0">Bạn chưa tạo hoạt động nào.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Tiêu đề hoạt động</th>
                                <th>Thời gian</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $act): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($act['title']) ?></div>
                                        <small class="text-muted"><?= (int)$act['shift_count'] ?> ca trực</small>
                                    </td>
                                    <td class="small"><?= date('d/m/Y', strtotime($act['date_start'])) ?></td>
                                    <td>
                                        <span class="badge bg-success-soft text-success"><?= htmlspecialchars($act['status']) ?></span>
                                    </td>
                                    <td>
                                        <a href="<?= $baseUrl ?>/coordinator/activities/<?= $act['id'] ?>/shifts" class="btn btn-sm btn-light border">
                                            Xem ca
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Báo vắng chờ duyệt -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0"><i class="bi bi-arrow-left-right text-warning me-2"></i>Báo Vắng Chờ Duyệt</h2>
                <span class="badge bg-danger rounded-pill"><?= count($pendingReplacements) ?></span>
            </div>

            <?php if (empty($pendingReplacements)): ?>
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-check-all fs-2 text-success"></i>
                    <p class="small mt-2 mb-0">Không có yêu cầu báo vắng nào đang chờ xử lý.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($pendingReplacements as $pr): ?>
                        <div class="list-group-item px-0 py-2">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="fw-bold text-dark small"><?= htmlspecialchars($pr['requester_name']) ?></span>
                                <small class="text-muted"><?= date('d/m H:i', strtotime($pr['created_at'])) ?></small>
                            </div>
                            <div class="small text-muted mb-2">Ca: <?= htmlspecialchars($pr['shift_name']) ?> (<?= date('d/m', strtotime($pr['shift_date'])) ?>)</div>
                            <a href="<?= $baseUrl ?>/coordinator/replacement" class="btn btn-sm btn-outline-warning w-100">
                                Xem & Chọn người thay
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
