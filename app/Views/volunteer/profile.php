<?php
/**
 * Hồ sơ cá nhân của tình nguyện viên - VolunteerHub Modern UI
 */
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$userInitials = mb_strtoupper(mb_substr($user['full_name'] ?? 'T', 0, 1));
?>
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white" style="max-width: 850px;">
    <!-- Profile Header with Avatar -->
    <div class="d-flex align-items-center gap-3 pb-3 mb-4 border-bottom">
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm flex-shrink-0" style="width: 60px; height: 60px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); font-size: 1.8rem; font-weight: 700;">
            <?= htmlspecialchars($userInitials) ?>
        </div>
        <div>
            <h1 class="h4 fw-bold mb-1 text-dark"><?= htmlspecialchars($user['full_name']) ?></h1>
            <a class="badge text-bg-success text-decoration-none" href="<?= rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/') ?>/volunteer/points">
                <?= number_format($pointsSummary['points']) ?> điểm · <?= htmlspecialchars($pointsSummary['tier_label']) ?>
            </a>
            <p class="text-muted small mb-0">
                <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($user['email']) ?> &bull; 
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Tình nguyện viên</span>
            </p>
        </div>
    </div>

    <form action="<?= $baseUrl ?>/volunteer/profile" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">

        <div class="row g-3">
            <div class="col-md-6">
                <label for="full_name" class="form-label fw-semibold small text-secondary">
                    <i class="bi bi-person me-1 text-success"></i> Họ và Tên <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control rounded-3" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label fw-semibold small text-secondary">
                    <i class="bi bi-envelope me-1 text-muted"></i> Địa chỉ Email
                </label>
                <input type="email" class="form-control bg-light rounded-3" id="email" value="<?= htmlspecialchars($user['email']) ?>" readonly disabled>
                <small class="text-muted" style="font-size: 0.72rem;">Email là định danh tài khoản, không thể thay đổi.</small>
            </div>

            <div class="col-md-6">
                <label for="phone" class="form-label fw-semibold small text-secondary">
                    <i class="bi bi-telephone me-1 text-primary"></i> Số điện thoại
                </label>
                <input type="tel" class="form-control rounded-3" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="090 123 4567">
            </div>
            <div class="col-md-6">
                <label for="student_id" class="form-label fw-semibold small text-secondary">
                    <i class="bi bi-card-text me-1 text-info"></i> Mã số sinh viên / Định danh
                </label>
                <input type="text" class="form-control rounded-3" id="student_id" name="student_id" value="<?= htmlspecialchars($profile['student_id'] ?? '') ?>" placeholder="VD: SV202401">
            </div>

            <div class="col-md-6">
                <label for="faculty" class="form-label fw-semibold small text-secondary">
                    <i class="bi bi-building me-1 text-secondary"></i> Khoa / Đơn vị
                </label>
                <input type="text" class="form-control rounded-3" id="faculty" name="faculty" value="<?= htmlspecialchars($profile['faculty'] ?? '') ?>" placeholder="VD: Công nghệ thông tin">
            </div>
            <div class="col-md-6">
                <label for="class_name" class="form-label fw-semibold small text-secondary">
                    <i class="bi bi-people me-1 text-warning"></i> Lớp / Chi đoàn
                </label>
                <input type="text" class="form-control rounded-3" id="class_name" name="class_name" value="<?= htmlspecialchars($profile['class_name'] ?? '') ?>" placeholder="VD: K65-CNTT">
            </div>

            <div class="col-md-6">
                <label for="gender" class="form-label fw-semibold small text-secondary">
                    <i class="bi bi-gender-ambiguous me-1 text-secondary"></i> Giới tính
                </label>
                <select class="form-select rounded-3" id="gender" name="gender">
                    <option value="">-- Chọn giới tính --</option>
                    <option value="male" <?= ($profile['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Nam</option>
                    <option value="female" <?= ($profile['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Nữ</option>
                    <option value="other" <?= ($profile['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Khác</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="date_of_birth" class="form-label fw-semibold small text-secondary">
                    <i class="bi bi-calendar-event me-1 text-danger"></i> Ngày sinh
                </label>
                <input type="date" class="form-control rounded-3" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($profile['date_of_birth'] ?? '') ?>">
            </div>

            <div class="col-12">
                <label for="address" class="form-label fw-semibold small text-secondary">
                    <i class="bi bi-geo-alt me-1 text-danger"></i> Địa chỉ liên hệ
                </label>
                <input type="text" class="form-control rounded-3" id="address" name="address" value="<?= htmlspecialchars($profile['address'] ?? '') ?>" placeholder="Địa chỉ thường trú hoặc tạm trú của bạn">
            </div>

            <div class="col-12">
                <label for="bio" class="form-label fw-semibold small text-secondary">
                    <i class="bi bi-chat-quote me-1 text-success"></i> Giới thiệu bản thân / Kinh nghiệm hoạt động
                </label>
                <textarea class="form-control rounded-3" id="bio" name="bio" rows="3" placeholder="Chia sẻ đôi nét về bạn, sở thích hoặc các hoạt động thiện nguyện từng tham gia..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
            </div>

            <div class="col-12 mt-4 pt-2 border-top d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    <i class="bi bi-shield-lock text-success me-1"></i> Dữ liệu được bảo mật theo quy định nội bộ
                </span>
                <button type="submit" class="btn btn-success px-4 py-2 rounded-pill fw-semibold text-white shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                    <i class="bi bi-save me-1"></i> Lưu thông tin hồ sơ
                </button>
            </div>
        </div>
    </form>
</div>
