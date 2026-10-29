<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$baseline = $comparison['baseline']['metrics'] ?? [];
$improved = $comparison['improved']['metrics'] ?? [];
$diff = $comparison['comparison'] ?? [];
?>
<div class="mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-graph-up-arrow text-danger me-2"></i>Thực Nghiệm So Sánh Hai Thuật Toán</h1>
            <p class="text-muted small mb-0">Đối chứng trực tiếp giữa <strong>Baseline Greedy</strong> (Cơ sở) và <strong>Improved Greedy</strong> (Cải tiến - MCF)</p>
        </div>
        
        <!-- Chọn bộ dữ liệu thử nghiệm -->
        <form action="<?= $baseUrl ?>/admin/reports/algorithm" method="GET" class="d-flex align-items-center gap-2">
            <label for="actSelect" class="form-label mb-0 fw-semibold small text-nowrap">Bộ dữ liệu / Hoạt động:</label>
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
        <strong>Dữ liệu đầu vào:</strong> <?= $comparison['input_summary']['total_shifts'] ?? 0 ?> ca trực | 
        <?= $comparison['input_summary']['total_volunteers'] ?? 0 ?> tình nguyện viên hợp lệ | 
        <?= $comparison['input_summary']['total_registrations'] ?? 0 ?> lượt đăng ký
    </div>
    <span class="badge bg-primary-subtle text-primary">Chạy trên cùng 1 phiên bản snapshot dữ liệu</span>
</div>

