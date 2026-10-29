<?php
/**
 * Layout cho khu vực Điều phối viên (Coordinator)
 */
use App\Core\Session;
use App\Models\RegistrationModel;
use App\Models\ReplacementModel;
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$userName = Session::userName();
$coordinatorId = (int)Session::userId();
$pendingRegistrationCount = (new RegistrationModel())->countPendingForCoordinator($coordinatorId);
$pendingReplacementCount = (new ReplacementModel())->countPendingForCoordinator($coordinatorId);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Điều Phối Viên | VolunteerHub') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
</head>
<body>
    <!-- Topbar -->
    <nav class="navbar navbar-expand-lg navbar-dark coordinator-shell-navbar sticky-top shadow-sm py-2">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand d-flex align-items-center gap-2 text-white" href="<?= $baseUrl ?>/coordinator/dashboard">
                <i class="bi bi-kanban text-info fs-4"></i>
                <span class="fw-bold">VolunteerHub</span>
                <span class="badge bg-info text-dark ms-2 small">Điều phối viên</span>
            </a>
            
            <div class="d-flex align-items-center gap-3 ms-auto">
                <span class="text-light d-none d-md-inline fw-semibold"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($userName) ?></span>
                <form action="<?= $baseUrl ?>/logout" method="POST" class="m-0">
                    <input type="hidden" name="_csrf_token" value="<?= Session::generateCsrf() ?>">
                    <button type="submit" class="btn btn-outline-light btn-sm" title="Đăng xuất"><i class="bi bi-box-arrow-right me-1"></i> Thoát</button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Dashboard Body -->
    <div class="dashboard-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="text-muted small text-uppercase fw-bold px-3 mb-2">Quản lý hoạt động</div>
            <nav class="nav flex-column">
                <a class="nav-link <?= str_ends_with($currentUri, '/dashboard') ? 'active' : '' ?>" href="<?= $baseUrl ?>/coordinator/dashboard">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link <?= str_contains($currentUri, '/activities') ? 'active' : '' ?>" href="<?= $baseUrl ?>/coordinator/activities">
                    <i class="bi bi-calendar-event"></i> Hoạt động & Ca trực
                </a>
                <a class="nav-link coordinator-pending-link <?= str_contains($currentUri, '/registrations') ? 'active' : '' ?>" href="<?= $baseUrl ?>/coordinator/registrations">
                    <i class="bi bi-card-checklist"></i> Quản lý đơn đăng ký
                    <?php if ($pendingRegistrationCount > 0): ?>
                        <span class="badge rounded-pill bg-danger coordinator-pending-count"
                              aria-label="<?= $pendingRegistrationCount ?> đơn đăng ký chờ duyệt"
                              title="<?= $pendingRegistrationCount ?> đơn đăng ký chờ duyệt">
                            <?= $pendingRegistrationCount > 99 ? '99+' : $pendingRegistrationCount ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a class="nav-link <?= str_contains($currentUri, '/volunteers') ? 'active' : '' ?>" href="<?= $baseUrl ?>/coordinator/volunteers">
                    <i class="bi bi-people"></i> Danh sách TNV & Kỹ năng
                </a>
            </nav>

            <div class="text-muted small text-uppercase fw-bold px-3 mt-4 mb-2">Phân công & Thay người</div>
            <nav class="nav flex-column">
                <a class="nav-link <?= str_contains($currentUri, '/assignment/run') ? 'active' : '' ?>" href="<?= $baseUrl ?>/coordinator/assignment/run">
                    <i class="bi bi-cpu"></i> Phân công tự động (MCF)
                </a>
                <a class="nav-link coordinator-pending-link <?= str_contains($currentUri, '/replacement') ? 'active' : '' ?>" href="<?= $baseUrl ?>/coordinator/replacement">
                    <i class="bi bi-arrow-left-right"></i> Duyệt báo vắng & Thay người
                    <?php if ($pendingReplacementCount > 0): ?>
                        <span class="badge rounded-pill bg-danger coordinator-pending-count"
                              aria-label="<?= $pendingReplacementCount ?> yêu cầu báo vắng chờ xử lý"
                              title="<?= $pendingReplacementCount ?> yêu cầu báo vắng chờ xử lý">
                            <?= $pendingReplacementCount > 99 ? '99+' : $pendingReplacementCount ?>
                        </span>
                    <?php endif; ?>
                </a>
            </nav>

            <div class="mt-4 pt-3 border-top px-3">
                <a href="<?= $baseUrl ?>/" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-arrow-left me-1"></i> Về trang chủ</a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/main.js"></script>
</body>
</html>
