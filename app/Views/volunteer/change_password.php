<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white" style="max-width: 520px;">
    <div class="mb-4">
        <span class="vh-section-badge">Bảo Mật Tài Khoản</span>
        <h1 class="h3 fw-bold mb-1 text-dark">
            <i class="bi bi-key-fill text-success me-2"></i>Đổi Mật Khẩu
        </h1>
        <p class="text-muted small mb-0">Để bảo vệ tài khoản, hãy sử dụng mật khẩu mạnh tối thiểu 8 ký tự</p>
    </div>

    <form action="<?= $baseUrl ?>/volunteer/change-password" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">

        <div class="mb-3">
            <label for="current_password" class="form-label fw-semibold small text-secondary">
                <i class="bi bi-lock me-1 text-muted"></i> Mật khẩu hiện tại <span class="text-danger">*</span>
            </label>
            <input type="password" class="form-control rounded-3" id="current_password" name="current_password" placeholder="Nhập mật khẩu đang dùng" required>
        </div>

        <div class="mb-3">
            <label for="new_password" class="form-label fw-semibold small text-secondary">
                <i class="bi bi-shield-lock me-1 text-primary"></i> Mật khẩu mới <span class="text-danger">*</span>
            </label>
            <input type="password" class="form-control rounded-3" id="new_password" name="new_password" minlength="8" placeholder="Tối thiểu 8 ký tự" required>
            <small class="text-muted" style="font-size: 0.72rem;">Sử dụng ít nhất 8 ký tự bao gồm chữ và số để đảm bảo an toàn.</small>
        </div>

        <div class="mb-4">
            <label for="confirm_password" class="form-label fw-semibold small text-secondary">
                <i class="bi bi-shield-check me-1 text-success"></i> Xác nhận mật khẩu mới <span class="text-danger">*</span>
            </label>
            <input type="password" class="form-control rounded-3" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu mới" required>
        </div>

        <button type="submit" class="btn btn-success w-100 py-2 rounded-pill fw-semibold text-white shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
            <i class="bi bi-shield-check me-1"></i> Cập nhật mật khẩu mới
        </button>
    </form>
</div>
