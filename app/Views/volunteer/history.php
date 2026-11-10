<?php
/**
 * Lịch sử hoạt động của tình nguyện viên - VolunteerHub Modern UI
 */
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <span class="vh-section-badge">Lịch Sử Ca Trực</span>
            <h1 class="h3 fw-bold mb-1 text-dark">
                <i class="bi bi-clock-history text-success me-2"></i>Lịch Sử Ca Trực
            </h1>
            <p class="text-muted small mb-0">Theo dõi các ca đã phân công và kết quả tham gia; lịch sử giao dịch điểm nằm trong mục Điểm cống hiến & Thi đua.</p>
        </div>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill font-monospace">
            <i class="bi bi-calendar2-check-fill me-1"></i> <?= count($assignments) ?> ca trực đã tham gia
        </span>
    </div>

    <?php if (empty($assignments)): ?>
        <div class="p-5 text-center text-muted border rounded-3 bg-light">
            <i class="bi bi-calendar2-x fs-1 text-secondary opacity-50 mb-2 d-block"></i>
            <h2 class="h5 fw-bold text-dark">Chưa có lịch sử hoạt động</h2>
            <p class="small text-muted mb-3">Lịch sử sẽ được tự động ghi nhận sau khi bạn được phân công và hoàn thành ca trực.</p>
            <a href="<?= $baseUrl ?>/volunteer/activities" class="btn btn-outline-success btn-sm rounded-pill px-4">
                Khám phá các hoạt động mở
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-custom mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ca trực & Hoạt động</th>
                        <th>Ngày diễn ra</th>
                        <th>Khung giờ</th>
                        <th>Địa điểm</th>
                        <th class="text-center">Kết quả tham gia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $asgn): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($asgn['shift_name']) ?></div>
                                <small class="text-success fw-semibold"><i class="bi bi-flag-fill me-1"></i><?= htmlspecialchars($asgn['activity_title']) ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><i class="bi bi-calendar3 text-primary me-1"></i><?= date('d/m/Y', strtotime($asgn['shift_date'])) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border rounded-pill px-2 py-1">
                                    <i class="bi bi-clock me-1 text-muted"></i><?= substr($asgn['time_start'], 0, 5) ?> - <?= substr($asgn['time_end'], 0, 5) ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($asgn['activity_location'] ?? 'Chưa rõ') ?>
                            </td>
                            <td class="text-center">
                                <?php if ($asgn['attendance_status'] === 'completed'): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                                        <i class="bi bi-award-fill me-1"></i> Đã hoàn thành
                                    </span>
                                <?php elseif ($asgn['attendance_status'] === 'confirmed'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i> Đã tham gia
                                    </span>
                                <?php elseif ($asgn['attendance_status'] === 'absent'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1">
                                        <i class="bi bi-x-circle-fill me-1"></i> Vắng mặt
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1">
                                        <i class="bi bi-calendar-event me-1"></i> Đã phân công
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php
    $pagination = array_merge($pagination ?? [], [
        'url' => $baseUrl . '/volunteer/history',
        'params' => [],
        'visible_count' => count($assignments),
        'item_label' => 'ca trực',
    ]);
    require VIEW_PATH . '/components/pagination.php';
    ?>
</div>
