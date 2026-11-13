<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');

// Tính tổng số ca có sẵn và số ca đã đăng ký của user
$totalAvailableShifts = count($shifts);
$myRegisteredCount = 0;
foreach ($shifts as $_s) {
    if (!empty($_s['my_reg_id']) && in_array($_s['my_reg_status'], ['approved', 'pending'], true)) {
        $myRegisteredCount++;
    }
}
?>
<div class="volunteer-shift-page">

    <!-- Hero Section -->
    <div class="shift-hero">
        <div class="shift-hero-icon">
            <i class="bi bi-calendar-event-fill"></i>
        </div>
        <div class="shift-hero-content">
            <h1 class="shift-hero-title">Ca Trực Đang Mở Đăng Ký</h1>
            <p class="shift-hero-subtitle">Chọn các ca phù hợp với lịch trình và kỹ năng của bạn để đăng ký tham gia tình nguyện.</p>
        </div>
        <div class="shift-hero-stats">
            <div class="shift-stat-item">
                <span class="shift-stat-number"><?= $totalAvailableShifts ?></span>
                <span class="shift-stat-label">Ca trực mở</span>
            </div>
            <div class="shift-stat-item">
                <span class="shift-stat-number text-success"><?= $myRegisteredCount ?></span>
                <span class="shift-stat-label">Ca bạn đã đăng ký</span>
            </div>
        </div>
        <a href="<?= $baseUrl ?>/volunteer/my-registrations" class="shift-hero-cta">
            <i class="bi bi-card-checklist me-1"></i> Xem ca đã đăng ký
        </a>
    </div>

    <!-- Filter Bar -->
    <form action="<?= $baseUrl ?>/volunteer/shifts" method="GET" class="shift-filter-card">
        <div class="shift-filter-header">
            <i class="bi bi-funnel-fill text-success"></i>
            <span class="shift-filter-title">Bộ lọc & Tìm kiếm</span>
        </div>
        <div class="shift-filter-body row g-3 align-items-end">
            <div class="col-md-4">
                <label class="shift-filter-label">Tìm kiếm</label>
                <input type="text" name="q" class="shift-filter-input" placeholder="Tên ca, hoạt động, địa điểm..." value="<?= isset($q) ? htmlspecialchars($q, ENT_QUOTES, 'UTF-8') : '' ?>">
            </div>
            <div class="col-md-3">
                <label class="shift-filter-label">Hoạt động</label>
                <select name="activity_id" class="shift-filter-select">
                    <option value="">Tất cả hoạt động</option>
                    <?php foreach ($activities as $activity): ?>
                        <option value="<?= (int)$activity['id'] ?>" <?= $activityId === (int)$activity['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($activity['title'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="shift-filter-label">Kỹ năng yêu cầu</label>
                <select name="skill_id" class="shift-filter-select">
                    <option value="">Tất cả kỹ năng</option>
                    <?php foreach ($requiredSkills as $requiredSkill): ?>
                        <option value="<?= (int)$requiredSkill['id'] ?>" <?= $skillId === (int)$requiredSkill['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($requiredSkill['name'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($requiredSkill['category'])): ?>
                                (<?= htmlspecialchars($requiredSkill['category'], ENT_QUOTES, 'UTF-8') ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="shift-filter-btn flex-grow-1">
                    <i class="bi bi-search me-1"></i> Áp dụng
                </button>
                <?php if ($activityId > 0 || $skillId > 0 || !empty($q)): ?>
                    <a href="<?= $baseUrl ?>/volunteer/shifts" class="shift-filter-reset" title="Xóa bộ lọc">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- Shifts Grid -->
    <?php if (empty($shifts)): ?>
        <div class="shift-empty-state">
            <div class="shift-empty-icon">
                <i class="bi bi-calendar-x"></i>
            </div>
            <h2 class="shift-empty-title">Không tìm thấy ca trực phù hợp</h2>
            <p class="shift-empty-desc">Thử thay đổi hoạt động hoặc kỹ năng yêu cầu để xem thêm ca trực.</p>
            <?php if ($activityId > 0 || $skillId > 0 || !empty($q)): ?>
                <a href="<?= $baseUrl ?>/volunteer/shifts" class="shift-empty-btn">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Xóa tất cả bộ lọc
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="shift-grid">
            <?php foreach ($shifts as $s): ?>
                <?php
                    $shiftId = (int)$s['id'];
                    $isRegistered = !empty($s['my_reg_id']) && in_array($s['my_reg_status'], ['approved', 'pending'], true);
                    $registeredCount = (int)$s['registered_count'];
                    $assignedCount = (int)$s['assigned_count'];
                    $maxVolunteers = (int)$s['max_volunteers'];
                    $isFull = max($registeredCount, $assignedCount) >= $maxVolunteers;
                    $registrationPct = $maxVolunteers > 0 ? min(100, round((max($registeredCount, $assignedCount) / $maxVolunteers) * 100)) : 0;
                    $shiftDate = date('d/m/Y', strtotime($s['date']));
                    $shiftTime = substr($s['time_start'], 0, 5) . ' - ' . substr($s['time_end'], 0, 5);
                    $shiftLocation = htmlspecialchars($s['location'] ?: ($s['activity_location'] ?? 'Chưa rõ'), ENT_QUOTES, 'UTF-8');
                    $shiftName = htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8');
                    $activityTitle = htmlspecialchars($s['activity_title'], ENT_QUOTES, 'UTF-8');
                    $requiredSkills = htmlspecialchars($s['required_skills'] ?? '', ENT_QUOTES, 'UTF-8');
                ?>
                <div class="shift-card <?= $isRegistered ? 'shift-card-registered' : '' ?> <?= $isFull ? 'shift-card-full' : '' ?>">
                    <div class="shift-card-header">
                        <div class="shift-card-badge">
                            <i class="bi bi-flag text-success"></i> <?= $activityTitle ?>
                        </div>
                        <h3 class="shift-card-title"><?= $shiftName ?></h3>
                    </div>

                    <div class="shift-card-body">
                        <!-- Date & Time -->
                        <div class="shift-info-row">
                            <span class="shift-info-icon"><i class="bi bi-calendar3"></i></span>
                            <span class="shift-info-text"><strong>Ngày:</strong> <?= $shiftDate ?></span>
                        </div>
                        <div class="shift-info-row">
                            <span class="shift-info-icon"><i class="bi bi-clock"></i></span>
                            <span class="shift-info-text"><strong>Thời gian:</strong> <?= $shiftTime ?></span>
                        </div>
                        <div class="shift-info-row">
                            <span class="shift-info-icon"><i class="bi bi-geo-alt"></i></span>
                            <span class="shift-info-text"><strong>Địa điểm:</strong> <?= $shiftLocation ?></span>
                        </div>

                        <!-- Skills -->
                        <?php if (!empty($requiredSkills) && $requiredSkills !== 'Không yêu cầu kỹ năng cụ thể'): ?>
                            <div class="shift-info-row mt-3">
                                <span class="shift-info-icon"><i class="bi bi-star-fill text-warning"></i></span>
                                <span class="shift-info-text"><strong>Kỹ năng:</strong> <?= $requiredSkills ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Registration Progress -->
                        <div class="shift-progress-wrapper mt-4">
                            <div class="shift-progress-header d-flex justify-content-between align-items-center mb-1">
                                <span class="shift-progress-label">Đã đăng ký / Sức chứa</span>
                                <span class="shift-progress-value fw-bold">
                                    <?= max($registeredCount, $assignedCount) ?> / <?= $maxVolunteers ?>
                                </span>
                            </div>
                            <div class="shift-progress-bar">
                                <div class="shift-progress-fill <?= $isFull ? 'bg-danger' : 'bg-success' ?>" style="width: <?= $registrationPct ?>%"></div>
                            </div>
                            <small class="shift-progress-hint text-muted mt-1">
                                <?= $isFull ? 'Ca này đã đăng ký đủ số lượng.' : 'Còn ' . ($maxVolunteers - max($registeredCount, $assignedCount)) . ' chỗ trống.' ?>
                            </small>
                        </div>
                    </div>

                    <div class="shift-card-footer">
                        <?php if ($isRegistered): ?>
                            <span class="shift-badge-registered">
                                <i class="bi bi-check2-circle me-1"></i> Đã đăng ký
                            </span>
                        <?php elseif ($isFull): ?>
                            <button type="button" class="shift-btn-full disabled">
                                <i class="bi bi-people-fill me-1"></i> Đã đủ đăng ký
                            </button>
                        <?php else: ?>
                            <form action="<?= $baseUrl ?>/volunteer/register" method="POST" class="shift-register-form d-inline">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="shift_id" value="<?= $shiftId ?>">
                                <button type="submit" class="shift-btn-register">
                                    <i class="bi bi-plus-lg me-1"></i> Đăng ký ca này
                                </button>
                            </form>
                        <?php endif; ?>
                        <button type="button" class="shift-btn-detail"
                                data-bs-toggle="modal" data-bs-target="#shift-detail-<?= $shiftId ?>">
                            <i class="bi bi-info-circle me-1"></i> Chi tiết
                        </button>
                    </div>
                </div>

                <!-- Modal: Chi tiết ca trực -->
                <div class="modal fade" id="shift-detail-<?= $shiftId ?>" tabindex="-1"
                     aria-labelledby="shift-detail-label-<?= $shiftId ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-light border-0">
                                <div class="d-flex align-items-center">
                                    <div class="shift-modal-avatar bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                        <i class="bi bi-calendar-event fs-4"></i>
                                    </div>
                                    <div class="ms-3">
                                        <span class="shift-modal-activity small text-success fw-semibold"><?= $activityTitle ?></span>
                                        <h5 class="shift-modal-title mb-0"><?= $shiftName ?></h5>
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                            </div>

                            <div class="modal-body">
                                <?php if (!empty($s['description'])): ?>
                                    <div class="shift-modal-section mb-4">
                                        <h6 class="shift-modal-section-title"><i class="bi bi-info-circle me-1 text-success"></i> Mô tả ca trực</h6>
                                        <p class="shift-modal-text"><?= nl2br(htmlspecialchars($s['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($s['activity_description'])): ?>
                                    <div class="shift-modal-section mb-4">
                                        <h6 class="shift-modal-section-title"><i class="bi bi-book me-1 text-success"></i> Giới thiệu hoạt động</h6>
                                        <p class="shift-modal-text"><?= nl2br(htmlspecialchars($s['activity_description'], ENT_QUOTES, 'UTF-8')) ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="shift-modal-details">
                                    <div class="shift-detail-item">
                                        <span class="shift-detail-icon text-primary"><i class="bi bi-calendar3"></i></span>
                                        <div>
                                            <span class="shift-detail-label small text-muted d-block">Ngày</span>
                                            <span class="shift-detail-value fw-semibold"><?= $shiftDate ?></span>
                                        </div>
                                    </div>
                                    <div class="shift-detail-item">
                                        <span class="shift-detail-icon text-primary"><i class="bi bi-clock"></i></span>
                                        <div>
                                            <span class="shift-detail-label small text-muted d-block">Thời gian</span>
                                            <span class="shift-detail-value fw-semibold"><?= $shiftTime ?></span>
                                        </div>
                                    </div>
                                    <div class="shift-detail-item">
                                        <span class="shift-detail-icon text-primary"><i class="bi bi-geo-alt"></i></span>
                                        <div>
                                            <span class="shift-detail-label small text-muted d-block">Địa điểm</span>
                                            <span class="shift-detail-value fw-semibold"><?= $shiftLocation ?></span>
                                        </div>
                                    </div>
                                    <div class="shift-detail-item">
                                        <span class="shift-detail-icon text-primary"><i class="bi bi-people"></i></span>
                                        <div>
                                            <span class="shift-detail-label small text-muted d-block">Số lượng</span>
                                            <span class="shift-detail-value fw-semibold"><?= (int)$s['min_volunteers'] ?>–<?= $maxVolunteers ?> người</span>
                                        </div>
                                    </div>
                                    <div class="shift-detail-item">
                                        <span class="shift-detail-icon text-primary"><i class="bi bi-check2-circle"></i></span>
                                        <div>
                                            <span class="shift-detail-label small text-muted d-block">Đã đăng ký</span>
                                            <span class="shift-detail-value fw-semibold"><?= max($registeredCount, $assignedCount) ?> / <?= $maxVolunteers ?> người</span>
                                        </div>
                                    </div>
                                    <div class="shift-detail-item">
                                        <span class="shift-detail-icon text-primary"><i class="bi bi-star"></i></span>
                                        <div>
                                            <span class="shift-detail-label small text-muted d-block">Kỹ năng yêu cầu</span>
                                            <span class="shift-detail-value fw-semibold"><?= !empty($requiredSkills) ? $requiredSkills : 'Không yêu cầu kỹ năng cụ thể' ?></span>
                                        </div>
                                    </div>
                                    <?php if (!empty($s['registration_deadline'])): ?>
                                        <div class="shift-detail-item">
                                            <span class="shift-detail-icon text-warning"><i class="bi bi-hourglass-split"></i></span>
                                            <div>
                                                <span class="shift-detail-label small text-muted d-block">Hạn đăng ký</span>
                                                <span class="shift-detail-value fw-semibold"><?= date('H:i d/m/Y', strtotime($s['registration_deadline'])) ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="modal-footer border-0 pt-4">
                                <?php if ($isRegistered): ?>
                                    <span class="shift-badge-registered">
                                        <i class="bi bi-check2 me-1"></i> Bạn đã đăng ký ca này
                                    </span>
                                <?php elseif ($isFull): ?>
                                    <button type="button" class="shift-btn-full disabled">
                                        <i class="bi bi-people-fill me-1"></i> Đã đủ đăng ký
                                    </button>
                                <?php else: ?>
                                    <div class="shift-register-modal-card p-3 rounded-3 border">
                                        <label class="shift-modal-note-label small fw-semibold text-muted mb-2 d-block">
                                            <i class="bi bi-pencil-square me-1"></i> Ghi chú thêm (không bắt buộc)
                                        </label>
                                        <form action="<?= $baseUrl ?>/volunteer/register" method="POST" class="d-inline">
                                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="shift_id" value="<?= $shiftId ?>">
                                            <textarea name="note" class="shift-modal-note-textarea" rows="2" maxlength="500" placeholder="Thông tin thêm cho điều phối viên..."></textarea>
                                            <button type="submit" class="shift-btn-register mt-2 w-auto">
                                                <i class="bi bi-plus-lg me-1"></i> Đăng ký ca này
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php
        $pagination = array_merge($pagination ?? [], [
            'url' => $baseUrl . '/volunteer/shifts',
            'params' => [],
            'visible_count' => count($shifts),
            'item_label' => 'ca trực',
        ]);
        require VIEW_PATH . '/components/pagination.php';
        ?>
    <?php endif; ?>

</div>
