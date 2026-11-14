<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$metrics = $report['metrics'] ?? [];
$inputSummary = $report['input_summary'] ?? [];
$shiftResults = $report['shift_results'] ?? [];
?>
<div class="mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-cpu text-primary me-2"></i>Báo Cáo Hiệu Quả Thuật Toán Phân Công</h1>
            <p class="text-muted small mb-0">Thuật toán phân công tối ưu: <strong>Ưu tiên kỹ năng cần thiết</strong> kết hợp <strong>Cân bằng khối lượng công việc (MCF & Workload Balancing)</strong></p>
        </div>
        
        <!-- Chọn bộ dữ liệu thử nghiệm -->
        <form action="<?= $baseUrl ?>/admin/reports/algorithm" method="GET" class="d-flex align-items-center gap-2">
            <label for="actSelect" class="form-label mb-0 fw-semibold small text-nowrap">Chọn hoạt động:</label>
            <select class="form-select form-select-sm" id="actSelect" name="activity_id" onchange="this.form.submit()">
                <?php foreach ($activities as $act): ?>
                    <option value="<?= $act['id'] ?>" <?= $selectedActId === (int)$act['id'] ? 'selected' : '' ?>>
                        #<?= $act['id'] ?>: <?= htmlspecialchars($act['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<!-- Dataset Context Banner -->
<div class="alert alert-secondary border-0 rounded-4 p-3 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
        <i class="bi bi-database-check text-primary me-2"></i>
        <strong>Dữ liệu đầu vào:</strong> <?= (int)($inputSummary['total_shifts'] ?? 0) ?> ca trực | 
        <?= (int)($inputSummary['total_volunteers'] ?? 0) ?> tình nguyện viên hợp lệ | 
        <?= (int)($inputSummary['total_registrations'] ?? 0) ?> lượt đăng ký
    </div>
    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Thuật toán duy nhất của hệ thống</span>
</div>

<!-- 4 Key Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 border-start border-success border-4">
            <div class="small text-muted fw-semibold">Tỷ lệ ca đủ người</div>
            <div class="fs-3 fw-bold text-success mt-1"><?= (float)($metrics['sufficient_rate'] ?? 0) ?>%</div>
            <small class="text-muted"><?= (int)($metrics['sufficient_shifts'] ?? 0) ?> / <?= (int)($metrics['total_shifts'] ?? 0) ?> ca đạt tối thiểu</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 border-start border-primary border-4">
            <div class="small text-muted fw-semibold">Lượt người-ca hợp lệ</div>
            <div class="fs-3 fw-bold text-primary mt-1"><?= (int)($metrics['total_person_shifts'] ?? 0) ?></div>
            <small class="text-muted">Tổng số lượt phân công đã gán</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 border-start border-warning border-4">
            <div class="small text-muted fw-semibold">Độ lệch khối lượng ca</div>
            <div class="fs-3 fw-bold text-dark mt-1"><?= (int)($metrics['shift_deviation'] ?? 0) ?> ca</div>
            <small class="text-muted">Max: <?= (int)($metrics['max_shifts_per_user'] ?? 0) ?> | Min: <?= (int)($metrics['min_shifts_per_user'] ?? 0) ?> (Càng nhỏ càng đều)</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 border-start border-info border-4">
            <div class="small text-muted fw-semibold">Thời gian thực thi</div>
            <div class="fs-3 fw-bold text-info mt-1"><?= (int)($metrics['duration_ms'] ?? 0) ?> ms</div>
            <small class="text-muted"><?= (int)($metrics['violations_count'] ?? 0) ?> vi phạm ràng buộc cứng</small>
        </div>
    </div>
</div>

<!-- Shift Breakdown Table -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 fw-bold mb-0"><i class="bi bi-table text-primary me-2"></i>Chi Tiết Kết Quả Từng Ca Trực</h2>
        <span class="badge bg-light text-dark border"><?= count($shiftResults) ?> ca</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Ca trực</th>
                    <th>Thời gian</th>
                    <th class="text-center">Sĩ số (Đã gán / Min / Max)</th>
                    <th class="text-center">Ưu tiên kỹ năng</th>
                    <th>Trạng thái & Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($shiftResults)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Chưa có dữ liệu ca trực cho hoạt động này.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($shiftResults as $sr): ?>
                        <tr>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($sr['shift_name']) ?></strong>
                            </td>
                            <td>
                                <div><i class="bi bi-calendar3 me-1 text-muted"></i><?= date('d/m/Y', strtotime($sr['date'])) ?></div>
                                <small class="text-muted"><?= substr($sr['time_start'], 0, 5) ?> - <?= substr($sr['time_end'], 0, 5) ?></small>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $sr['is_sufficient'] ? 'bg-success' : 'bg-danger' ?> fs-6">
                                    <?= (int)$sr['assigned_count'] ?> / <?= (int)$sr['min_volunteers'] ?>
                                </span>
                                <div class="small text-muted mt-1">Tối đa: <?= (int)$sr['max_volunteers'] ?></div>
                            </td>
                            <td class="text-center">
                                <?php if (!empty($sr['needed_skills'])): ?>
                                    <span class="badge bg-warning-soft text-dark">
                                        <i class="bi bi-star-fill text-warning me-1"></i><?= (int)($sr['skilled_assigned_count'] ?? 0) ?> người có KN
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">Không yêu cầu</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($sr['is_sufficient']): ?>
                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle-fill me-1"></i>Đủ số lượng tối thiểu</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Thiếu <?= (int)$sr['shortage'] ?> người</span>
                                    <div class="small text-muted mt-1"><?= htmlspecialchars($sr['reason']) ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
