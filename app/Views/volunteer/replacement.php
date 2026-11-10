<?php
/**
 * Báo vắng & Xin thay người - VolunteerHub Modern UI
 */
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="row g-4">
    <!-- Form Gửi Yêu Cầu Báo Vắng -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <span class="vh-section-badge">Hỗ Trợ Đột Xuất</span>
            <h1 class="h4 fw-bold mb-1 text-dark">
                <i class="bi bi-person-x text-danger me-2"></i>Báo Vắng & Xin Thay Người
            </h1>
            <p class="text-muted small mb-3">
                Nếu bạn bận việc đột xuất không thể tham gia ca trực đã phân công, hãy gửi lý do để Điều phối viên kịp thời bố trí tình nguyện viên thay thế.
            </p>

            <form action="<?= $baseUrl ?>/volunteer/replacement" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">

                <div class="mb-3">
                    <label for="assignment_id" class="form-label fw-semibold small text-secondary">
                        <i class="bi bi-calendar-check me-1 text-primary"></i> Chọn ca trực cần báo vắng <span class="text-danger">*</span>
                    </label>
                    <select class="form-select rounded-3" id="assignment_id" name="assignment_id" required>
                        <option value="">-- Chọn ca trực của bạn --</option>
                        <?php foreach ($myAssignments as $asgn): ?>
                            <?php if (
                                in_array($asgn['assignment_status'], ['assigned', 'confirmed'], true)
                                && !in_array($asgn['attendance_status'], ['absent', 'replaced'], true)
                                && ($asgn['replacement_status'] ?? '') !== 'pending'
                            ): ?>
                                <option value="<?= $asgn['id'] ?>">
                                    <?= htmlspecialchars($asgn['shift_name']) ?> (<?= date('d/m/Y', strtotime($asgn['shift_date'])) ?>) - <?= htmlspecialchars($asgn['activity_title']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="reason" class="form-label fw-semibold small text-secondary">
                        <i class="bi bi-chat-left-dots me-1 text-danger"></i> Lý do vắng mặt cụ thể <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control rounded-3" id="reason" name="reason" rows="4" placeholder="VD: Bị sốt đột xuất, bận thi học kỳ, có lịch gia đình đột xuất..." required></textarea>
                </div>

                <button type="submit" class="btn btn-danger w-100 py-2 rounded-pill fw-semibold shadow-sm" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none;">
                    <i class="bi bi-send-fill me-1"></i> Gửi yêu cầu báo vắng ngay
                </button>
            </form>
        </div>
    </div>

    <!-- Lịch Sử Các Yêu Cầu Báo Vắng -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="h4 fw-bold mb-1 text-dark">
                        <i class="bi bi-clock-history text-secondary me-2"></i>Lịch Sử Yêu Cầu Báo Vắng
                    </h2>
                    <p class="text-muted small mb-0">Theo dõi tiến độ duyệt và người được phân công thay thế</p>
                </div>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill font-monospace">
                    <?= count($requests) ?> yêu cầu
                </span>
            </div>

            <?php if (empty($requests)): ?>
                <div class="p-5 text-center text-muted border rounded-3 bg-light">
                    <i class="bi bi-check-circle-fill fs-1 text-success opacity-50 mb-2 d-block"></i>
                    <h3 class="h6 fw-bold text-dark">Bạn chưa có yêu cầu báo vắng nào</h3>
                    <p class="small text-muted mb-0">Tuyệt vời! Bạn luôn hoàn thành đầy đủ các ca trực đã nhận.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-custom mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ca trực</th>
                                <th>Lý do</th>
                                <th>Trạng thái</th>
                                <th>TNV thay thế</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($req['shift_name']) ?></div>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($req['shift_date'])) ?> (<?= substr($req['time_start'], 0, 5) ?>)
                                        </small>
                                    </td>
                                    <td class="small text-muted" style="max-width: 200px;">
                                        <?= htmlspecialchars($req['reason']) ?>
                                    </td>
                                    <td>
                                        <?php if ($req['status'] === 'approved'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                                <i class="bi bi-check-circle-fill me-1"></i> Đã chấp thuận
                                            </span>
                                        <?php elseif ($req['status'] === 'rejected'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">
                                                <i class="bi bi-x-circle-fill me-1"></i> Bị từ chối
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1">
                                                <i class="bi bi-hourglass-split me-1"></i> Chờ duyệt
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <?php if (!empty($req['replacement_name'])): ?>
                                            <span class="fw-bold text-success d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-person-check-fill"></i><?= htmlspecialchars($req['replacement_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Đang tìm người</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <?php
            $pagination = array_merge($pagination ?? [], [
                'url' => $baseUrl . '/volunteer/replacement',
                'params' => [],
                'visible_count' => count($requests),
                'item_label' => 'yêu cầu',
            ]);
            require VIEW_PATH . '/components/pagination.php';
            ?>
        </div>
    </div>
</div>
