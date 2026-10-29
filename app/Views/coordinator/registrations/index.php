<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">Quản Lý Đơn Đăng Ký Ca Trực</h1>
            <p class="text-muted small mb-0">Xem và phê duyệt các đơn đăng ký tham gia từ tình nguyện viên</p>
        </div>
    </div>

    <!-- Filter by status -->
    <div class="d-flex gap-2 mb-3">
        <a href="<?= $baseUrl ?>/coordinator/registrations?per_page=<?= $perPage === 0 ? 'all' : (int)$perPage ?>" class="btn btn-sm <?= empty($status) ? 'btn-primary' : 'btn-outline-secondary' ?>">Tất cả</a>
        <a href="<?= $baseUrl ?>/coordinator/registrations?<?= http_build_query(['status' => 'approved', 'per_page' => $perPage === 0 ? 'all' : $perPage]) ?>" class="btn btn-sm <?= $status === 'approved' ? 'btn-success' : 'btn-outline-secondary' ?>">Đã duyệt</a>
        <a href="<?= $baseUrl ?>/coordinator/registrations?<?= http_build_query(['status' => 'pending', 'per_page' => $perPage === 0 ? 'all' : $perPage]) ?>" class="btn btn-sm <?= $status === 'pending' ? 'btn-warning text-dark' : 'btn-outline-secondary' ?>">Chờ duyệt</a>
        <a href="<?= $baseUrl ?>/coordinator/registrations?<?= http_build_query(['status' => 'rejected', 'per_page' => $perPage === 0 ? 'all' : $perPage]) ?>" class="btn btn-sm <?= $status === 'rejected' ? 'btn-danger' : 'btn-outline-secondary' ?>">Từ chối</a>
    </div>

    <?php if (empty($registrations)): ?>
        <div class="p-5 text-center text-muted">
            <i class="bi bi-inbox fs-1"></i>
            <h2 class="h5 mt-2">Chưa có đơn đăng ký nào</h2>
            <p class="small">Các đơn đăng ký từ tình nguyện viên sẽ xuất hiện ở đây.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-custom mb-0">
                <thead>
                    <tr>
                        <th>Tình nguyện viên</th>
                        <th>Thông tin liên hệ</th>
                        <th>Ca & Hoạt động</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $r): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($r['volunteer_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($r['volunteer_email']) ?></small>
                            </td>
                            <td>
                                <div><i class="bi bi-telephone me-1 text-success"></i><?= htmlspecialchars($r['phone'] ?? '-') ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($r['shift_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($r['activity_title']) ?></small>
                            </td>
                            <td>
                                <div><?= date('d/m/Y', strtotime($r['shift_date'])) ?></div>
                                <small class="text-muted"><?= substr($r['time_start'], 0, 5) ?> - <?= substr($r['time_end'], 0, 5) ?></small>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'approved'): ?>
                                    <span class="badge bg-success-soft text-success">Đã duyệt</span>
                                <?php elseif ($r['status'] === 'pending'): ?>
                                    <span class="badge bg-warning-soft text-warning">Chờ duyệt</span>
                                <?php elseif ($r['status'] === 'withdrawn'): ?>
                                    <span class="badge bg-secondary">Đã rút</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-soft text-danger"><?= htmlspecialchars($r['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($r['status'] === 'pending'): ?>
                                    <div class="btn-group btn-group-sm">
                                        <form action="<?= $baseUrl ?>/coordinator/registrations/approve" method="POST" class="d-inline">
                                            <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="btn btn-outline-success" title="Duyệt">
                                                <i class="bi bi-check-lg"></i> Duyệt
                                            </button>
                                        </form>
                                        <form action="<?= $baseUrl ?>/coordinator/registrations/reject" method="POST" class="d-inline">
                                            <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Từ chối">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php
    $pagination = [
        'url' => $baseUrl . '/coordinator/registrations',
        'params' => ['status' => $status],
        'current_page' => $page,
        'last_page' => $lastPage,
        'total' => $total,
        'per_page' => $perPage,
        'visible_count' => count($registrations),
        'item_label' => 'đơn đăng ký',
    ];
    require VIEW_PATH . '/components/pagination.php';
    ?>
</div>
