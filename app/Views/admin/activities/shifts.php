<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<section class="admin-activity-shifts">
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <a href="<?= $baseUrl ?>/admin/activities" class="btn btn-outline-secondary btn-sm mb-3">
                    <i class="bi bi-arrow-left me-1"></i> Danh sách hoạt động
                </a>
                <div class="text-muted small">Hoạt động · Điều phối viên: <?= htmlspecialchars($activity['coordinator_name'], ENT_QUOTES, 'UTF-8') ?></div>
                <h1 class="h4 fw-bold mt-1 mb-1">Danh sách ca trực</h1>
                <p class="text-muted mb-0"><?= htmlspecialchars($activity['title'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <a href="<?= $baseUrl ?>/activities/<?= (int)$activity['id'] ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-box-arrow-up-right me-1"></i> Xem hoạt động công khai
            </a>
        </div>

        <?php if (empty($shifts)): ?>
            <div class="py-5 text-center text-muted">
                <i class="bi bi-calendar-x fs-1"></i>
                <h2 class="h5 mt-2">Hoạt động chưa có ca trực</h2>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Ca trực</th>
                            <th>Ngày & giờ</th>
                            <th>Đăng ký</th>
                            <th>Phân công</th>
                            <th>Kỹ năng yêu cầu</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shifts as $shift): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($shift['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (!empty($shift['description'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($shift['description'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?= date('d/m/Y', strtotime($shift['date'])) ?></div>
                                    <small class="text-muted"><?= substr($shift['time_start'], 0, 5) ?> – <?= substr($shift['time_end'], 0, 5) ?></small>
                                </td>
                                <td>
                                    <div><?= (int)$shift['registered_count'] ?> / <?= (int)$shift['max_volunteers'] ?></div>
                                    <small class="text-muted"><?= (int)$shift['pending_count'] ?> chờ duyệt</small>
                                </td>
                                <td><?= (int)$shift['assigned_count'] ?> / <?= (int)$shift['max_volunteers'] ?></td>
                                <td>
                                    <?= !empty($shift['required_skills'])
                                        ? htmlspecialchars($shift['required_skills'], ENT_QUOTES, 'UTF-8')
                                        : '<span class="text-muted">Không yêu cầu</span>' ?>
                                </td>
                                <td>
                                    <?php
                                    $statusLabels = [
                                        'open' => ['Mở đăng ký', 'success'],
                                        'full' => ['Đã đủ', 'primary'],
                                        'closed' => ['Đã đóng', 'secondary'],
                                        'cancelled' => ['Đã hủy', 'danger'],
                                    ];
                                    [$statusLabel, $statusColor] = $statusLabels[$shift['status']] ?? [$shift['status'], 'secondary'];
                                    ?>
                                    <span class="badge text-bg-<?= $statusColor ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $pagination = [
                'url' => $baseUrl . '/admin/activities/' . (int)$activity['id'] . '/shifts',
                'params' => [],
                'current_page' => $page,
                'last_page' => $lastPage,
                'total' => $total,
                'per_page' => $perPage,
                'visible_count' => count($shifts),
                'item_label' => 'ca trực',
            ];
            require VIEW_PATH . '/components/pagination.php';
            ?>
        <?php endif; ?>
    </div>
</section>
