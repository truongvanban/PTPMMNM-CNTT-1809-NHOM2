<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="auth-heading">
    <span class="auth-heading-kicker">Chào mừng trở lại</span>
    <h2>Đăng nhập</h2>
    <p>Đăng nhập để tiếp tục hành trình tình nguyện của bạn.</p>
</div>

<form action="<?= $baseUrl ?>/login" method="POST" class="auth-form">
    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">

    <div class="auth-field">
        <label for="email" class="form-label">Địa chỉ email</label>
        <div class="auth-input-wrap">
            <i class="bi bi-envelope auth-input-icon"></i>
            <input type="email" class="form-control" id="email" name="email" placeholder="ten@email.com" autocomplete="email" required autofocus>
        </div>
    </div>

    <div class="auth-field">
        <label for="password" class="form-label">Mật khẩu</label>
        <div class="auth-input-wrap">
            <i class="bi bi-lock auth-input-icon"></i>
            <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" autocomplete="current-password" required>
        </div>
    </div>

    <button type="submit" class="btn auth-submit w-100">
        Đăng nhập <i class="bi bi-arrow-right ms-2"></i>
    </button>
</form>

<div class="auth-switch">
    <p>Chưa có tài khoản? <a href="<?= $baseUrl ?>/register">Đăng ký ngay</a></p>
    <p class="auth-switch-secondary">
        <i class="bi bi-compass me-1"></i> Bạn có thể <a href="<?= $baseUrl ?>/activities">khám phá hoạt động</a> trước.
    </p>
</div>
