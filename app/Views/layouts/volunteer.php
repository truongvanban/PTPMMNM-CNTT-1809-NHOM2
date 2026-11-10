<?php
/**
 * Layout cho khu vực Tình nguyện viên (Volunteer) - VolunteerHub Modern UI
 */
use App\Core\Session;
use App\Models\AssignmentModel;
use App\Models\NotificationModel;
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$userName = Session::userName();
$styleVersion = filemtime(dirname(__DIR__, 3) . '/public/assets/css/style.css');
$assignedShiftCount = (new AssignmentModel())->countActiveByUser((int)Session::userId());
$unreadNotificationCount = (new NotificationModel())->getUnreadCount((int)Session::userId());
$userInitials = mb_strtoupper(mb_substr($userName ?: 'T', 0, 1));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Tình Nguyện Viên | VolunteerHub') ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css?v=<?= $styleVersion ?>">
</head>
<body>
    <!-- Modern Topbar -->
    <nav class="navbar navbar-expand-lg navbar-dark volunteer-shell-navbar sticky-top shadow-sm py-2" style="background: linear-gradient(110deg, #064e3b 0%, #065f46 50%, #0d9488 100%);">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand d-flex align-items-center gap-2 text-white" href="<?= $baseUrl ?>/volunteer/dashboard">
                <span class="d-inline-flex align-items-center justify-content-center bg-white bg-opacity-20 rounded-3 text-warning shadow-sm" style="width: 34px; height: 34px; font-size: 1.25rem;">
                    <i class="bi bi-heart-pulse-fill"></i>
                </span>
                <span class="fw-bold tracking-tight">Volunteer<span class="text-warning">Hub</span></span>
                <span class="badge bg-white text-success ms-2 small fw-semibold rounded-pill px-2">Tình nguyện viên</span>
            </a>

            <div class="d-flex align-items-center gap-3 ms-auto">
                <!-- Notifications Bell -->
                <a href="<?= $baseUrl ?>/volunteer/notifications" class="text-white text-decoration-none position-relative d-inline-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-10 p-2" title="Thông báo" style="width: 38px; height: 38px;">
                    <i class="bi bi-bell-fill fs-5"></i>
                    <?php if ($unreadNotificationCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" style="font-size: 0.65rem;">
                            <?= $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- User Profile Pill -->
                <div class="d-none d-md-inline-flex align-items-center gap-2 bg-white bg-opacity-15 text-white px-3 py-1 rounded-pill border border-white border-opacity-20">
                    <span class="rounded-circle bg-warning text-dark fw-bold d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.75rem;">
                        <?= htmlspecialchars($userInitials) ?>
                    </span>
                    <span class="small fw-semibold"><?= htmlspecialchars($userName) ?></span>
                </div>

                <!-- Logout Button -->
                <form action="<?= $baseUrl ?>/logout" method="POST" class="m-0">
                    <input type="hidden" name="_csrf_token" value="<?= Session::generateCsrf() ?>">
                    <button type="submit" class="btn btn-outline-light btn-sm rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1" title="Đăng xuất">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="d-none d-sm-inline">Thoát</span>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Dashboard Body -->
    <div class="dashboard-wrapper">
        <!-- Modern Sidebar Navigation -->
        <aside class="sidebar">
            <!-- Group 1: Hoạt động & Ca trực -->
            <div class="text-muted small text-uppercase fw-bold px-3 mb-2" style="font-size: 0.7rem; letter-spacing: 0.08em;">
                Khám phá & Ca trực
            </div>
            <nav class="nav flex-column mb-3">
                <a class="nav-link <?= str_ends_with($currentUri, '/dashboard') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/dashboard">
                    <i class="bi bi-grid-1x2-fill text-success"></i> <span>Tổng quan</span>
                </a>
                <a class="nav-link <?= str_contains($currentUri, '/activities') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/activities">
                    <i class="bi bi-compass-fill text-primary"></i> <span>Khám phá hoạt động</span>
                    <span class="badge bg-warning text-dark ms-auto" style="font-size: 0.65rem;">Mới</span>
                </a>
                <a class="nav-link <?= str_ends_with($currentUri, '/shifts') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/shifts">
                    <i class="bi bi-plus-circle-fill text-info"></i> <span>Đăng ký ca trực</span>
                </a>
            </nav>

            <!-- Group 2: Lịch trình & Báo vắng -->
            <div class="text-muted small text-uppercase fw-bold px-3 mb-2" style="font-size: 0.7rem; letter-spacing: 0.08em;">
                Lịch trình & Đơn
            </div>
            <nav class="nav flex-column mb-3">
                <a class="nav-link volunteer-schedule-link <?= str_ends_with($currentUri, '/schedule') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/schedule">
                    <i class="bi bi-calendar-check-fill text-success"></i> <span>Lịch phân công</span>
                    <?php if ($assignedShiftCount > 0): ?>
                        <span class="badge rounded-pill bg-danger volunteer-assignment-count"
                              aria-label="<?= $assignedShiftCount ?> ca đã được phân công"
                              title="<?= $assignedShiftCount ?> ca đã được phân công">
                            <?= $assignedShiftCount > 99 ? '99+' : $assignedShiftCount ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a class="nav-link <?= str_ends_with($currentUri, '/my-registrations') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/my-registrations">
                    <i class="bi bi-card-checklist text-primary"></i> <span>Quản lý đơn đăng ký</span>
                </a>
                <a class="nav-link <?= str_ends_with($currentUri, '/replacement') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/replacement">
                    <i class="bi bi-arrow-left-right text-warning"></i> <span>Báo vắng & Thay người</span>
                </a>
            </nav>

            <!-- Group 3: Hồ sơ & Thành tích -->
            <div class="text-muted small text-uppercase fw-bold px-3 mb-2" style="font-size: 0.7rem; letter-spacing: 0.08em;">
                Cá nhân & Thành tích
            </div>
            <nav class="nav flex-column">
                <a class="nav-link <?= str_ends_with($currentUri, '/profile') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/profile">
                    <i class="bi bi-person-badge-fill text-dark"></i> <span>Hồ sơ cá nhân</span>
                </a>
                <a class="nav-link <?= str_ends_with($currentUri, '/skills') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/skills">
                    <i class="bi bi-patch-check-fill text-primary"></i> <span>Kỹ năng của tôi</span>
                </a>
                <a class="nav-link <?= str_ends_with($currentUri, '/history') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/history">
                    <i class="bi bi-calendar2-check-fill text-warning"></i> <span>Lịch sử ca trực</span>
                </a>
                <a class="nav-link <?= str_contains($currentUri, '/points') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/points">
                    <i class="bi bi-award-fill text-success"></i> <span>Điểm cống hiến & Thi đua</span>
                </a>
                <a class="nav-link volunteer-notifications-link <?= str_ends_with($currentUri, '/notifications') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/notifications">
                    <i class="bi bi-bell-fill text-danger"></i> <span>Thông báo</span>
                    <?php if ($unreadNotificationCount > 0): ?>
                        <span class="badge rounded-pill bg-danger volunteer-notification-count"
                              aria-label="<?= $unreadNotificationCount ?> thông báo chưa đọc"
                              title="<?= $unreadNotificationCount ?> thông báo chưa đọc">
                            <?= $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a class="nav-link <?= str_ends_with($currentUri, '/change-password') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/change-password">
                    <i class="bi bi-key-fill text-secondary"></i> <span>Đổi mật khẩu</span>
                </a>
            </nav>

            <div class="mt-4 pt-3 border-top px-3">
                <a href="<?= $baseUrl ?>/" class="btn btn-sm btn-outline-secondary w-100 rounded-pill"><i class="bi bi-arrow-left me-1"></i> Về trang chủ</a>
            </div>

            <!-- Profile Badge at sidebar bottom -->
            <div class="volunteer-sidebar-profile mt-3 p-2 bg-light rounded-3 d-flex align-items-center gap-2 border">
                <span class="volunteer-sidebar-avatar bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
                    <?= htmlspecialchars($userInitials) ?>
                </span>
                <div class="overflow-hidden">
                    <strong class="d-block text-truncate text-dark" style="font-size: 0.82rem;"><?= htmlspecialchars($userName) ?></strong>
                    <small class="text-success fw-semibold" style="font-size: 0.72rem;">● Đang hoạt động</small>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show rounded-3" role="alert" style="background-color: #ecfdf5; color: #065f46;">
                    <i class="bi bi-check-circle-fill text-success me-2 fs-5 align-middle"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show rounded-3" role="alert" style="background-color: #fef2f2; color: #991b1b;">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-2 fs-5 align-middle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/main.js"></script>
</body>
</html>