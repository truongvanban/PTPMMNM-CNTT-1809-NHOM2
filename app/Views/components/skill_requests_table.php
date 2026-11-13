<?php
$skillRequestStatus = [
    'pending' => ['Đang chờ duyệt', 'warning'],
    'approved' => ['Đã duyệt', 'success'],
    'rejected' => ['Đã từ chối', 'danger'],
];
?>
<?php if (empty($skillRequests)): ?>
    <div class="text-center text-muted py-4">
        <i class="bi bi-inbox fs-2"></i>
        <p class="mb-0 mt-2">Chưa có yêu cầu kỹ năng nào.</p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Tình nguyện viên</th>
                    <th>Kỹ năng</th>
                    <th>Bằng chứng</th>
                    <th>Trạng thái</th>
                    <th style="min-width: 260px;">Xử lý</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($skillRequests as $request): ?>
                    <?php [$requestStatusLabel, $requestStatusColor] = $skillRequestStatus[$request['status']] ?? ['Không xác định', 'secondary']; ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($request['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small class="d-block text-muted"><?= htmlspecialchars($request['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td class="fw-semibold"><?= htmlspecialchars($request['skill_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small" style="min-width: 220px; max-width: 360px;">
                            <?= nl2br(htmlspecialchars($request['proof'], ENT_QUOTES, 'UTF-8')) ?>
                            <?php if ($request['status'] === 'rejected' && !empty($request['admin_note'])): ?>
                                <div class="text-danger mt-2"><strong>Lý do:</strong> <?= nl2br(htmlspecialchars($request['admin_note'], ENT_QUOTES, 'UTF-8')) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge text-bg-<?= $requestStatusColor ?>"><?= $requestStatusLabel ?></span></td>
                        <td>
                            <?php if ($request['status'] === 'pending'): ?>
                                <div class="d-flex flex-column gap-2">
                                    <form action="<?= $baseUrl . $requestActionBase ?>/<?= (int)$request['id'] ?>/approve" method="POST">
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Danh mục</label>
                                            <select class="form-select form-select-sm" name="category">
                                                <option value="" <?= empty($request['category']) ? 'selected' : '' ?>><?= htmlspecialchars($request['category'] ?: '-- Giữ mặc định --', ENT_QUOTES, 'UTF-8') ?></option>
                                                <option value="Y tế" <?= ($request['category'] ?? '') === 'Y tế' ? 'selected' : '' ?>>Y tế</option>
                                                <option value="Ngoại ngữ" <?= ($request['category'] ?? '') === 'Ngoại ngữ' ? 'selected' : '' ?>>Ngoại ngữ</option>
                                                <option value="Truyền thông" <?= ($request['category'] ?? '') === 'Truyền thông' ? 'selected' : '' ?>>Truyền thông</option>
                                                <option value="Kỹ năng mềm" <?= ($request['category'] ?? '') === 'Kỹ năng mềm' ? 'selected' : '' ?>>Kỹ năng mềm</option>
                                                <option value="Kỹ năng cứng" <?= ($request['category'] ?? '') === 'Kỹ năng cứng' ? 'selected' : '' ?>>Kỹ năng cứng</option>
                                                <option value="Khác" <?= ($request['category'] ?? '') === 'Khác' ? 'selected' : '' ?>>Khác</option>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Trình độ</label>
                                            <select class="form-select form-select-sm" name="level">
                                                <option value="" <?= empty($request['level']) ? 'selected' : '' ?>><?= htmlspecialchars($request['level'] ?: '-- Giữ mặc định --', ENT_QUOTES, 'UTF-8') ?></option>
                                                <option value="beginner" <?= ($request['level'] ?? '') === 'beginner' ? 'selected' : '' ?>>Cơ bản</option>
                                                <option value="intermediate" <?= ($request['level'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Trung bình</option>
                                                <option value="advanced" <?= ($request['level'] ?? '') === 'advanced' ? 'selected' : '' ?>>Thành thạo</option>
                                            </select>
                                        </div>
                                        <button class="btn btn-sm btn-success w-100" type="submit">
                                            <i class="bi bi-check-lg me-1"></i>Duyệt và thêm kỹ năng
                                        </button>
                                    </form>
                                    <form action="<?= $baseUrl . $requestActionBase ?>/<?= (int)$request['id'] ?>/reject" method="POST">
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <textarea class="form-control form-control-sm mb-1" name="admin_note" rows="2" maxlength="2000"
                                                  placeholder="Lý do từ chối" required></textarea>
                                        <button class="btn btn-sm btn-outline-danger w-100" type="submit">Từ chối yêu cầu</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">Đã xử lý</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
