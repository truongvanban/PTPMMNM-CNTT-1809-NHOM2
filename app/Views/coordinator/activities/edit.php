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
?>
<section class="activity-v2-edit">
    <!-- ===== PAGE HEADER ===== -->
    <header class="activity-v2-edit-header">
        <div class="activity-v2-edit-header-inner">
            <a href="<?= $baseUrl ?>/coordinator/activities" class="activity-v2-edit-back">
                <i class="bi bi-arrow-left me-2"></i>Quay lại danh sách
            </a>
            <div class="activity-v2-edit-head-content">
                <h1 class="activity-v2-edit-title">Chỉnh Sửa Hoạt Động</h1>
                <p class="activity-v2-edit-subtitle">Cập nhật thông tin hoặc thay đổi trạng thái của hoạt động</p>
            </div>
            <span class="activity-v2-edit-status-badge <?= $statusColors[$actStatus] ?? 'is-slate' ?>">
                <span class="activity-v2-status-dot"></span><?= htmlspecialchars($statusLabels[$actStatus] ?? $actStatus, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
    </header>

    <!-- ===== FORM ===== -->
    <form action="<?= $baseUrl ?>/coordinator/activities/<?= (int)$activity['id'] ?>/edit" method="POST" class="activity-v2-edit-form">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <div class="activity-v2-edit-grid">
            <!-- Cột trái: thông tin cơ bản + thời gian -->
            <div class="activity-v2-edit-cols">
                <div class="activity-v2-edit-panel">
                    <h2 class="activity-v2-panel-title"><i class="bi bi-info-circle-fill me-2"></i>Thông tin cơ bản</h2>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên hoạt động <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($activity['title'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Nhập tên hoạt động..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Địa điểm tổ chức</label>
                        <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($activity['location'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Nhập địa điểm...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số lượng tình nguyện viên tối đa</label>
                        <input type="number" class="form-control" id="max_volunteers" name="max_volunteers" value="<?= (int)($activity['max_volunteers'] ?? 0) ?>" min="0" placeholder="0 = không giới hạn">
                        <small class="form-text text-muted">Để 0 nếu không giới hạn số lượng đăng ký.</small>
                    </div>
                </div>

                <div class="activity-v2-edit-panel">
                    <h2 class="activity-v2-panel-title"><i class="bi bi-calendar-event-fill me-2"></i>Thời gian &amp; Hạn chót</h2>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Thời gian bắt đầu <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="date_start" name="date_start" value="<?= date('Y-m-d\TH:i', strtotime($activity['date_start'])) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Thời gian kết thúc <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="date_end" name="date_end" value="<?= date('Y-m-d\TH:i', strtotime($activity['date_end'])) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hạn chót đăng ký</label>
                        <input type="datetime-local" class="form-control" id="registration_deadline" name="registration_deadline" value="<?= !empty($activity['registration_deadline']) ? date('Y-m-d\TH:i', strtotime($activity['registration_deadline'])) : '' ?>">
                        <small class="form-text text-muted">Sau hạn chót, tình nguyện viên không thể đăng ký mới.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select class="form-select" id="status" name="status">
                            <?php foreach ($statusLabels as $statusValue => $statusLabel): ?>
                                <option value="<?= htmlspecialchars($statusValue, ENT_QUOTES, 'UTF-8') ?>" <?= $actStatus === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Cột phải: mô tả chi tiết -->
            <div class="activity-v2-edit-description">
                <h2 class="activity-v2-panel-title"><i class="bi bi-text-paragraph me-2"></i>Mô tả chi tiết</h2>
                <textarea class="form-control" id="description" name="description" rows="6" placeholder="Mô tả chi tiết về hoạt động..."><?= htmlspecialchars($activity['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <small class="form-text text-muted">Mô tả sẽ hiển thị cho tình nguyện viên khi xem chi tiết hoạt động.</small>
            </div>
        </div>

        <!-- ===== ACTIONS ===== -->
        <div class="activity-v2-edit-actions">
            <button type="submit" class="btn btn-primary activity-v2-edit-save">
                <i class="bi bi-save me-2"></i>Lưu thay đổi
            </button>
            <a href="<?= $baseUrl ?>/coordinator/activities" class="btn btn-outline-secondary activity-v2-edit-cancel">
                Quay lại
            </a>
        </div>
    </form>
</section>
