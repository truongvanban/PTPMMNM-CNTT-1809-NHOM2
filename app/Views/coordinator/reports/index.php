<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h1 class="h3 fw-bold mb-1"><i class="bi bi-file-earmark-bar-graph text-primary me-2"></i>Báo Cáo & Thống Kê Ca Trực</h1>
        <p class="text-muted small mb-0">Theo dõi ca đủ người, ca thiếu người, khối lượng ca theo từng tình nguyện viên</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $baseUrl ?>/coordinator/reports/export-csv" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Xuất Danh Sách Phân Công (CSV UTF-8)
        </a>
        <a href="<?= $baseUrl ?>/coordinator/history" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-clock-history me-1"></i> Lịch sử thay đổi
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Bảng tình trạng ca -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h2 class="h5 fw-bold mb-3"><i class="bi bi-calendar3-range text-success me-2"></i>Tình Trạng Ca Trực (Đủ Người / Thiếu Người)</h2>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Ca & Hoạt động</th>
                            <th>Ngày & Giờ</th>
                            <th>Chỉ tiêu min/max</th>
                            <th>Đã phân công</th>
                            <th>Đã xác nhận</th>
                            <th>Tình trạng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shiftsReport)): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-4">Chưa có ca trực để báo cáo.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($shiftsReport as $sr): ?>
                            <?php 
                                $isShortage = (int)$sr['assigned_count'] < (int)$sr['min_volunteers'];
                            ?>
                            <tr class="<?= $isShortage ? 'table-warning' : '' ?>">
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($sr['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($sr['activity_title']) ?></small>
                                </td>
                                <td>
                                    <div><?= date('d/m/Y', strtotime($sr['date'])) ?></div>
                                    <small class="text-muted"><?= substr($sr['time_start'], 0, 5) ?> - <?= substr($sr['time_end'], 0, 5) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= (int)$sr['min_volunteers'] ?> - <?= (int)$sr['max_volunteers'] ?> người
                                    </span>
                                </td>
                                <td class="fw-bold"><?= (int)$sr['assigned_count'] ?> người</td>
                                <td>
                                    <span class="badge bg-success-soft text-success"><?= (int)$sr['confirmed_count'] ?> đã xác nhận</span>
                                    <?php if ((int)$sr['absent_count'] > 0): ?>
                                        <span class="badge bg-danger-soft text-danger"><?= (int)$sr['absent_count'] ?> vắng</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isShortage): ?>
                                        <span class="badge bg-danger">Thiếu <?= (int)$sr['min_volunteers'] - (int)$sr['assigned_count'] ?> người</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Đủ người</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            $pagination = [
                'url' => $baseUrl . '/coordinator/reports',
                'params' => ['volunteers_page' => $volunteerPage],
                'page_param' => 'shifts_page',
                'size_id' => 'shifts-page-size',
                'current_page' => $shiftPage,
                'last_page' => $shiftLastPage,
                'total' => $shiftTotal,
                'per_page' => $perPage,
                'visible_count' => count($shiftsReport),
                'item_label' => 'ca trực',
            ];
            require VIEW_PATH . '/components/pagination.php';
            ?>
        </div>
    </div>

    <!-- Bảng khối lượng công việc TNV -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h2 class="h5 fw-bold mb-3"><i class="bi bi-bar-chart-steps text-info me-2"></i>Báo Cáo Khối Lượng Ca Theo Tình Nguyện Viên</h2>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Tình nguyện viên</th>
                            <th>MSSV / Khoa</th>
                            <th>Liên hệ</th>
                            <th class="text-center">Tổng ca phân công</th>
                            <th class="text-center">Đã xác nhận tham gia</th>
                            <th class="text-center">Số lần vắng mặt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($volunteerWorkload)): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-4">Chưa có dữ liệu phân công tình nguyện viên.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($volunteerWorkload as $vw): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($vw['full_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($vw['email']) ?></small>
                                </td>
                                <td>
                                    <div><span class="badge bg-light text-dark border"><?= htmlspecialchars($vw['student_id'] ?? '-') ?></span></div>
                                    <small class="text-muted"><?= htmlspecialchars($vw['faculty'] ?? '') ?></small>
                                </td>
                                <td class="small"><?= htmlspecialchars($vw['phone'] ?? '-') ?></td>
                                <td class="text-center fw-bold text-primary fs-6"><?= (int)$vw['total_assigned'] ?></td>
                                <td class="text-center text-success fw-bold"><?= (int)$vw['total_confirmed'] ?></td>
                                <td class="text-center text-danger fw-bold"><?= (int)$vw['total_absent'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            $pagination = [
                'url' => $baseUrl . '/coordinator/reports',
                'params' => ['shifts_page' => $shiftPage],
                'page_param' => 'volunteers_page',
                'size_id' => 'volunteers-page-size',
                'current_page' => $volunteerPage,
                'last_page' => $volunteerLastPage,
                'total' => $volunteerTotal,
                'per_page' => $perPage,
                'visible_count' => count($volunteerWorkload),
                'item_label' => 'tình nguyện viên',
            ];
            require VIEW_PATH . '/components/pagination.php';
            ?>
        </div>
    </div>
</div>
