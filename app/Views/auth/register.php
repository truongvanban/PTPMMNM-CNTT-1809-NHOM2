<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="auth-heading auth-heading-register">
    <span class="auth-heading-kicker">Tham gia cộng đồng</span>
    <h2>Tạo tài khoản</h2>
    <p>Đăng ký tình nguyện viên và bắt đầu tạo nên những thay đổi tích cực.</p>
</div>

<form action="<?= $baseUrl ?>/register" method="POST" class="auth-form">
    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">

    <div class="auth-field">
        <label for="full_name" class="form-label">Họ và tên <span class="text-danger">*</span></label>
        <div class="auth-input-wrap">
            <i class="bi bi-person auth-input-icon"></i>
            <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Nguyễn Văn A" autocomplete="name" required>
        </div>
    </div>

    <div class="auth-field">
        <label for="email" class="form-label">Địa chỉ email <span class="text-danger">*</span></label>
        <div class="auth-input-wrap">
            <i class="bi bi-envelope auth-input-icon"></i>
            <input type="email" class="form-control" id="email" name="email" placeholder="ten@email.com" autocomplete="email" required>
        </div>
    </div>

    <div class="auth-field">
        <label for="phone" class="form-label">Số điện thoại</label>
        <div class="auth-input-wrap">
            <i class="bi bi-telephone auth-input-icon"></i>
            <input type="tel" class="form-control" id="phone" name="phone" placeholder="090 123 4567" autocomplete="tel">
        </div>
    </div>

    <div class="auth-field">
        <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
        <div class="auth-input-wrap">
            <i class="bi bi-lock auth-input-icon"></i>
            <input type="password" class="form-control" id="password" name="password" placeholder="Tối thiểu 8 ký tự" minlength="8" autocomplete="new-password" required>
        </div>
        <small class="auth-field-hint">Sử dụng ít nhất 8 ký tự để bảo vệ tài khoản.</small>
    </div>

    <div class="auth-field">
        <label for="password_confirm" class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
        <div class="auth-input-wrap">
            <i class="bi bi-shield-check auth-input-icon"></i>
            <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Nhập lại mật khẩu" autocomplete="new-password" required>
        </div>
    </div>

    <button type="submit" class="btn auth-submit w-100">
        Tạo tài khoản <i class="bi bi-arrow-right ms-2"></i>
    </button>
</form>

<div class="auth-switch">
    <p>Đã có tài khoản? <a href="<?= $baseUrl ?>/login">Đăng nhập</a></p>
</div>
