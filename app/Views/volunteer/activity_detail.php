<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$activityRegistrationOpen = in_array($activity['status'], ['open', 'ongoing'], true)
    && (empty($activity['registration_deadline']) || strtotime($activity['registration_deadline']) >= time())
    && (empty($activity['date_end']) || strtotime($activity['date_end']) >= time());
?>
<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/volunteer/dashboard" class="text-decoration-none">Tổng quan</a></li>
        <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/volunteer/activities" class="text-decoration-none">Khám phá hoạt động</a></li>
        <li class="breadcrumb-item active text-truncate" style="max-width: 300px;" aria-current="page"><?= htmlspecialchars($activity['title']) ?></li>
    </ol>
</nav>

<!-- Activity Overview Card -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <!-- Header Banner -->
    <div class="p-4 text-white position-relative" style="background: linear-gradient(135deg, #198754 0%, #20c997 100%);">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <span class="badge <?= $activityRegistrationOpen ? 'bg-white text-success' : 'bg-secondary text-white' ?> px-3 py-2 fw-semibold shadow-sm">
                <i class="bi bi-broadcast me-1"></i> <?= $activityRegistrationOpen ? 'Đang mở đăng ký' : 'Đã đóng đăng ký' ?>
            </span>
            <?php if (!empty($activity['registration_deadline'])): ?>
                <span class="badge bg-dark bg-opacity-25 text-white px-3 py-2">
                    <i class="bi bi-clock-history me-1"></i> Hạn chót: <?= date('H:i d/m/Y', strtotime($activity['registration_deadline'])) ?>
                </span>
            <?php endif; ?>
        </div>
        <h1 class="h2 fw-bold text-white mb-2"><?= htmlspecialchars($activity['title']) ?></h1>
        <p class="text-white-50 mb-0 small">
            <i class="bi bi-person-badge me-1"></i> Điều phối viên phụ trách: <strong><?= htmlspecialchars($activity['coordinator_name']) ?></strong> (<?= htmlspecialchars($activity['coordinator_email']) ?>)
        </p>
        <?php if (!empty($activity['category'])): ?>
            <span class="badge bg-white bg-opacity-25 text-white mt-3"><?= htmlspecialchars($activity['category']) ?></span>
        <?php endif; ?>
    </div>

    <!-- Details Body -->
    <div class="card-body p-4">
        <?php if (!empty($activity['image_url'])): ?>
            <img src="<?= $baseUrl . htmlspecialchars($activity['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($activity['title'], ENT_QUOTES, 'UTF-8') ?>"
                 class="activity-detail-cover mb-4">
        <?php endif; ?>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="p-3 rounded-3 bg-light h-100">
                    <div class="text-muted small mb-1"><i class="bi bi-calendar-range text-success me-1"></i> Thời gian diễn ra</div>
                    <div class="fw-bold text-dark">
                        <?= date('d/m/Y', strtotime($activity['date_start'])) ?>
                        <?php if (date('Y-m-d', strtotime($activity['date_start'])) !== date('Y-m-d', strtotime($activity['date_end']))): ?>
                            - <?= date('d/m/Y', strtotime($activity['date_end'])) ?>
                        <?php endif; ?>
                    </div>
                    <div class="small text-muted"><?= date('H:i', strtotime($activity['date_start'])) ?> - <?= date('H:i', strtotime($activity['date_end'])) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3 bg-light h-100">
                    <div class="text-muted small mb-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Địa điểm tổ chức</div>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($activity['location'] ?? 'Chưa xác định') ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3 bg-light h-100">
                    <div class="text-muted small mb-1"><i class="bi bi-people-fill text-primary me-1"></i> Quy mô dự kiến</div>
                    <div class="fw-bold text-dark">
                        <?= !empty($activity['max_volunteers']) ? (int)$activity['max_volunteers'] . ' tình nguyện viên' : 'Không giới hạn' ?>
                    </div>
                    <div class="small text-muted"><?= (int)($pagination['total'] ?? count($shifts)) ?> ca trực có sẵn</div>
                </div>
            </div>

        </div>

        <div class="border-top pt-3">
            <h2 class="h6 fw-bold text-uppercase text-secondary mb-2">Mô tả hoạt động</h2>
            <div class="text-secondary" style="white-space: pre-line; line-height: 1.6;">
                <?= htmlspecialchars($activity['description'] ?? 'Chưa có thông tin mô tả chi tiết.') ?>
            </div>
        </div>

        <?php if (!empty($requiredSkills)): ?>
            <div class="border-top pt-3 mt-3">
                <h2 class="h6 fw-bold text-uppercase text-secondary mb-2">Kỹ năng yêu cầu</h2>
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
</div>

