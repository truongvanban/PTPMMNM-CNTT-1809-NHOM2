<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="row g-4">
    <!-- Form Thêm Kỹ Năng Mới -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-tag-fill text-success me-2"></i>Thêm Kỹ Năng Mới</h1>
            <p class="text-muted small mb-3">Tạo danh mục kỹ năng để tình nguyện viên khai báo và ca trực yêu cầu</p>

            <form action="<?= $baseUrl ?>/admin/skills/create" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">

                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Tên kỹ năng <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="VD: Y tế sơ cứu, MC, Tiếng Anh..." required autofocus>
                </div>

                <div class="mb-3">
                    <label for="category" class="form-label fw-semibold">Nhóm danh mục</label>
                    <input type="text" class="form-control" id="category" name="category" placeholder="VD: Y tế, Ngoại ngữ, Truyền thông...">
                </div>



                <div class="mb-4">
                    <label for="description" class="form-label fw-semibold">Mô tả kỹ năng</label>
                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Mô tả tiêu chuẩn của kỹ năng này..."></textarea>
                </div>

                <button type="submit" class="btn btn-success w-100 py-2">
                    <i class="bi bi-plus-circle me-1"></i> Lưu kỹ năng mới
                </button>
            </form>
        </div>
    </div>

    <!-- Danh Sách Kỹ Năng Hiện Có -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h2 class="h4 fw-bold mb-1"><i class="bi bi-tags text-primary me-2"></i>Danh Mục Kỹ Năng Hệ Thống (<?= (int)$total ?>)</h2>
            <p class="text-muted small mb-3">Tất cả các kỹ năng đang được áp dụng trong VolunteerHub</p>

            <div class="table-responsive">
                <table class="table table-hover align-middle table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Tên kỹ năng</th>
                            <th>Danh mục</th>
                            <th>Mô tả</th>
                            <th>Trạng thái</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($skills as $sk): ?>
                            <tr>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($sk['name']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($sk['category'] ?? 'Chung') ?></span>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($sk['description'] ?? '-') ?></td>
                                <td>
                                    <?php if ($sk['is_active']): ?>
                                        <span class="badge bg-success-soft text-success">Đang dùng</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-soft text-secondary">Ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSkillModal<?= $sk['id'] ?>" title="Chỉnh sửa kỹ năng">
                                            <i class="bi bi-pencil"></i> Sửa
                                        </button>
                                        <form action="<?= $baseUrl ?>/admin/skills/<?= $sk['id'] ?>/delete" method="POST" class="d-inline">
                                            <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                            <button type="submit" class="btn btn-outline-danger" data-confirm="Bạn có chắc chắn muốn xóa kỹ năng này?">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Modal Sửa Kỹ Năng -->
                                    <div class="modal fade text-start" id="editSkillModal<?= $sk['id'] ?>" tabindex="-1" aria-labelledby="editSkillLabel<?= $sk['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <form action="<?= $baseUrl ?>/admin/skills/<?= $sk['id'] ?>/edit" method="POST">
                                                    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                                    <div class="modal-header border-0 pb-0">
                                                        <h3 class="modal-title h5 fw-bold" id="editSkillLabel<?= $sk['id'] ?>">
                                                            <i class="bi bi-pencil-square text-primary me-2"></i>Chỉnh sửa kỹ năng
                                                        </h3>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                                                    </div>
                                                    <div class="modal-body pt-3">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Tên kỹ năng <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($sk['name']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Nhóm danh mục</label>
                                                            <input type="text" class="form-control" name="category" value="<?= htmlspecialchars($sk['category'] ?? '') ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Mô tả kỹ năng</label>
                                                            <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($sk['description'] ?? '') ?></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Trạng thái áp dụng</label>
                                                            <select class="form-select" name="is_active">
                                                                <option value="1" <?= $sk['is_active'] ? 'selected' : '' ?>>Đang áp dụng (Hiển thị)</option>
                                                                <option value="0" <?= !$sk['is_active'] ? 'selected' : '' ?>>Tạm ẩn (Không áp dụng)</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                                                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Lưu thay đổi</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            $pagination = [
                'url' => $baseUrl . '/admin/skills',
                'params' => [],
                'current_page' => $page,
                'last_page' => $lastPage,
                'total' => $total,
                'per_page' => $perPage,
                'visible_count' => count($skills),
                'item_label' => 'kỹ năng',
            ];
            require VIEW_PATH . '/components/pagination.php';
            ?>
        </div>
    </div>
</div>

<section class="card border-0 shadow-sm rounded-4 p-4 mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h4 fw-bold mb-1"><i class="bi bi-inbox text-primary me-2"></i>Yêu cầu bổ sung kỹ năng (<?= (int)$requestsTotal ?>)</h2>
            <p class="text-muted small mb-0">Các yêu cầu đang chờ được ưu tiên hiển thị.</p>
        </div>
        <span class="badge text-bg-warning"><?= (int)$pendingSkillRequestCount ?> chờ duyệt</span>
    </div>
    <?php
    $requestActionBase = '/admin/skills/requests';
    require VIEW_PATH . '/components/skill_requests_table.php';
    $pagination = [
        'url' => $baseUrl . '/admin/skills',
        'params' => [],
        'page_param' => 'requests_page',
        'size_id' => 'skill-requests-page-size',
        'current_page' => $requestsPage,
        'last_page' => $requestsLastPage,
        'total' => $requestsTotal,
        'per_page' => $requestPerPage,
        'visible_count' => count($skillRequests),
        'item_label' => 'yêu cầu kỹ năng',
    ];
    require VIEW_PATH . '/components/pagination.php';
    ?>
</section>
