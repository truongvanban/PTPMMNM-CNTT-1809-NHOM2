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
$styleVersion = filemtime(dirname(__DIR__, 3) . '/public/assets/css/style.css');
$coordinatorId = (int)Session::userId();
$pendingRegistrationCount = (new RegistrationModel())->countPendingForCoordinator($coordinatorId);
$pendingReplacementCount = (new ReplacementModel())->countPendingForCoordinator($coordinatorId);
$isActivityCreatePage = str_ends_with($currentUri, '/coordinator/activities/create');
$isActivitySection = str_contains($currentUri, '/coordinator/activities')
    || str_contains($currentUri, '/coordinator/shifts');
$isDashboardPage = str_ends_with($currentUri, '/coordinator/dashboard');
$isAssignmentPage = str_contains($currentUri, '/coordinator/assignment');
$isReportPage = str_contains($currentUri, '/coordinator/reports')
    && !str_contains($currentUri, '/coordinator/history');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Điều Phối Viên | VolunteerHub') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css?v=<?= $styleVersion ?>">
</head>
<body class="coordinator-create-shell <?= $isActivityCreatePage ? 'coordinator-create-page' : '' ?>">
    <header class="activity-create-topbar">
        <button type="button" class="activity-create-menu-toggle" aria-label="Thu gọn menu" aria-expanded="true" data-coordinator-sidebar-toggle>
            <i class="bi bi-list"></i>
        </button>
        <form action="<?= $baseUrl ?>/coordinator/activities" method="GET" class="activity-create-search">
            <i class="bi bi-search"></i>
            <input type="search" name="keyword" placeholder="Tìm kiếm hoạt động..." aria-label="Tìm kiếm hoạt động">
        </form>
        <a href="<?= $baseUrl ?>/coordinator/registrations" class="activity-create-topbar-alert" aria-label="<?= $pendingRegistrationCount ?> đơn đăng ký chờ duyệt">
            <i class="bi bi-bell"></i>
            <?php if ($pendingRegistrationCount > 0): ?>
                <span><?= $pendingRegistrationCount > 99 ? '99+' : $pendingRegistrationCount ?></span>
            <?php endif; ?>
        </a>
    </header>

    <!-- Dashboard Body -->
    <div class="dashboard-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="sidebar coordinator-sidebar">
            <a class="activity-create-brand" href="<?= $baseUrl ?>/coordinator/dashboard">
                <i class="bi bi-heart-pulse-fill"></i>
                <span><strong>VolunteerHub</strong><small>Kết nối · Lan tỏa · Cống hiến</small></span>
            </a>
            <nav class="coordinator-sidebar-nav" aria-label="Điều hướng điều phối viên">
                <a class="nav-link <?= $isDashboardPage ? 'active' : '' ?>" href="<?= $baseUrl ?>/coordinator/dashboard" <?= $isDashboardPage ? 'aria-current="page"' : '' ?>>
                    <i class="bi bi-house-door-fill"></i><span>Tổng quan</span>
                </a>

                <section class="coordinator-sidebar-section">
                    <a class="nav-link coordinator-sidebar-parent <?= $isActivitySection ? 'active' : '' ?>"
                       href="<?= $baseUrl ?>/coordinator/activities"
                       <?= $isActivitySection ? 'aria-current="true"' : '' ?>>
                        <i class="bi bi-calendar-event-fill"></i><span>Quản lý hoạt động</span>
                        <i class="bi bi-chevron-down coordinator-sidebar-chevron"></i>
                    </a>
                    <?php if ($isActivitySection): ?>
                        <div class="coordinator-sidebar-subnav">
                            <a class="<?= !$isActivityCreatePage ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/coordinator/activities"
                               <?= !$isActivityCreatePage ? 'aria-current="page"' : '' ?>>
                                <span></span>Danh sách hoạt động
                            </a>
                            <a class="<?= $isActivityCreatePage ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/coordinator/activities/create"
                               <?= $isActivityCreatePage ? 'aria-current="page"' : '' ?>>
                                <span></span>Tạo hoạt động
                            </a>
                        </div>
                    <?php endif; ?>
                </section>

                <a class="nav-link <?= str_contains($currentUri, '/volunteers') ? 'active' : '' ?>" href="<?= $baseUrl ?>/coordinator/volunteers">
                    <i class="bi bi-people-fill"></i><span>Quản lý TNV</span>
                </a>
                <a class="nav-link coordinator-pending-link <?= str_contains($currentUri, '/registrations') ? 'active' : '' ?>" href="<?= $baseUrl ?>/coordinator/registrations">
                    <i class="bi bi-clipboard-check-fill"></i><span>Quản lý đăng ký</span>
                    <?php if ($pendingRegistrationCount > 0): ?>
                        <span class="badge rounded-pill bg-danger coordinator-pending-count"
                              aria-label="<?= $pendingRegistrationCount ?> đơn đăng ký chờ duyệt"
                              title="<?= $pendingRegistrationCount ?> đơn đăng ký chờ duyệt">
                            <?= $pendingRegistrationCount > 99 ? '99+' : $pendingRegistrationCount ?>
                        </span>
                    <?php endif; ?>
                </a>

                <div class="coordinator-sidebar-heading">ĐIỀU PHỐI</div>
                <a class="nav-link <?= $isAssignmentPage ? 'active' : '' ?>" href="<?= $baseUrl ?>/coordinator/assignment/run">
                    <i class="bi bi-diagram-3-fill"></i><span>Phân công TNV</span>
                </a>
                <a class="nav-link coordinator-pending-link <?= str_contains($currentUri, '/replacement') ? 'active' : '' ?>" href="<?= $baseUrl ?>/coordinator/replacement">
                    <i class="bi bi-arrow-left-right"></i><span>Thay người</span>
                    <?php if ($pendingReplacementCount > 0): ?>
                        <span class="badge rounded-pill bg-danger coordinator-pending-count"
                              aria-label="<?= $pendingReplacementCount ?> yêu cầu báo vắng chờ xử lý"
                              title="<?= $pendingReplacementCount ?> yêu cầu báo vắng chờ xử lý">
                            <?= $pendingReplacementCount > 99 ? '99+' : $pendingReplacementCount ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a class="nav-link <?= $isReportPage ? 'active' : '' ?>" href="<?= $baseUrl ?>/coordinator/reports">
                    <i class="bi bi-bar-chart-fill"></i><span>Báo cáo</span>
                </a>
                <a class="nav-link <?= str_contains($currentUri, '/coordinator/history') ? 'active' : '' ?>" href="<?= $baseUrl ?>/coordinator/history">
                    <i class="bi bi-clock-history"></i><span>Lịch sử phân công</span>
                </a>
                <a class="nav-link <?= str_contains($currentUri, '/coordinator/profile') ? 'active' : '' ?>" href="<?= $baseUrl ?>/coordinator/profile">
                    <i class="bi bi-person-circle"></i><span>Hồ sơ cá nhân</span>
                </a>
            </nav>

            <div class="activity-create-sidebar-user">
                <span class="activity-create-user-avatar"><i class="bi bi-person-fill"></i></span>
                <span class="activity-create-user-name"><strong><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></strong><small>Điều phối viên</small></span>
                <form action="<?= $baseUrl ?>/logout" method="POST" class="m-0">
                    <input type="hidden" name="_csrf_token" value="<?= Session::generateCsrf() ?>">
                    <button type="submit" class="activity-create-sidebar-logout" aria-label="Đăng xuất" title="Đăng xuất"><i class="bi bi-box-arrow-right"></i></button>
                </form>
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