<!-- Shifts Section -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1"><i class="bi bi-clock-history text-success me-2"></i>Danh Sách Ca Trực Có Sẵn</h2>
        <p class="text-muted small mb-0">Chọn ca trực phù hợp với thời gian và kỹ năng của bạn để đăng ký tham gia</p>
    </div>
</div>

<?php if (empty($shifts)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted mb-4">
        <i class="bi bi-calendar-x fs-1 text-warning mb-2"></i>
        <h3 class="h5 fw-bold text-dark">Chưa có ca trực nào được mở cho hoạt động này</h3>
        <p class="small text-muted mb-0">Điều phối viên đang sắp xếp lịch trực. Vui lòng quay lại sau ít phút.</p>
    </div>
<?php else: ?>
    <div class="row g-3 mb-4">
        <?php foreach ($shifts as $s): ?>
            <?php 
                $isRegistered = !empty($s['my_reg_id']) && in_array($s['my_reg_status'], ['approved', 'pending'], true);
                $registeredCount = (int)$s['registered_count'];
                $assignedCount = (int)$s['assigned_count'];
                $maxVolunteers = (int)$s['max_volunteers'];
                $occupiedCount = max($registeredCount, $assignedCount);
                $isFull = $occupiedCount >= $maxVolunteers;
                $registrationOpen = $activityRegistrationOpen && $s['status'] === 'open';
                $percent = $maxVolunteers > 0 ? min(100, round(($occupiedCount / $maxVolunteers) * 100)) : 0;
            ?>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100 <?= $isRegistered ? 'border border-2 border-success bg-success-soft' : '' ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h3 class="h5 fw-bold text-dark mb-1"><?= htmlspecialchars($s['name']) ?></h3>
                            <div class="text-muted small">
                                <i class="bi bi-calendar-event me-1 text-primary"></i> <?= date('d/m/Y', strtotime($s['date'])) ?>
                                <span class="mx-1">•</span>
                                <i class="bi bi-clock me-1 text-success"></i> <?= substr($s['time_start'], 0, 5) ?> - <?= substr($s['time_end'], 0, 5) ?>
                            </div>
                        </div>
                        <?php if ($isRegistered): ?>
                            <?php if ($s['my_reg_status'] === 'approved'): ?>
                                <span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i> Đã duyệt đơn</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-hourglass-split me-1"></i> Chờ duyệt</span>
                            <?php endif; ?>
                        <?php elseif (!$registrationOpen): ?>
                            <span class="badge bg-secondary px-3 py-2">Đã đóng đăng ký</span>
                        <?php elseif ($isFull): ?>
                            <span class="badge bg-secondary px-3 py-2">Đã đủ số lượng</span>
                        <?php else: ?>
                            <span class="badge bg-success-soft text-success px-3 py-2">Đang tuyển</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($s['description'])): ?>
                        <p class="text-muted small mb-2"><?= htmlspecialchars($s['description']) ?></p>
                    <?php endif; ?>

                    <div class="mb-3 small">
                        <div class="text-muted mb-1">
                            <i class="bi bi-geo-alt text-danger me-1"></i> <?= htmlspecialchars($s['location'] ?? ($activity['location'] ?? 'Tại địa điểm hoạt động')) ?>
                        </div>
                        <div class="text-muted">
                            <i class="bi bi-stars text-warning me-1"></i> Yêu cầu kỹ năng:
                            <?php if (!empty($s['required_skills'])): ?>
                                <span class="badge bg-warning-soft text-dark"><?= htmlspecialchars($s['required_skills']) ?></span>
                            <?php else: ?>
                                <span class="text-muted fst-italic">Mọi tình nguyện viên đều có thể tham gia</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Progress Capacity -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between text-muted small mb-1">
                            <span>Số lượng đã đăng ký:</span>
                            <span class="fw-semibold text-dark"><?= $occupiedCount ?> / <?= $maxVolunteers ?> người</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar <?= $percent >= 100 ? 'bg-danger' : ($percent >= 75 ? 'bg-warning' : 'bg-success') ?>" 
                                 role="progressbar" style="width: <?= $percent ?>%;" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="mt-auto pt-2 border-top">
                        <?php if ($isRegistered): ?>
                            <div class="d-flex justify-content-between align-items-center">
                                <?php if ($s['my_attendance_status'] === 'completed'): ?>
                                    <span class="text-primary small fw-semibold"><i class="bi bi-award-fill me-1"></i> Ca đã được ghi nhận hoàn thành</span>
                                <?php elseif ($s['my_assignment_status'] === 'confirmed'): ?>
                                    <span class="text-success small fw-semibold"><i class="bi bi-check-circle me-1"></i> Bạn đã xác nhận tham gia ca</span>
                                <?php elseif (in_array($s['my_assignment_status'], ['assigned', 'absent'], true)): ?>
                                    <span class="text-primary small fw-semibold"><i class="bi bi-clock me-1"></i> Bạn đã được phân công, vui lòng xác nhận tham gia</span>
                                <?php elseif ($s['my_reg_status'] === 'approved'): ?>
                                    <span class="text-success small fw-semibold"><i class="bi bi-check2-circle me-1"></i> Đơn đã được duyệt, không thể hủy đăng ký</span>
                                <?php else: ?>
                                    <span class="text-success small fw-semibold"><i class="bi bi-check2-circle me-1"></i> Bạn đã đăng ký ca này</span>
                                    <form action="<?= $baseUrl ?>/volunteer/unregister" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đăng ký ca này không?');">
                                        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="shift_id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-x-circle me-1"></i> Hủy đăng ký
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php elseif (!$registrationOpen): ?>
                            <button class="btn btn-secondary btn-sm w-100" disabled>
                                <i class="bi bi-lock me-1"></i> Đã đóng đăng ký
                            </button>
                        <?php elseif ($isFull): ?>
                            <button class="btn btn-secondary btn-sm w-100" disabled>
                                <i class="bi bi-dash-circle me-1"></i> Ca trực đã đủ số lượng
                            </button>
                        <?php else: ?>
                            <form action="<?= $baseUrl ?>/volunteer/activities/register" method="POST">
                                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="shift_id" value="<?= $s['id'] ?>">
                                <label for="registration-note-<?= (int)$s['id'] ?>" class="form-label small text-muted">Ghi chú (không bắt buộc)</label>
                                <textarea id="registration-note-<?= (int)$s['id'] ?>" name="note" class="form-control form-control-sm mb-2" rows="2" maxlength="500" placeholder="Thông tin thêm cho điều phối viên"></textarea>
                                <button type="submit" class="btn btn-success btn-sm w-100 fw-semibold">
                                    <i class="bi bi-check2-circle me-1"></i> Đăng Ký Tham Gia Ca Này
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$pagination = array_merge($pagination ?? [], [
    'url' => $baseUrl . '/volunteer/activities/' . (int)$activity['id'],
    'params' => [],
    'visible_count' => count($shifts),
    'item_label' => 'ca trực',
]);
require VIEW_PATH . '/components/pagination.php';
?>

<div class="mt-4">
    <a href="<?= $baseUrl ?>/volunteer/activities" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách hoạt động
    </a>
</div>
