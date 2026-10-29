<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-clock-history text-secondary me-2"></i>Lịch Sử Thay Đổi Phân Công Thủ Công</h1>
            <p class="text-muted small mb-0">Nhật ký trước và sau khi thêm/gỡ người khỏi ca của Điều phối viên</p>
        </div>
        <a href="<?= $baseUrl ?>/coordinator/reports" class="btn btn-outline-secondary btn-sm">Quay lại báo cáo</a>
    </div>

    <?php if (empty($history)): ?>
        <div class="p-5 text-center text-muted">
            <i class="bi bi-journal-x fs-1"></i>
            <h2 class="h5 mt-2">Chưa có lịch sử thay đổi nào</h2>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-custom mb-0">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Người thực hiện</th>
                        <th>Ca trực</th>
                        <th>Tình nguyện viên</th>
                        <th>Hành động</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td class="small text-muted"><?= date('d/m/Y H:i:s', strtotime($h['created_at'])) ?></td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($h['changed_by_name']) ?></td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($h['shift_name']) ?></div>
                                <small class="text-muted"><?= date('d/m/Y', strtotime($h['shift_date'])) ?></small>
                            </td>
                            <td><?= htmlspecialchars($h['volunteer_name']) ?></td>
                            <td>
                                <?php if ($h['action'] === 'add'): ?>
                                    <span class="badge bg-success">Thêm vào ca</span>
                                <?php elseif ($h['action'] === 'remove'): ?>
                                    <span class="badge bg-danger">Gỡ khỏi ca</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($h['action']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($h['note'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php
    $pagination = [
        'url' => $baseUrl . '/coordinator/history',
        'params' => [],
        'current_page' => $page,
        'last_page' => $lastPage,
        'total' => $total,
        'per_page' => $perPage,
        'visible_count' => count($history),
        'item_label' => 'lịch sử',
    ];
    require VIEW_PATH . '/components/pagination.php';
    ?>
</div>
