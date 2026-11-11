<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$userInitials = mb_strtoupper(mb_substr($user['full_name'] ?? '?', 0, 1));
?>
<div class="row g-4">
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex align-items-center gap-3 pb-3 mb-4 border-bottom">
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-success text-white fw-bold flex-shrink-0"
                     style="width: 56px; height: 56px; font-size: 1.5rem;">
                    <?= htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div>
                    <h1 class="h4 fw-bold mb-1">Hồ sơ cá nhân</h1>
                    <span class="badge text-bg-success"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <form action="<?= $baseUrl ?>/<?= $section ?>/profile" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                <div class="mb-3">
                    <label for="profile-full-name" class="form-label fw-semibold">Họ và tên</label>
                    <input type="text" class="form-control" id="profile-full-name" name="full_name"
                           value="<?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                           minlength="2" maxlength="200" required>
                </div>
                <div class="mb-3">
                    <label for="profile-email" class="form-label fw-semibold">Email</label>
                    <input type="email" class="form-control bg-light" id="profile-email"
                           value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                    <small class="text-muted">Email đăng nhập hiện chưa thể thay đổi.</small>
                </div>
                <div class="mb-4">
                    <label for="profile-phone" class="form-label fw-semibold">Số điện thoại</label>
                    <input type="tel" class="form-control" id="profile-phone" name="phone"
                           value="<?= htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           maxlength="20" autocomplete="tel">
                </div>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save me-1"></i> Lưu hồ sơ
                </button>
            </form>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h2 class="h5 fw-bold mb-1"><i class="bi bi-shield-lock text-success me-2"></i>Đổi mật khẩu</h2>
            <p class="text-muted small mb-4">Xác nhận mật khẩu hiện tại để bảo vệ tài khoản.</p>
            <form action="<?= $baseUrl ?>/<?= $section ?>/profile/password" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                <div class="mb-3">
                    <label for="current-password" class="form-label fw-semibold">Mật khẩu hiện tại</label>
                    <input type="password" class="form-control" id="current-password" name="current_password"
                           autocomplete="current-password" required>
                </div>
                <div class="mb-3">
                    <label for="new-password" class="form-label fw-semibold">Mật khẩu mới</label>
                    <input type="password" class="form-control" id="new-password" name="new_password"
                           minlength="8" autocomplete="new-password" required>
                    <small class="text-muted">Tối thiểu 8 ký tự.</small>
                </div>
                <div class="mb-4">
                    <label for="confirm-password" class="form-label fw-semibold">Xác nhận mật khẩu mới</label>
                    <input type="password" class="form-control" id="confirm-password" name="confirm_password"
                           minlength="8" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn btn-outline-success">
                    <i class="bi bi-key me-1"></i> Đổi mật khẩu
                </button>
            </form>
        </div>
    </div>
</div>
