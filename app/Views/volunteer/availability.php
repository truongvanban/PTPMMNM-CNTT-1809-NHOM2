<?php
/**
 * Khai báo lịch rảnh của tình nguyện viên - VolunteerHub Modern UI
 */
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="row g-4">
    <!-- Form Khai Báo Lịch Rảnh -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <span class="vh-section-badge">Thiết Lập Thời Gian</span>
            <h1 class="h4 fw-bold mb-1 text-dark">
                <i class="bi bi-calendar-plus text-success me-2"></i>Thêm Khung Giờ Rảnh
            </h1>
            <p class="text-muted small mb-3">
                Khai báo khung thời gian bạn có thể tham gia để thuật toán xếp ca thông minh, không bị trùng lịch học hay việc bận cá nhân.
            </p>

            <form action="<?= $baseUrl ?>/volunteer/availability" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">

                <div class="mb-3">
                    <label for="date" class="form-label fw-semibold small text-secondary">
                        <i class="bi bi-calendar-event me-1 text-success"></i> Ngày rảnh <span class="text-danger">*</span>
                    </label>
                    <input type="date" class="form-control rounded-3" id="date" name="date" required value="<?= date('Y-m-d') ?>">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label for="time_start" class="form-label fw-semibold small text-secondary">
                            <i class="bi bi-clock me-1 text-primary"></i> Từ giờ <span class="text-danger">*</span>
                        </label>
                        <input type="time" class="form-control rounded-3" id="time_start" name="time_start" required value="08:00">
                    </div>
                    <div class="col-6">
                        <label for="time_end" class="form-label fw-semibold small text-secondary">
                            <i class="bi bi-clock-history me-1 text-primary"></i> Đến giờ <span class="text-danger">*</span>
                        </label>
                        <input type="time" class="form-control rounded-3" id="time_end" name="time_end" required value="17:00">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="note" class="form-label fw-semibold small text-secondary">
                        <i class="bi bi-chat-text me-1 text-muted"></i> Ghi chú (tùy chọn)
                    </label>
                    <input type="text" class="form-control rounded-3" id="note" name="note" placeholder="VD: Rảnh cả ngày thứ 7, rảnh buổi sáng...">
                </div>

                <button type="submit" class="btn btn-success w-100 py-2 rounded-pill fw-semibold text-white shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                    <i class="bi bi-plus-circle me-1"></i> Thêm vào danh sách lịch rảnh
                </button>
            </form>
        </div>
    </div>

    <!-- Danh Sách Lịch Rảnh Đã Khai Báo -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="h4 fw-bold mb-1 text-dark">
                        <i class="bi bi-clock-history text-primary me-2"></i>Khung Giờ Rảnh Đã Khai Báo
                    </h2>
                    <p class="text-muted small mb-0">Danh sách thời gian bạn đã đăng ký rảnh trên hệ thống</p>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill font-monospace">
                    <?= count($availabilities) ?> khung giờ
                </span>
            </div>

            <?php if (empty($availabilities)): ?>
                <div class="p-5 text-center text-muted border rounded-3 bg-light">
                    <i class="bi bi-calendar-x fs-1 text-secondary opacity-50 mb-2 d-block"></i>
                    <h3 class="h6 fw-bold text-dark">Bạn chưa khai báo khung giờ rảnh nào</h3>
                    <p class="small text-muted mb-0">Thêm khung giờ rảnh ở cột bên trái để được tự động phân công ca trực phù hợp.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-custom mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ngày rảnh</th>
                                <th>Khung giờ</th>
                                <th>Ghi chú</th>
                                <th class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($availabilities as $av): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">
                                            <i class="bi bi-calendar-check text-success me-1"></i><?= date('d/m/Y', strtotime($av['date'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border rounded-pill px-2 py-1">
                                            <i class="bi bi-clock me-1 text-muted"></i><?= substr($av['time_start'], 0, 5) ?> - <?= substr($av['time_end'], 0, 5) ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($av['note'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <form action="<?= $baseUrl ?>/volunteer/availability/delete" method="POST" class="d-inline">
                                            <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= $av['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle" style="width: 32px; height: 32px; padding: 0;" data-confirm="Bạn có chắc chắn muốn xóa lịch rảnh này?" title="Xóa">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <?php
            $pagination = array_merge($pagination ?? [], [
                'url' => $baseUrl . '/volunteer/availability',
                'params' => [],
                'visible_count' => count($availabilities),
                'item_label' => 'khung giờ',
            ]);
            require VIEW_PATH . '/components/pagination.php';
            ?>
        </div>
    </div>
</div>
