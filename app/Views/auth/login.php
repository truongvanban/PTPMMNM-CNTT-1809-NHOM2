<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="auth-heading">
    <span class="auth-heading-kicker">Chào mừng trở lại</span>
    <h2>Đăng nhập</h2>
    <p>Đăng nhập để tiếp tục hành trình tình nguyện của bạn.</p>
</div>

<!-- 1-Touch Demo Quick Login -->
<div class="demo-quick-login-wrap">
    <div class="demo-quick-login-title">
        <i class="bi bi-lightning-charge-fill text-warning"></i>
        <span>Đăng nhập nhanh (Tài khoản thử nghiệm)</span>
    </div>
    <div class="demo-quick-login-grid">
        <button type="button" class="btn-demo-quick btn-demo-admin" data-demo-email="admin@volunteerhub.vn" data-demo-pass="Admin@123456" title="Đăng nhập Quản trị viên">
            <i class="bi bi-shield-lock-fill text-danger"></i>
            <span>Admin</span>
        </button>
        <button type="button" class="btn-demo-quick btn-demo-coord" data-demo-email="coord1@volunteerhub.vn" data-demo-pass="Coord@123456" title="Đăng nhập Điều phối viên">
            <i class="bi bi-kanban-fill text-primary"></i>
            <span>Điều phối</span>
        </button>
        <button type="button" class="btn-demo-quick btn-demo-tnv" data-demo-email="tnv1@volunteerhub.vn" data-demo-pass="Tnv@123456" title="Đăng nhập Tình nguyện viên">
            <i class="bi bi-person-heart text-success"></i>
            <span>TNV 1</span>
        </button>
    </div>
</div>

<form action="<?= $baseUrl ?>/login" method="POST" class="auth-form" id="loginForm">
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
        <div class="auth-input-wrap position-relative">
            <i class="bi bi-lock auth-input-icon"></i>
            <input type="password" class="form-control pe-5" id="password" name="password" placeholder="Nhập mật khẩu" autocomplete="current-password" required>
            <button type="button" class="auth-password-toggle" id="togglePasswordBtn" aria-label="Hiện/Ẩn mật khẩu" title="Hiện/Ẩn mật khẩu">
                <i class="bi bi-eye" id="togglePasswordIcon"></i>
            </button>
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
