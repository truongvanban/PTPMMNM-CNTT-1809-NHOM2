<?php
use App\Core\Session;
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$activityRegistrationOpen = in_array($activity['status'], ['open', 'ongoing'], true)
    && (empty($activity['registration_deadline']) || strtotime($activity['registration_deadline']) >= time())
    && (empty($activity['date_end']) || strtotime($activity['date_end']) >= time());
?>
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/activities">Hoạt động</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($activity['title']) ?></li>
        </ol>
    </nav>

    <!-- Activity Header Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <span class="badge <?= $activityRegistrationOpen ? 'bg-success' : 'bg-secondary' ?> px-3 py-2 fs-6">
                <?= $activityRegistrationOpen ? 'Đang mở đăng ký' : 'Đã đóng đăng ký' ?>
            </span>
            <span class="text-danger fw-semibold small">
                <i class="bi bi-hourglass-split me-1"></i> Hạn đăng ký: 
                <?= $activity['registration_deadline'] ? date('H:i d/m/Y', strtotime($activity['registration_deadline'])) : 'Không giới hạn' ?>
            </span>
        </div>
        <h1 class="h2 fw-bold text-dark mb-3"><?= htmlspecialchars($activity['title']) ?></h1>

        <?php if (!empty($activity['image_url'])): ?>
            <img src="<?= $baseUrl . htmlspecialchars($activity['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($activity['title'], ENT_QUOTES, 'UTF-8') ?>"
                 class="activity-detail-cover mb-3">
        <?php endif; ?>
        
        <div class="row g-3 text-muted mb-3">
            <div class="col-md-4">
                <i class="bi bi-calendar3 text-success me-2"></i>
                <strong>Thời gian:</strong> <?= date('d/m/Y H:i', strtotime($activity['date_start'])) ?> - <?= date('d/m/Y H:i', strtotime($activity['date_end'])) ?>
            </div>
            <div class="col-md-4">
                <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                <strong>Địa điểm:</strong> <?= htmlspecialchars($activity['location'] ?? 'Chưa xác định') ?>
            </div>
            <div class="col-md-4">
                <i class="bi bi-person-circle text-primary me-2"></i>
                <strong>Điều phối viên:</strong> <?= htmlspecialchars($activity['coordinator_name']) ?>
            </div>
            <?php if (!empty($activity['category'])): ?>
                <div class="col-md-4">
                    <i class="bi bi-heart-fill text-danger me-2"></i>
                    <strong>Danh mục:</strong> <?= htmlspecialchars($activity['category']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="border-top pt-3">
            <h2 class="h6 fw-bold text-uppercase text-secondary">Mô tả hoạt động</h2>
            <p class="text-secondary mb-0" style="white-space: pre-line;">
                <?= htmlspecialchars($activity['description'] ?? 'Chưa có mô tả chi tiết.') ?>
            </p>
        </div>

        <?php if (!empty($requiredSkills)): ?>
            <div class="border-top pt-3 mt-3">
                <h2 class="h6 fw-bold text-uppercase text-secondary mb-2">Kỹ năng cần thiết (ưu tiên)</h2>
                <div class="activity-detail-chips">
                    <?php foreach ($requiredSkills as $skill): ?>
                        <span><?= htmlspecialchars($skill['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($attachments)): ?>
            <div class="border-top pt-3 mt-3">
                <h2 class="h6 fw-bold text-uppercase text-secondary mb-2">Tài liệu đính kèm</h2>
                <ul class="activity-detail-attachments">
                    <?php foreach ($attachments as $attachment): ?>
                        <li>
                            <a href="<?= $baseUrl . htmlspecialchars($attachment['file_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                <i class="bi bi-file-earmark-arrow-down me-1"></i><?= htmlspecialchars($attachment['original_name']) ?>
                            </a>
                            <small><?= number_format((int)$attachment['file_size'] / (1024 * 1024), 2) ?> MB</small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <!-- Shifts Section -->
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 fw-bold mb-0"><i class="bi bi-clock-history text-success me-2"></i>Danh Sách Ca Trực</h2>
            <span class="text-muted small"><?= (int)($pagination['total'] ?? count($shifts)) ?> ca trực có sẵn</span>
        </div>

        <?php if (empty($shifts)): ?>
            <div class="alert alert-warning text-center py-4">
                <i class="bi bi-exclamation-circle me-1"></i> Chưa có ca trực nào được thiết lập cho hoạt động này.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Tên ca</th>
                            <th>Ngày & Giờ</th>
                            <th>Số lượng cần</th>
                            <th>Kỹ năng cần thiết</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shifts as $s): ?>
                            <?php
                            $isRegistered = !empty($s['my_reg_id']) && in_array($s['my_reg_status'], ['pending', 'approved'], true);
                            $occupiedCount = max((int)$s['assigned_count'], (int)$s['registered_count']);
                            $isFull = $occupiedCount >= (int)$s['max_volunteers'];
                            $shiftRegistrationOpen = $activityRegistrationOpen
                                && $s['activity_status'] !== 'cancelled'
                                && $s['status'] === 'open';
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($s['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($s['description'] ?? '') ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-success"><i class="bi bi-calendar-event me-1"></i><?= date('d/m/Y', strtotime($s['date'])) ?></div>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= substr($s['time_start'], 0, 5) ?> - <?= substr($s['time_end'], 0, 5) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= $occupiedCount ?> / <?= (int)$s['max_volunteers'] ?> người
                                    </span>
                                    <div class="small text-muted mt-1">Tối thiểu: <?= (int)$s['min_volunteers'] ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($s['required_skills'])): ?>
                                        <span class="badge bg-info-soft text-dark"><i class="bi bi-star-fill text-warning me-1"></i><?= htmlspecialchars($s['required_skills']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">Không yêu cầu đặc biệt</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isLogged): ?>
                                        <?php if (Session::hasRole('volunteer')): ?>
                                            <?php if ($isRegistered): ?>
                                                <span class="badge <?= $s['my_reg_status'] === 'approved' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                    <?= $s['my_reg_status'] === 'approved' ? 'Đã duyệt đơn' : 'Chờ duyệt' ?>
                                                </span>
                                            <?php elseif (!$shiftRegistrationOpen): ?>
                                                <button type="button" class="btn btn-secondary btn-sm" disabled>Đã đóng đăng ký</button>
                                            <?php elseif ($isFull): ?>
                                                <button type="button" class="btn btn-secondary btn-sm" disabled>Đã đủ số lượng</button>
                                            <?php else: ?>
                                                <form action="<?= $baseUrl ?>/volunteer/activities/register" method="POST" class="d-inline">
                                                    <input type="hidden" name="_csrf_token" value="<?= Session::generateCsrf() ?>">
                                                    <input type="hidden" name="shift_id" value="<?= $s['id'] ?>">
                                                    <input type="hidden" name="return_to" value="/activities/<?= (int)$activity['id'] ?>">
                                                    <button type="submit" class="btn btn-success btn-sm px-3">
                                                        <i class="bi bi-check2-circle me-1"></i> <?= in_array($s['my_reg_status'], ['rejected', 'withdrawn', 'cancelled'], true) ? 'Đăng ký lại' : 'Đăng ký' ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Dành cho TNV</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="<?= $baseUrl ?>/login" class="btn btn-outline-success btn-sm">
                                            Đăng nhập để đăng ký
                                        </a>
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
            'url' => $baseUrl . '/activities/' . (int)$activity['id'],
            'params' => [],
            'visible_count' => count($shifts),
            'item_label' => 'ca trực',
        ]);
        require VIEW_PATH . '/components/pagination.php';
        ?>
    </div>
</div>
