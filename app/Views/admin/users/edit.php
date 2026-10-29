<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="mb-4">
        <h1 class="h4 fw-bold mb-1">Chỉnh Sửa Tài Khoản</h1>
        <p class="text-muted small mb-0">Cập nhật thông tin cơ bản của <?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?>.</p>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form action="<?= $baseUrl ?>/admin/users/<?= (int)$user['id'] ?>/edit" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="mb-3">
            <label for="full_name" class="form-label">Họ và tên</label>
            <input id="full_name" name="full_name" class="form-control" maxlength="200" required
                   value="<?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" class="form-control" maxlength="191" required
                   value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="mb-4">
            <label for="phone" class="form-label">Số điện thoại</label>
            <input id="phone" name="phone" type="tel" class="form-control" maxlength="20"
                   value="<?= htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            <a href="<?= $baseUrl ?>/admin/users" class="btn btn-outline-secondary">Quay lại</a>
        </div>
    </form>
</div>
