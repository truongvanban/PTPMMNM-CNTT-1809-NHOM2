<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$assignedCount = count($assignedList);
$minVol = (int)$shift['min_volunteers'];
$maxVol = (int)$shift['max_volunteers'];
$shiftHasEnded = strtotime($shift['date'] . ' ' . $shift['time_end']) <= time();
$scheduledHours = max(0.1, (strtotime($shift['time_end']) - strtotime($shift['time_start'])) / 3600);
?>
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/coordinator/activities/<?= $shift['activity_id'] ?>/shifts">Danh sách ca</a></li>
            <li class="breadcrumb-item active">Điều chỉnh thủ công</li>
        </ol>
    </nav>
    <h1 class="h3 fw-bold mb-0">Điều Chỉnh Phân Công: <?= htmlspecialchars($shift['name']) ?></h1>
    <small class="text-muted">Hoạt động: <strong><?= htmlspecialchars($shift['activity_title']) ?></strong> | Ngày: <?= date('d/m/Y', strtotime($shift['date'])) ?> (<?= substr($shift['time_start'], 0, 5) ?> - <?= substr($shift['time_end'], 0, 5) ?>)</small>
</div>

<!-- Shift Capacity Status -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 stat-card">
            <div class="small text-muted fw-semibold">Hiện tại / Tối đa</div>
            <div class="h3 fw-bold mt-1 mb-0 <?= $assignedCount >= $maxVol ? 'text-danger' : 'text-success' ?>">
                <?= $assignedCount ?> / <?= $maxVol ?> người
            </div>
            <small class="text-muted">Tối thiểu: <?= $minVol ?> người</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 stat-card">
            <div class="small text-muted fw-semibold">Tình trạng ca</div>
            <div class="h3 fw-bold mt-1 mb-0">
                <?php if ($assignedCount < $minVol): ?>
                    <span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Thiếu <?= $minVol - $assignedCount ?> người</span>
                <?php else: ?>
                    <span class="text-success"><i class="bi bi-check-circle me-1"></i> Đã đủ chỉ tiêu</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 stat-card">
            <div class="small text-muted fw-semibold">Kỹ năng bắt buộc của ca</div>
            <div class="mt-1">
                <?php if (empty($requiredSkills)): ?>
                    <span class="text-muted small">Không yêu cầu bắt buộc</span>
                <?php else: ?>
                    <?php foreach ($requiredSkills as $rs): ?>
                        <span class="badge <?= $rs['is_mandatory'] ? 'bg-danger-soft text-danger' : 'bg-light text-dark border' ?> me-1">
                            <?= htmlspecialchars($rs['name']) ?> <?= $rs['is_mandatory'] ? '(Bắt buộc)' : '' ?>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Danh sách đang phân công -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h2 class="h5 fw-bold mb-3"><i class="bi bi-people-fill text-primary me-2"></i>Tình Nguyện Viên Trong Ca (<?= $assignedCount ?>)</h2>

            <?php if (empty($assignedList)): ?>
                <div class="p-4 text-center text-muted">
                    <p class="small mb-0">Chưa có ai trong ca này. Hãy thêm tình nguyện viên từ bảng bên cạnh.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Họ và tên</th>
                                <th>MSSV & Liên hệ</th>
                                <th>Trạng thái</th>
                                <th class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignedList as $asgn): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($asgn['volunteer_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($asgn['volunteer_email']) ?></small>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= htmlspecialchars($asgn['student_id'] ?? '-') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($asgn['phone'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <?php if ($asgn['attendance_status'] === 'completed'): ?>
                                            <span class="badge bg-primary">Đã hoàn thành</span>
                                        <?php elseif ($asgn['attendance_status'] === 'confirmed'): ?>
                                            <span class="badge bg-success-soft text-success">Đã xác nhận</span>
                                        <?php elseif ($asgn['attendance_status'] === 'absent' || $asgn['status'] === 'absent'): ?>
                                            <span class="badge bg-danger-soft text-danger">Báo vắng</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-soft text-warning">Chờ xác nhận</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (in_array($asgn['status'], ['assigned', 'confirmed'], true) && $asgn['attendance_status'] !== 'completed'): ?>
                                            <?php if ($shiftHasEnded): ?>
                                                <form action="<?= $baseUrl ?>/coordinator/assignment/complete" method="POST" class="d-inline-flex flex-wrap justify-content-center gap-1 mb-1">
                                                    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                                    <input type="hidden" name="assignment_id" value="<?= $asgn['id'] ?>">
                                                    <input type="number" class="form-control form-control-sm" style="width: 78px" name="hours_worked" min="0.1" max="24" step="0.1" value="<?= number_format($scheduledHours, 1, '.', '') ?>" required aria-label="Số giờ thực tế">
                                                    <label class="form-check small d-inline-flex align-items-center gap-1 mb-0">
                                                        <input class="form-check-input" type="checkbox" name="on_time" value="1">
                                                        <span>Đúng giờ</span>
                                                    </label>
                                                    <button type="submit" class="btn btn-success btn-sm">Hoàn thành</button>
                                                </form>
                                                <form action="<?= $baseUrl ?>/coordinator/assignment/no-show" method="POST" class="d-inline">
                                                    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                                    <input type="hidden" name="assignment_id" value="<?= $asgn['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-warning btn-sm" data-confirm="Xác nhận tình nguyện viên vắng mặt không phép?">
                                                        Vắng không phép
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="small text-muted">Chưa kết thúc</span>
                                            <?php endif; ?>
                                            <form action="<?= $baseUrl ?>/coordinator/assignment/manual/remove" method="POST" class="d-inline">
                                                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="assignment_id" value="<?= $asgn['id'] ?>">
                                                <input type="hidden" name="shift_id" value="<?= $shift['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Bạn có chắc chắn muốn gỡ tình nguyện viên này khỏi ca?">
                                                    <i class="bi bi-person-dash"></i> Gỡ
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="small text-muted">Đã xử lý</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Thêm người vào ca thủ công -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h2 class="h5 fw-bold mb-1"><i class="bi bi-person-plus-fill text-success me-2"></i>Thêm Người Vào Ca</h2>
            <p class="text-muted small mb-3">
                Chọn từ <?= (int)($candidatePagination['total'] ?? count($availableVolunteers)) ?> tình nguyện viên đã đăng ký, được duyệt, đủ kỹ năng bắt buộc và không trùng lịch. Sau khi được phân công, tình nguyện viên cần xác nhận tham gia.
            </p>

            <?php if ($assignedCount >= $maxVol): ?>
                <div class="alert alert-danger py-2 small mb-0">
                    <i class="bi bi-slash-circle me-1"></i> Ca đã đạt số lượng tối đa (<?= $maxVol ?> người). Không thể thêm người nữa.
                </div>
            <?php else: ?>
                <?php if (empty($availableVolunteers)): ?>
                    <div class="alert alert-info py-2 small mb-0">
                        <?= $assignedCount > 0
                            ? 'Không còn tình nguyện viên nào khác đáp ứng đủ điều kiện phân công cho ca này.'
                            : 'Chưa có tình nguyện viên nào đáp ứng đủ điều kiện phân công cho ca này.' ?>
                    </div>
                <?php else: ?>
                <form action="<?= $baseUrl ?>/coordinator/assignment/manual/add" method="POST">
                    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="shift_id" value="<?= $shift['id'] ?>">

                    <div class="mb-3">
                        <label for="user_id" class="form-label fw-semibold">Chọn tình nguyện viên</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">-- Chọn tình nguyện viên --</option>
                            <?php foreach ($availableVolunteers as $vol): ?>
                                <option value="<?= $vol['id'] ?>">
                                    <?= htmlspecialchars($vol['full_name']) ?> (<?= htmlspecialchars($vol['student_id'] ?? '') ?>) - [<?= (int)$vol['current_shifts'] ?> ca]
                                    <?= !empty($vol['skills']) ? ' - KN: ' . htmlspecialchars($vol['skills']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2">
                        <i class="bi bi-plus-lg me-1"></i> Thêm vào ca này
                    </button>
                </form>
                <?php
                $pagination = array_merge($candidatePagination ?? [], [
                    'url' => $baseUrl . '/coordinator/assignment/manual/' . (int)$shift['id'],
                    'params' => [],
                    'visible_count' => count($availableVolunteers),
                    'item_label' => 'tình nguyện viên phù hợp',
                    'size_id' => 'eligible-volunteer-page-size',
                ]);
                require VIEW_PATH . '/components/pagination.php';
                ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
