<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/coordinator/assignment/run">Phân công</a></li>
                <li class="breadcrumb-item active">Xem trước phương án</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold mb-0">Xem Trước Kết Quả Phân Công</h1>
        <small class="text-muted">Hoạt động: <strong><?= htmlspecialchars($run['activity_title']) ?></strong> | Thuật toán: <span class="badge bg-success-soft text-success"><i class="bi bi-magic me-1"></i>Thuật toán phân công tối ưu</span></small>
    </div>

    <div>
        <?php if ($registrationsChanged): ?>
            <a href="<?= $baseUrl ?>/coordinator/assignment/run?activity_id=<?= (int)$run['activity_id'] ?>" class="btn btn-warning px-4 fw-bold">
                <i class="bi bi-arrow-repeat me-1"></i> Chạy lại với danh sách mới
            </a>
        <?php elseif ($run['status'] === 'confirmed'): ?>
            <span class="badge bg-success p-2 fs-6"><i class="bi bi-check-circle-fill me-1"></i> Phương án này đã được áp dụng</span>
        <?php else: ?>
            <form action="<?= $baseUrl ?>/coordinator/assignment/confirm/<?= $run['id'] ?>" method="POST" class="d-inline">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                <button type="submit" class="btn btn-success px-4 fw-bold" data-confirm="Bạn có chắc chắn muốn xác nhận và lưu phương án phân công này vào hệ thống?">
                    <i class="bi bi-check2-all me-1"></i> Xác Nhận & Lưu Phương Án
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($registrationsChanged): ?>
    <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>
            <strong>Phương án đã cũ.</strong> Danh sách đơn đăng ký đã duyệt thay đổi sau khi chạy thuật toán nên phương án này chưa bao gồm người đăng ký mới. Hãy chạy lại để cập nhật trước khi xác nhận.
        </div>
    </div>
<?php endif; ?>

<?php $pendingRegistrationTotal = array_sum($pendingRegistrationCounts); ?>
<?php if ($pendingRegistrationTotal > 0): ?>
    <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4" role="alert">
        <div>
            <i class="bi bi-hourglass-split me-2"></i>
            Có <strong><?= (int)$pendingRegistrationTotal ?> đơn đăng ký đang chờ duyệt</strong>; các đơn này chưa được đưa vào phương án phân công.
        </div>
        <a href="<?= $baseUrl ?>/coordinator/registrations?status=pending" class="btn btn-sm btn-outline-dark">
            Duyệt đơn đăng ký
        </a>
    </div>
<?php endif; ?>

<!-- Metrics Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small fw-semibold">Tỷ lệ ca đủ người</div>
            <div class="h3 fw-bold text-success mt-1 mb-0"><?= $metrics['sufficient_rate'] ?? 0 ?>%</div>
            <small class="text-muted"><?= $metrics['sufficient_shifts'] ?? 0 ?> / <?= $metrics['total_shifts'] ?? 0 ?> ca đạt tối thiểu</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small fw-semibold">Tổng lượt người-ca</div>
            <div class="h3 fw-bold text-primary mt-1 mb-0"><?= $metrics['total_person_shifts'] ?? 0 ?></div>
            <small class="text-muted">TNV tham gia: <?= $metrics['active_volunteers_used'] ?? 0 ?> người</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small fw-semibold">Vi phạm ràng buộc</div>
            <div class="h3 fw-bold <?= ($metrics['violations_count'] ?? 0) === 0 ? 'text-success' : 'text-danger' ?> mt-1 mb-0">
                <?= $metrics['violations_count'] ?? 0 ?>
            </div>
            <small class="text-muted">Mục tiêu: 0 vi phạm</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small fw-semibold">Độ lệch số ca (Chênh lệch)</div>
            <div class="h3 fw-bold text-warning mt-1 mb-0"><?= $metrics['shift_deviation'] ?? 0 ?></div>
            <small class="text-muted">Max: <?= $metrics['max_shifts_per_user'] ?? 0 ?> - Min: <?= $metrics['min_shifts_per_user'] ?? 0 ?></small>
        </div>
    </div>
</div>

<!-- Shift by Shift Details -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <h2 class="h5 fw-bold mb-3"><i class="bi bi-list-columns-reverse text-primary me-2"></i>Chi Tiết Kết Quả Từng Ca Trực</h2>

    <div class="table-responsive">
        <table class="table table-hover align-middle table-custom mb-0">
            <thead>
                <tr>
                    <th>Ca trực</th>
                    <th>Thời gian</th>
                    <th>Chỉ tiêu / Đã xếp</th>
                    <th>Đơn chờ duyệt</th>
                    <th>Tình trạng</th>
                    <th>Tình nguyện viên được phân công</th>
                    <th>Nguyên nhân nếu thiếu</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($run['items'] as $item): ?>
                    <tr class="<?= !$item['is_sufficient'] ? 'table-warning' : '' ?>">
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($item['shift_name']) ?></div>
                        </td>
                        <td>
                            <div><?= date('d/m/Y', strtotime($item['shift_date'])) ?></div>
                            <small class="text-muted"><?= substr($item['time_start'], 0, 5) ?> - <?= substr($item['time_end'], 0, 5) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?= (int)$item['assigned_count'] ?> / <?= (int)$item['max_volunteers'] ?>
                            </span>
                            <div class="small text-muted mt-1">Cần min: <?= (int)$item['required_min'] ?></div>
                        </td>
                        <td>
                            <?php $pendingCount = (int)($pendingRegistrationCounts[(int)$item['shift_id']] ?? 0); ?>
                            <?php if ($pendingCount > 0): ?>
                                <a href="<?= $baseUrl ?>/coordinator/registrations?status=pending" class="badge bg-warning-soft text-dark text-decoration-none">
                                    <?= $pendingCount ?> đơn
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['is_sufficient']): ?>
                                <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i> Đủ người</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Thiếu <?= (int)$item['shortage'] ?> người</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (empty($item['users_list'])): ?>
                                <span class="text-muted small">Chưa có ai</span>
                            <?php else: ?>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($item['users_list'] as $u): ?>
                                        <span class="badge bg-white text-dark border" title="<?= htmlspecialchars($u['email']) ?>">
                                            <i class="bi bi-person text-success me-1"></i><?= htmlspecialchars($u['full_name']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($item['unassigned_users_list'])): ?>
                                <details class="small mt-2">
                                    <summary class="text-warning-emphasis fw-semibold">
                                        <?= count($item['unassigned_users_list']) ?> người đã duyệt chưa được xếp
                                    </summary>
                                    <ul class="ps-3 mt-2 mb-0">
                                        <?php foreach ($item['unassigned_users_list'] as $volunteer): ?>
                                            <li class="mb-1">
                                                <?= htmlspecialchars($volunteer['full_name'], ENT_QUOTES, 'UTF-8') ?>:
                                                <?= htmlspecialchars($volunteer['reason'], ENT_QUOTES, 'UTF-8') ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <?php if (!$item['is_sufficient']): ?>
                                <span class="text-danger"><i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($item['reason'] ?? 'Thiếu ứng viên thỏa mãn điều kiện') ?></span>
                            <?php else: ?>
                                <span class="text-success"><i class="bi bi-check2"></i> Đạt yêu cầu</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
