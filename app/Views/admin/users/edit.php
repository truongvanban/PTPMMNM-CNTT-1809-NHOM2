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

    <?php
    $assignedRoleIds = array_map('intval', array_filter(explode(',', $user['role_ids'] ?? '')));
    $userId = (int)$user['id'];
    ?>
    <hr class="my-4">
    <section aria-labelledby="account-roles-title">
        <h2 id="account-roles-title" class="h5 fw-bold mb-1">Vai trò tài khoản</h2>
        <p class="text-muted small mb-3">Vai trò quản trị viên cho phép truy cập toàn bộ khu vực quản trị.</p>

        <?php if ($userId === $currentAdminId): ?>
            <div class="alert alert-info mb-0">Không thể thay đổi vai trò của tài khoản quản trị viên đang đăng nhập.</div>
        <?php else: ?>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php foreach ($roles as $role): ?>
                    <?php if (in_array((int)$role['id'], $assignedRoleIds, true)): ?>
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($role['display_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php
            $rolesToSet = array_filter(
                $roles,
                static fn(array $role): bool => $role['name'] !== 'admin'
            );
            ?>
            <?php if ($rolesToSet): ?>
                <form action="<?= $baseUrl ?>/admin/users/<?= $userId ?>/role" method="POST" class="row g-2 align-items-end">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="replace">
                    <div class="col-sm-8">
                        <label for="role_id" class="form-label small fw-semibold">Đổi vai trò (thay thế tất cả vai trò hiện tại)</label>
                        <select id="role_id" name="role_id" class="form-select" required>
                            <option value="">Chọn vai trò mới...</option>
                            <?php foreach ($rolesToSet as $role): ?>
                                <option value="<?= (int)$role['id'] ?>"><?= htmlspecialchars($role['display_name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <button type="submit" class="btn btn-outline-primary w-100"
                                data-confirm="Đổi vai trò? Tài khoản sẽ chỉ còn vai trò vừa chọn.">
                            <i class="bi bi-arrow-repeat me-1"></i>Đổi vai trò
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
