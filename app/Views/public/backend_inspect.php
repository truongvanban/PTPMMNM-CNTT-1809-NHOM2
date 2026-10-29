<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <span class="badge bg-danger mb-2 fs-6"><i class="bi bi-terminal-fill me-1"></i> Admin Backend Inspector</span>
            <h1 class="h3 fw-bold mb-1">Bảng Kiểm Tra Trực Tiếp Toàn Bộ Dữ Liệu Backend</h1>
            <p class="text-muted small mb-0">Trang dành riêng cho quản trị viên; dữ liệu hệ thống không hiển thị công khai.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $baseUrl ?>/api/status" target="_blank" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-filetype-json me-1"></i> Xem dạng JSON API
            </a>
            <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=volunteerhub" target="_blank" class="btn btn-warning btn-sm fw-semibold">
                <i class="bi bi-database-fill me-1"></i> Mở phpMyAdmin
            </a>
        </div>
    </div>

    <!-- Quick Status Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card p-3 stat-card">
                <div class="small text-muted fw-semibold">Máy chủ Backend</div>
                <div class="h5 fw-bold text-success mt-1 mb-0">PHP <?= PHP_VERSION ?> (Active)</div>
                <small class="text-muted">Mô hình Pure MVC PSR-4</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card p-3 stat-card">
                <div class="small text-muted fw-semibold">Cơ sở dữ liệu</div>
                <div class="h5 fw-bold text-primary mt-1 mb-0">MariaDB 10.4.32</div>
                <small class="text-muted">Kết nối PDO Prepared Statements</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card p-3 stat-card">
                <div class="small text-muted fw-semibold">Tài khoản trong CSDL</div>
                <div class="h5 fw-bold text-dark mt-1 mb-0"><?= count($users) ?> tài khoản</div>
                <small class="text-muted">Admin, Coord, 30 Volunteers</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card p-3 stat-card">
                <div class="small text-muted fw-semibold">Tổng số ca trực</div>
                <div class="h5 fw-bold text-info mt-1 mb-0"><?= count($shifts) ?> ca trực</div>
                <small class="text-muted">Thuộc 4 hoạt động</small>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-3 border-bottom pb-3" id="backendTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="tab-users-btn" data-bs-toggle="pill" data-bs-target="#tab-users" type="button" role="tab">
                <i class="bi bi-people-fill me-1"></i> Bảng Users (<?= count($users) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="tab-shifts-btn" data-bs-toggle="pill" data-bs-target="#tab-shifts" type="button" role="tab">
                <i class="bi bi-clock-history me-1"></i> Bảng Shifts (<?= count($shifts) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="tab-algo-btn" data-bs-toggle="pill" data-bs-target="#tab-algo" type="button" role="tab">
                <i class="bi bi-cpu-fill me-1"></i> Thuật Toán Lõi (Live Run)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="tab-logs-btn" data-bs-toggle="pill" data-bs-target="#tab-logs" type="button" role="tab">
                <i class="bi bi-journal-code me-1"></i> Bảng Audit Logs (<?= count($auditLogs) ?>)
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="backendTabsContent">
        <!-- 1. TAB USERS -->
        <div class="tab-pane fade show active" id="tab-users" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h2 class="h5 fw-bold mb-3"><i class="bi bi-database me-2 text-primary"></i>Dữ Liệu Thô Bảng <code>users</code> và <code>user_roles</code> Trong MySQL</h2>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-custom mb-0 small">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Họ và tên</th>
                                <th>Email</th>
                                <th>Điện thoại</th>
                                <th>Vai trò (Roles)</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><code>#<?= $u['id'] ?></code></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($u['full_name']) ?></td>
                                    <td><code><?= htmlspecialchars($u['email']) ?></code></td>
                                    <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($u['role_names'] ?? 'volunteer') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-soft text-success"><?= htmlspecialchars($u['status']) ?></span>
                                    </td>
                                    <td class="text-muted"><?= htmlspecialchars($u['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. TAB SHIFTS -->
        <div class="tab-pane fade" id="tab-shifts" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h2 class="h5 fw-bold mb-3"><i class="bi bi-database me-2 text-primary"></i>Dữ Liệu Bảng <code>shifts</code> và <code>activities</code> Trong MySQL</h2>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-custom mb-0 small">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên ca trực</th>
                                <th>Hoạt động</th>
                                <th>Ngày</th>
                                <th>Khung giờ</th>
                                <th>Min / Max</th>
                                <th>Đã đăng ký</th>
                                <th>Đã phân công</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shifts as $s): ?>
                                <tr>
                                    <td><code>#<?= $s['id'] ?></code></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($s['name']) ?></td>
                                    <td><?= htmlspecialchars($s['activity_title']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($s['date'])) ?></td>
                                    <td><?= substr($s['time_start'], 0, 5) ?> - <?= substr($s['time_end'], 0, 5) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= $s['min_volunteers'] ?> - <?= $s['max_volunteers'] ?> người</span>
                                    </td>
                                    <td class="fw-bold text-primary"><?= (int)$s['registered_count'] ?> người</td>
                                    <td class="fw-bold text-success"><?= (int)$s['assigned_count'] ?> người</td>
                                    <td>
                                        <span class="badge bg-success"><?= htmlspecialchars($s['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. TAB ALGORITHM LIVE RUN -->
        <div class="tab-pane fade" id="tab-algo" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h2 class="h5 fw-bold mb-2"><i class="bi bi-cpu text-success me-2"></i>Kết Quả Chạy Trực Tiếp Của Backend Service (Live Execution)</h2>
                <p class="text-muted small mb-4">Dữ liệu được tính toán thời gian thực bởi <code>app/Services/ImprovedGreedy.php</code> và <code>BaselineGreedy.php</code></p>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card p-3 border-success border-opacity-50 bg-light">
                            <h3 class="h6 fw-bold text-success mb-2">Thuật toán cải tiến (Improved Greedy - MCF)</h3>
                            <ul class="list-unstyled small mb-3">
                                <li><strong>Tỷ lệ đủ người:</strong> <?= $algoTest['improved']['metrics']['sufficient_rate'] ?? 0 ?>%</li>
                                <li><strong>Vi phạm ràng buộc:</strong> <?= $algoTest['improved']['metrics']['violations_count'] ?? 0 ?> vi phạm</li>
                                <li><strong>Độ lệch số ca:</strong> <?= $algoTest['improved']['metrics']['shift_deviation'] ?? 0 ?> ca</li>
                                <li><strong>Thời gian chạy:</strong> <?= $algoTest['improved']['duration_ms'] ?? 0 ?> ms</li>
                            </ul>
                            <div class="small fw-semibold text-secondary">JSON Output Snapshot:</div>
                            <pre class="bg-dark text-light p-2 rounded small mt-1" style="max-height: 200px; overflow-y: auto;"><code><?= json_encode($algoTest['improved']['metrics'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></code></pre>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card p-3 border-secondary border-opacity-50 bg-light">
                            <h3 class="h6 fw-bold text-secondary mb-2">Thuật toán cơ sở (Baseline Greedy)</h3>
                            <ul class="list-unstyled small mb-3">
                                <li><strong>Tỷ lệ đủ người:</strong> <?= $algoTest['baseline']['metrics']['sufficient_rate'] ?? 0 ?>%</li>
                                <li><strong>Vi phạm ràng buộc:</strong> <?= $algoTest['baseline']['metrics']['violations_count'] ?? 0 ?> vi phạm</li>
                                <li><strong>Độ lệch số ca:</strong> <?= $algoTest['baseline']['metrics']['shift_deviation'] ?? 0 ?> ca</li>
                                <li><strong>Thời gian chạy:</strong> <?= $algoTest['baseline']['duration_ms'] ?? 0 ?> ms</li>
                            </ul>
                            <div class="small fw-semibold text-secondary">JSON Output Snapshot:</div>
                            <pre class="bg-dark text-light p-2 rounded small mt-1" style="max-height: 200px; overflow-y: auto;"><code><?= json_encode($algoTest['baseline']['metrics'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. TAB AUDIT LOGS -->
        <div class="tab-pane fade" id="tab-logs" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h2 class="h5 fw-bold mb-3"><i class="bi bi-journal-code text-danger me-2"></i>Dữ Liệu Bảng <code>audit_logs</code> Trong MySQL</h2>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-custom mb-0 small">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Người dùng</th>
                                <th>Hành động (Action)</th>
                                <th>Loại thực thể</th>
                                <th>Dữ liệu ghi vết</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditLogs as $log): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($log['created_at']) ?></code></td>
                                    <td class="fw-bold"><?= htmlspecialchars($log['user_name'] ?? 'Hệ thống') ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($log['action']) ?></span>
                                    </td>
                                    <td><code><?= htmlspecialchars($log['entity_type'] ?? '-') ?> #<?= (int)$log['entity_id'] ?></code></td>
                                    <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <code><?= htmlspecialchars($log['new_data'] ?? $log['old_data'] ?? '-') ?></code>
                                    </td>
                                    <td><code><?= htmlspecialchars($log['ip_address'] ?? '-') ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