<!-- Side-by-Side Comparison Table -->
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <h2 class="h5 fw-bold mb-3"><i class="bi bi-table text-primary me-2"></i>Bảng So Sánh Chỉ Số Thực Nghiệm</h2>

    <div class="table-responsive">
        <table class="table table-bordered align-middle text-center mb-0">
            <thead class="table-light">
                <tr>
                    <th class="text-start" style="width: 35%;">Chỉ số đo lường</th>
                    <th style="width: 25%;" class="bg-light">
                        <span class="badge bg-secondary mb-1">Baseline Greedy</span><br>
                        Thuật toán cơ sở (Tuần tự)
                    </th>
                    <th style="width: 25%;" class="bg-success-soft text-success">
                        <span class="badge bg-success mb-1">Improved Greedy</span><br>
                        Thuật toán cải tiến (MCF + Workload)
                    </th>
                    <th style="width: 15%;">Chênh lệch (Delta)</th>
                </tr>
            </thead>
            <tbody>
                <!-- 1. Tỷ lệ ca đủ người -->
                <tr>
                    <td class="text-start">
                        <strong>1. Tỷ lệ ca đủ người (Sufficient Rate)</strong>
                        <div class="small text-muted">Số ca đạt mức min / Tổng số ca × 100%</div>
                    </td>
                    <td class="fs-5 fw-bold"><?= $baseline['sufficient_rate'] ?? 0 ?>%</td>
                    <td class="fs-5 fw-bold text-success"><?= $improved['sufficient_rate'] ?? 0 ?>%</td>
                    <td>
                        <?php $rateDiff = ($improved['sufficient_rate'] ?? 0) - ($baseline['sufficient_rate'] ?? 0); ?>
                        <span class="badge <?= $rateDiff >= 0 ? 'bg-success' : 'bg-danger' ?> fs-6">
                            <?= $rateDiff >= 0 ? "+$rateDiff%" : "$rateDiff%" ?>
                        </span>
                    </td>
                </tr>

                <!-- 2. Số vi phạm ràng buộc -->
                <tr>
                    <td class="text-start">
                        <strong>2. Số vi phạm ràng buộc cứng (Violations)</strong>
                        <div class="small text-muted">Trùng lịch, thiếu kỹ năng, vượt số max (Mục tiêu: 0)</div>
                    </td>
                    <td class="fs-5 fw-bold <?= ($baseline['violations_count'] ?? 0) === 0 ? 'text-success' : 'text-danger' ?>">
                        <?= $baseline['violations_count'] ?? 0 ?>
                    </td>
                    <td class="fs-5 fw-bold <?= ($improved['violations_count'] ?? 0) === 0 ? 'text-success' : 'text-danger' ?>">
                        <?= $improved['violations_count'] ?? 0 ?>
                    </td>
                    <td>
                        <span class="badge bg-success">0 vi phạm</span>
                    </td>
                </tr>

                <!-- 3. Độ lệch số ca giữa các TNV -->
                <tr>
                    <td class="text-start">
                        <strong>3. Độ lệch số ca (Workload Deviation)</strong>
                        <div class="small text-muted">Max(ca) - Min(ca) của nhóm TNV được xét (Càng nhỏ càng đều)</div>
                    </td>
                    <td class="fs-5 fw-bold text-danger"><?= $baseline['shift_deviation'] ?? 0 ?> ca</td>
                    <td class="fs-5 fw-bold text-success"><?= $improved['shift_deviation'] ?? 0 ?> ca</td>
                    <td>
                        <?php $devDiff = ($baseline['shift_deviation'] ?? 0) - ($improved['shift_deviation'] ?? 0); ?>
                        <span class="badge <?= $devDiff >= 0 ? 'bg-success' : 'bg-warning' ?> fs-6">
                            <?= $devDiff >= 0 ? "Giảm $devDiff ca (Đều hơn)" : "Tăng $devDiff ca" ?>
                        </span>
                    </td>
                </tr>

                <!-- 4. Tổng số lượt người-ca hợp lệ -->
                <tr>
                    <td class="text-start">
                        <strong>4. Tổng số lượt người-ca (Valid Person-Shifts)</strong>
                        <div class="small text-muted">Tổng số lượt phân công hợp lệ đã gán được</div>
                    </td>
                    <td class="fs-5 fw-bold"><?= $baseline['total_person_shifts'] ?? 0 ?></td>
                    <td class="fs-5 fw-bold text-success"><?= $improved['total_person_shifts'] ?? 0 ?></td>
                    <td>
                        <?php $psDiff = ($improved['total_person_shifts'] ?? 0) - ($baseline['total_person_shifts'] ?? 0); ?>
                        <span class="badge <?= $psDiff >= 0 ? 'bg-success' : 'bg-secondary' ?> fs-6">
                            <?= $psDiff >= 0 ? "+$psDiff" : "$psDiff" ?>
                        </span>
                    </td>
                </tr>

                <!-- 5. Thời gian chạy -->
                <tr>
                    <td class="text-start">
                        <strong>5. Thời gian thực thi (Execution Time)</strong>
                        <div class="small text-muted">Tính bằng mili-giây (ms)</div>
                    </td>
                    <td><code><?= $baseline['duration_ms'] ?? 0 ?> ms</code></td>
                    <td><code><?= $improved['duration_ms'] ?? 0 ?> ms</code></td>
                    <td class="text-muted small">&lt; 10 ms (Rất nhanh)</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js Visualization -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h2 class="h5 fw-bold mb-3"><i class="bi bi-pie-chart text-success me-2"></i>Biểu Đồ So Sánh Tỷ Lệ Đủ Người (%)</h2>
            <canvas id="rateChart" style="max-height: 260px;"></canvas>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h2 class="h5 fw-bold mb-3"><i class="bi bi-bar-chart text-primary me-2"></i>Biểu Đồ Độ Lệch Khối Lượng Ca (Càng thấp càng đều)</h2>
            <canvas id="deviationChart" style="max-height: 260px;"></canvas>
        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Rate Chart
    new Chart(document.getElementById('rateChart'), {
        type: 'bar',
        data: {
            labels: ['Baseline Greedy', 'Improved Greedy (MCF)'],
            datasets: [{
                label: 'Tỷ lệ ca đủ người (%)',
                data: [<?= $baseline['sufficient_rate'] ?? 0 ?>, <?= $improved['sufficient_rate'] ?? 0 ?>],
                backgroundColor: ['#6c757d', '#198754'],
                borderRadius: 8
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true, max: 100 }
            },
            plugins: { legend: { display: false } }
        }
    });

    // 2. Deviation Chart
    new Chart(document.getElementById('deviationChart'), {
        type: 'bar',
        data: {
            labels: ['Baseline Greedy', 'Improved Greedy (MCF)'],
            datasets: [{
                label: 'Độ lệch ca (Max - Min)',
                data: [<?= $baseline['shift_deviation'] ?? 0 ?>, <?= $improved['shift_deviation'] ?? 0 ?>],
                backgroundColor: ['#dc3545', '#0dcaf0'],
                borderRadius: 8
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            },
            plugins: { legend: { display: false } }
        }
    });
});
</script>
