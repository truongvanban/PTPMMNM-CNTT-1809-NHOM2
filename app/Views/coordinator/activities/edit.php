<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');

$actStatus = $activity['status'] ?? 'draft';

$statusLabels = [
    'draft'     => 'Bản nháp (Draft)',
    'open'      => 'Mở đăng ký (Open)',
    'closed'    => 'Đóng đăng ký (Closed)',
    'ongoing'   => 'Đang diễn ra (Ongoing)',
    'completed' => 'Đã hoàn thành (Completed)',
    'cancelled' => 'Đã hủy (Cancelled)',
];

$statusColors = [
    'draft'     => 'is-gray',
    'open'      => 'is-green',
    'closed'    => 'is-gray',
    'ongoing'   => 'is-blue',
    'completed' => 'is-purple',
    'cancelled' => 'is-red',
];

/**
 * Format a datetime-local string to a readable Vietnamese date/time.
 */
function formatActivityDateTime(?string $value, string $format = 'd/m/Y H:i'): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    $timestamp = strtotime($value);
    return $timestamp !== false ? date($format, $timestamp) : $value;
}

$title = htmlspecialchars($activity['title'] ?? 'Hoạt động chưa có tên', ENT_QUOTES, 'UTF-8');
$location = htmlspecialchars($activity['location'] ?? '', ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars($activity['description'] ?? '', ENT_QUOTES, 'UTF-8');
$dateStart = $activity['date_start'] ?? null;
$dateEnd = $activity['date_end'] ?? null;
$deadline = $activity['registration_deadline'] ?? null;
$maxVol = isset($activity['max_volunteers']) && is_numeric($activity['max_volunteers'])
    ? (int)$activity['max_volunteers']
    : 0;
$maxVolText = $maxVol > 0 ? $maxVol : 'Không giới hạn';
$hasLocation = $location !== '' && $location !== 'null';

?>
<section class="activity-v2-edit">
    <!-- ===== PAGE HEADER ===== -->
    <header class="activity-v2-edit-header">
        <div class="activity-v2-edit-header-inner">
            <a href="<?= $baseUrl ?>/coordinator/activities" class="activity-v2-edit-back">
                <i class="bi bi-arrow-left me-2"></i>Quay lại danh sách
            </a>

            <div class="activity-v2-edit-header-main">
                <div class="activity-v2-edit-header-icon">
                    <i class="bi bi-calendar-heart-fill"></i>
                </div>
                <div class="activity-v2-edit-header-content">
                    <h1 class="activity-v2-edit-title">Chỉnh Sửa Hoạt Động</h1>
                    <p class="activity-v2-edit-subtitle">Cập nhật thông tin, thời gian và trạng thái của hoạt động</p>
                </div>
            </div>

            <span class="activity-v2-edit-status-badge <?= $statusColors[$actStatus] ?? 'is-slate' ?>">
                <span class="activity-v2-status-dot"></span>
                <?= htmlspecialchars($statusLabels[$actStatus] ?? $actStatus, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
    </header>

    <!-- ===== SUMMARY STRIP ===== -->
    <div class="activity-v2-edit-summary">
        <div class="activity-v2-edit-summary-item">
            <span class="activity-v2-edit-summary-label">Tên hoạt động</span>
            <span class="activity-v2-edit-summary-value"><?= $title ?></span>
        </div>
        <div class="activity-v2-edit-summary-item">
            <span class="activity-v2-edit-summary-label">Thời gian</span>
            <span class="activity-v2-edit-summary-value">
                <strong><?= formatActivityDateTime($dateStart) ?></strong>
                <span class="text-muted">→</span>
                <strong><?= formatActivityDateTime($dateEnd) ?></strong>
            </span>
        </div>
        <div class="activity-v2-edit-summary-item">
            <span class="activity-v2-edit-summary-label">Số lượng</span>
            <span class="activity-v2-edit-summary-value"><?= $maxVolText ?></span>
        </div>
    </div>

    <?php if ($hasLocation): ?>
    <div class="activity-v2-edit-summary">
        <div class="activity-v2-edit-summary-item activity-v2-edit-summary-item--wide">
            <span class="activity-v2-edit-summary-label">Địa điểm tổ chức</span>
            <span class="activity-v2-edit-summary-value"><?= $location ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== FORM ===== -->
    <form action="<?= $baseUrl ?>/coordinator/activities/<?= (int)$activity['id'] ?>/edit" method="POST" class="activity-v2-edit-form">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <div class="activity-v2-edit-grid">
            <div class="activity-v2-edit-cols">
                <!-- Thông tin cơ bản -->
                <div class="activity-v2-edit-panel activity-v2-edit-panel--blue">
                    <h2 class="activity-v2-panel-title">
                        <i class="bi bi-card-text"></i>Thông tin cơ bản
                    </h2>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên hoạt động <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                               value="<?= $title ?>" placeholder="Nhập tên hoạt động..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Địa điểm tổ chức</label>
                        <input type="text" class="form-control" id="location" name="location"
                               value="<?= $location ?>" placeholder="Nhập địa điểm...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số lượng tình nguyện viên tối đa</label>
                        <input type="number" class="form-control" id="max_volunteers" name="max_volunteers"
                               value="<?= $maxVol ?>" min="0" placeholder="0 = không giới hạn">
                        <small class="form-text text-muted">Để 0 nếu không giới hạn số lượng đăng ký.</small>
                    </div>
                </div>

                <!-- Thời gian & Hạn chót -->
                <div class="activity-v2-edit-panel activity-v2-edit-panel--green">
                    <h2 class="activity-v2-panel-title">
                        <i class="bi bi-calendar-event"></i>Thời gian &amp; Hạn chót
                    </h2>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Thời gian bắt đầu <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="date_start" name="date_start"
                                   value="<?= $dateStart !== null ? date('Y-m-d\TH:i', strtotime($dateStart)) : '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Thời gian kết thúc <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="date_end" name="date_end"
                                   value="<?= $dateEnd !== null ? date('Y-m-d\TH:i', strtotime($dateEnd)) : '' ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hạn chót đăng ký</label>
                        <input type="datetime-local" class="form-control" id="registration_deadline" name="registration_deadline"
                               value="<?= $deadline !== null ? date('Y-m-d\TH:i', strtotime($deadline)) : '' ?>">
                        <small class="form-text text-muted">Sau hạn chót, tình nguyện viên không thể đăng ký mới.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select class="form-select" id="status" name="status">
                            <?php foreach ($statusLabels as $statusValue => $statusLabel): ?>
                                <option value="<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $actStatus === $statusValue ? 'selected' : '' ?>
                                ><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Mô tả chi tiết -->
            <div class="activity-v2-edit-description">
                <h2 class="activity-v2-panel-title">
                    <i class="bi bi-text-paragraph"></i>Mô tả chi tiết
                </h2>
                <textarea class="form-control" id="description" name="description" rows="8"
                          placeholder="Mô tả chi tiết về hoạt động..."><?= $description ?></textarea>
                <small class="form-text text-muted">Mô tả sẽ hiển thị cho tình nguyện viên khi xem chi tiết hoạt động.</small>
            </div>
        </div>

        <!-- ===== ACTIONS ===== -->
        <div class="activity-v2-edit-actions">
            <button type="submit" class="activity-v2-btn activity-v2-btn--save">
                <i class="bi bi-check-circle-fill me-2"></i>Lưu thay đổi
            </button>
            <button type="button" class="activity-v2-btn activity-v2-btn--cancel" id="cancelBtn">
                <i class="bi bi-x-circle me-2"></i>Hủy
            </button>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const cancelBtn = document.getElementById('cancelBtn');
    const form = cancelBtn ? cancelBtn.closest('form') : null;
    if (!cancelBtn || !form) return;

    const hasContent = () => {
        const fields = form.querySelectorAll('input, textarea, select');
        for (const field of fields) {
            if (field.value !== '') return true;
        }
        return false;
    };

    cancelBtn.addEventListener('click', () => {
        if (hasContent()) {
            if (confirm('Bạn có chắc muốn hủy bỏ không? Những thay đổi chưa lưu sẽ bị mất.')) {
                window.location.href = <?= json_encode($baseUrl . '/coordinator/activities') ?>;
            }
        } else {
            window.location.href = <?= json_encode($baseUrl . '/coordinator/activities') ?>;
        }
    });
});
</script>
