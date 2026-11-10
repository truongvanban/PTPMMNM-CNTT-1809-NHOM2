<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$roleLabels = [];
foreach ($roles as $availableRole) {
    $roleLabels[$availableRole['name']] = $availableRole['display_name'];
}
$selectedPageSize = $perPage === 0 ? 'all' : $perPage;
?>
<section class="admin-users-page">
    <header class="admin-users-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="admin-users-eyebrow">QUẢN TRỊ HỆ THỐNG</span>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-people-fill text-danger me-2"></i>Quản lý tài khoản</h1>
            <p class="text-secondary mb-0">Tìm kiếm tài khoản, cập nhật vai trò và trạng thái truy cập.</p>
        </div>
        <div>
            <button type="button" class="btn btn-success btn-sm px-3" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-person-plus-fill me-1"></i>Thêm tài khoản mới
            </button>
        </div>
    </header>

    <form action="<?= $baseUrl ?>/admin/users" method="GET" class="admin-user-filters">
        <div class="admin-user-search">
            <label class="visually-hidden" for="user-keyword">Tìm tài khoản</label>
            <i class="bi bi-search" aria-hidden="true"></i>
            <input id="user-keyword" type="search" class="form-control" name="keyword"
                   value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="Tìm tên, email hoặc số điện thoại...">
        </div>
        <select class="form-select" name="role" aria-label="Lọc theo vai trò">
            <option value="">Tất cả vai trò</option>
            <?php foreach ($roles as $availableRole): ?>
                <option value="<?= htmlspecialchars($availableRole['name'], ENT_QUOTES, 'UTF-8') ?>"
                    <?= $role === $availableRole['name'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($availableRole['display_name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select class="form-select" name="status" aria-label="Lọc theo trạng thái">
            <option value="">Tất cả trạng thái</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Hoạt động</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Ngừng hoạt động</option>
            <option value="locked" <?= $status === 'locked' ? 'selected' : '' ?>>Đã khóa</option>
        </select>
        <input type="hidden" name="sort_by" value="<?= htmlspecialchars($sortBy, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="sort_direction" value="<?= htmlspecialchars($sortDirection, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="per_page" value="<?= htmlspecialchars((string)$selectedPageSize, ENT_QUOTES, 'UTF-8') ?>">
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Lọc</button>
            <a href="<?= $baseUrl ?>/admin/users" class="btn btn-outline-secondary" title="Xóa bộ lọc" aria-label="Xóa bộ lọc">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>

    <form action="<?= $baseUrl ?>/admin/users" method="GET" class="admin-user-sort">
        <div class="admin-user-sort-heading">
            <i class="bi bi-arrow-down-up" aria-hidden="true"></i>
            <span class="fw-semibold">Sắp xếp danh sách</span>
        </div>
        <input type="hidden" name="keyword" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="role" value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="per_page" value="<?= htmlspecialchars((string)$selectedPageSize, ENT_QUOTES, 'UTF-8') ?>">
        <select class="form-select" name="sort_by" aria-label="Sắp xếp tài khoản theo">
            <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Ngày tạo</option>
            <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Họ và tên</option>
            <option value="email" <?= $sortBy === 'email' ? 'selected' : '' ?>>Email</option>
            <option value="status" <?= $sortBy === 'status' ? 'selected' : '' ?>>Trạng thái</option>
            <option value="last_login_at" <?= $sortBy === 'last_login_at' ? 'selected' : '' ?>>Lần đăng nhập</option>
        </select>
        <select class="form-select" name="sort_direction" aria-label="Thứ tự sắp xếp">
            <option value="DESC" <?= $sortDirection === 'DESC' ? 'selected' : '' ?>>Giảm dần</option>
            <option value="ASC" <?= $sortDirection === 'ASC' ? 'selected' : '' ?>>Tăng dần</option>
        </select>
        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-sort-down me-1"></i>Áp dụng sắp xếp</button>
    </form>

    <form id="bulk-delete-users" action="<?= $baseUrl ?>/admin/users/delete" method="POST"
          data-confirm="Các tài khoản đã chọn và dữ liệu liên quan sẽ bị xóa vĩnh viễn. Bạn có chắc chắn muốn tiếp tục?">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    </form>

    <section class="admin-user-table-card" aria-label="Danh sách tài khoản">
        <div class="admin-user-table-toolbar">
            <div>
                <h2 class="h5 fw-bold mb-1">Tài khoản người dùng</h2>
                <p class="small text-secondary mb-0">
                    Đã chọn <strong data-selection-count="user-selection">0</strong>
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-clear-selection="user-selection">
                    <i class="bi bi-x-circle me-1"></i>Bỏ chọn
                </button>
                <button type="submit" form="bulk-delete-users" class="btn btn-outline-danger btn-sm"
                        data-selection-submit="user-selection">
                    <i class="bi bi-trash me-1"></i>Xóa đã chọn
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0 admin-user-table">
                <thead>
                    <tr>
                        <th class="text-center admin-user-select-cell">
                            <input type="checkbox" class="form-check-input" aria-label="Chọn tất cả tài khoản trên trang"
                                   data-select-all="user-selection">
                        </th>
                        <th>Tài khoản</th>
                        <th>Liên hệ</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="py-5 text-center text-secondary">
                                <i class="bi bi-person-x fs-2 d-block mb-2"></i>Không tìm thấy tài khoản phù hợp.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($users as $u): ?>
                        <?php
                        $userId = (int)$u['id'];
                        $isCurrentAdmin = $userId === $currentAdminId;
                        $userRoleNames = array_filter(array_map('trim', explode(',', $u['role_names'] ?? '')));
                        $nextStatus = $u['status'] === 'active' ? 'locked' : 'active';
                        $statusLabel = ['active' => 'Hoạt động', 'inactive' => 'Ngừng hoạt động', 'locked' => 'Đã khóa'][$u['status']] ?? $u['status'];
                        $statusClass = ['active' => 'active', 'inactive' => 'inactive', 'locked' => 'locked'][$u['status']] ?? 'other';
                        ?>
                        <tr>
                            <td class="text-center admin-user-select-cell">
                                <?php if (!$isCurrentAdmin): ?>
                                    <input type="checkbox" class="form-check-input" name="user_ids[]"
                                           value="<?= $userId ?>" form="bulk-delete-users" data-delete-user-id="<?= $userId ?>"
                                           aria-label="Chọn tài khoản <?= htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                           data-select-item="user-selection">
                                <?php else: ?>
                                    <input type="checkbox" class="form-check-input" disabled aria-label="Không thể chọn tài khoản đang đăng nhập">
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <small class="text-secondary">Tạo <?= date('d/m/Y', strtotime($u['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="small"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                <small class="text-secondary"><?= htmlspecialchars($u['phone'] ?: 'Chưa có số điện thoại', ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td>
                                <div class="admin-user-role-list">
                                    <?php foreach ($userRoleNames as $assignedRoleName): ?>
                                        <?php
                                        $roleColorClass = $assignedRoleName === 'admin' ? 'admin' : ($assignedRoleName === 'coordinator' ? 'coordinator' : 'volunteer');
                                        ?>
                                        <div class="admin-user-role-tag <?= $roleColorClass ?>">
                                            <?= htmlspecialchars($roleLabels[$assignedRoleName] ?? $assignedRoleName, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (!$userRoleNames): ?><span class="text-secondary small">Chưa gán vai trò</span><?php endif; ?>
                                </div>
                                <?php if (!$isCurrentAdmin): ?>
                                    <?php
                                    $rolesToSet = array_filter(
                                        $roles,
                                        static fn(array $availableRole): bool => $availableRole['name'] !== 'admin'
                                    );
                                    ?>
                                    <?php if ($rolesToSet): ?>
                                        <form action="<?= $baseUrl ?>/admin/users/<?= $userId ?>/role" method="POST" class="admin-user-add-role">
                                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="replace">
                                            <label class="visually-hidden" for="set-role-<?= $userId ?>">Đổi vai trò</label>
                                            <select id="set-role-<?= $userId ?>" name="role_id" class="form-select form-select-sm"
                                                    onchange="if (this.value && confirm('Đổi vai trò? Tài khoản sẽ chỉ còn vai trò vừa chọn.')) this.form.submit(); else this.value = '';">
                                                <option value="">Đổi vai trò...</option>
                                                <?php foreach ($rolesToSet as $availableRole): ?>
                                                    <option value="<?= (int)$availableRole['id'] ?>"><?= htmlspecialchars($availableRole['display_name'], ENT_QUOTES, 'UTF-8') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="admin-user-status-cell">
                                    <span class="admin-user-status <?= $statusClass ?>"><span></span><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if (!$isCurrentAdmin): ?>
                                        <form action="<?= $baseUrl ?>/admin/users/<?= $userId ?>/status" method="POST"
                                              aria-label="Đổi trạng thái <?= htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="status" value="<?= $nextStatus ?>">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input admin-user-status-toggle" type="checkbox" role="switch"
                                                       aria-label="<?= $u['status'] === 'active' ? 'Khóa tài khoản' : 'Mở khóa tài khoản' ?>"
                                                       <?= $u['status'] === 'active' ? 'checked' : '' ?>
                                                       onchange="if (!this.checked && !confirm('Khóa tài khoản này? Người dùng sẽ không thể đăng nhập cho đến khi được mở khóa.')) { this.checked = true; return; } this.form.submit()">
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <small class="text-secondary">Tài khoản hiện tại</small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-end">
                                <a href="<?= $baseUrl ?>/admin/users/<?= $userId ?>/edit"
                                   class="btn btn-sm btn-outline-primary" aria-label="Sửa tài khoản <?= htmlspecialchars($u['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                   title="Sửa thông tin tài khoản">
                                    <i class="bi bi-pencil-square me-1"></i>Sửa
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php
        $pagination = [
            'url' => $baseUrl . '/admin/users',
            'params' => ['keyword' => $keyword, 'role' => $role, 'status' => $status, 'sort_by' => $sortBy, 'sort_direction' => $sortDirection],
            'current_page' => $page,
            'last_page' => $lastPage,
            'total' => $total,
            'per_page' => $perPage,
            'visible_count' => count($users),
            'item_label' => 'tài khoản',
        ];
        require VIEW_PATH . '/components/pagination.php';
        ?>
    </section>

    <!-- Modal Tạo Tài Khoản Mới -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <form action="<?= $baseUrl ?>/admin/users/create" method="POST">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="modal-header border-0 pb-0">
                        <h2 class="modal-title h5 fw-bold" id="createUserModalLabel">
                            <i class="bi bi-person-plus-fill text-success me-2"></i>Thêm tài khoản mới
                        </h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="mb-3">
                            <label for="new_full_name" class="form-label fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="new_full_name" name="full_name" placeholder="Nguyễn Văn A" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_email" class="form-label fw-semibold">Địa chỉ email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="new_email" name="email" placeholder="email@volunteerhub.vn" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_phone" class="form-label fw-semibold">Số điện thoại</label>
                            <input type="tel" class="form-control" id="new_phone" name="phone" placeholder="0901234567">
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-semibold">Mật khẩu ban đầu <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="password" minlength="8" placeholder="Tối thiểu 8 ký tự" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_role_id" class="form-label fw-semibold">Vai trò <span class="text-danger">*</span></label>
                            <select class="form-select" id="new_role_id" name="role_id" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= (int)$r['id'] ?>" <?= $r['name'] === 'volunteer' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['display_name']) ?> (<?= htmlspecialchars($r['name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Tạo tài khoản</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <details class="admin-user-danger-zone">
        <summary><i class="bi bi-three-dots me-2"></i>Khu vực thao tác nguy hiểm</summary>
        <div class="admin-user-danger-content">
            <div>
                <strong class="text-danger">Xóa tất cả tài khoản</strong>
                <p class="small text-secondary mb-0">Xóa vĩnh viễn toàn bộ tài khoản và dữ liệu liên quan, ngoại trừ tài khoản admin đang đăng nhập.</p>
            </div>
            <form action="<?= $baseUrl ?>/admin/users/delete-all" method="POST" class="admin-user-delete-all-form">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <label for="delete-all-confirm" class="form-label small fw-semibold mb-1">Nhập DELETE để xác nhận</label>
                <div class="d-flex gap-2">
                    <input id="delete-all-confirm" type="text" name="confirm_text" class="form-control form-control-sm"
                           autocomplete="off" placeholder="DELETE" required>
                    <button id="delete-all-submit" type="submit" class="btn btn-danger btn-sm" disabled>
                        <i class="bi bi-trash3-fill me-1"></i>Xóa tất cả
                    </button>
                </div>
            </form>
        </div>
    </details>
</section>

<script>
(() => {
    const confirmationInput = document.getElementById('delete-all-confirm');
    const deleteButton = document.getElementById('delete-all-submit');
    if (!confirmationInput || !deleteButton) return;
    confirmationInput.addEventListener('input', () => {
        deleteButton.disabled = confirmationInput.value !== 'DELETE';
    });
})();
</script>