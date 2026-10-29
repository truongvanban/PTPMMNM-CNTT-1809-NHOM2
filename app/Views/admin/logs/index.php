<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="mb-4">
        <h1 class="h4 fw-bold mb-1"><i class="bi bi-journal-text text-danger me-2"></i>Nhật Ký Hệ Thống (Audit Logs)</h1>
        <p class="text-muted small mb-0">Theo dõi lịch sử đăng nhập, thay đổi cấu hình, tạo hoạt động và phân công</p>
    </div>

    <!-- Filter form -->
    <form action="<?= $baseUrl ?>/admin/logs" method="GET" class="row g-2 mb-4">
        <div class="col-md-5">
            <input type="text" class="form-control" name="action" value="<?= htmlspecialchars($action) ?>" placeholder="Lọc theo hành động (login, register...)">
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" name="entity" value="<?= htmlspecialchars($entity) ?>" placeholder="Loại thực thể (user, activity...)">
        </div>
        <input type="hidden" name="per_page" value="<?= $perPage === 0 ? 'all' : (int)$perPage ?>">
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Lọc</button>
            <a href="<?= $baseUrl ?>/admin/logs" class="btn btn-outline-secondary btn-sm">Đặt lại</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle table-custom mb-0">
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Người dùng</th>
                    <th>Hành động</th>
                    <th>Thực thể</th>
                    <th>Chi tiết dữ liệu</th>
                    <th>IP / User-Agent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="small text-muted"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                        <td>
                            <div class="fw-bold small"><?= htmlspecialchars($log['user_name'] ?? 'Khách / Hệ thống') ?></div>
                            <small class="text-muted"><?= htmlspecialchars($log['user_email'] ?? '') ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($log['action']) ?></span>
                        </td>
                        <td class="small"><?= htmlspecialchars($log['entity_type'] ?? '-') ?> #<?= (int)$log['entity_id'] ?></td>
                        <td class="small text-muted" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <code><?= htmlspecialchars($log['new_data'] ?? $log['old_data'] ?? '-') ?></code>
                        </td>
                        <td class="small text-muted">
                            <div><?= htmlspecialchars($log['ip_address'] ?? '-') ?></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $pagination = [
        'url' => $baseUrl . '/admin/logs',
        'params' => ['action' => $action, 'entity' => $entity],
        'current_page' => $page,
        'last_page' => $lastPage,
        'total' => $total,
        'per_page' => $perPage,
        'visible_count' => count($logs),
        'item_label' => 'nhật ký',
    ];
    require VIEW_PATH . '/components/pagination.php';
    ?>
</div>
