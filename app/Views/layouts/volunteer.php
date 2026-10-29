<?php
/**
 * Layout cho khu vực Tình nguyện viên (Volunteer)
 */
use App\Core\Session;
use App\Models\AssignmentModel;
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$userName = Session::userName();
$assignedShiftCount = (new AssignmentModel())->countActiveByUser((int)Session::userId());
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Tình Nguyện Viên | VolunteerHub') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
</head>
<body>
    <!-- Topbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success volunteer-shell-navbar sticky-top shadow-sm py-2">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand d-flex align-items-center gap-2 text-white" href="<?= $baseUrl ?>/volunteer/dashboard">
                <i class="bi bi-heart-pulse-fill fs-4 text-warning"></i>
                <span class="fw-bold">VolunteerHub</span>
                <span class="badge bg-white text-success ms-2 small">Tình nguyện viên</span>
            </a>
            
            <div class="d-flex align-items-center gap-3 ms-auto">
                <a href="<?= $baseUrl ?>/volunteer/notifications" class="text-white text-decoration-none position-relative" title="Thông báo">
                    <i class="bi bi-bell-fill fs-5"></i>
                </a>
                <span class="text-white d-none d-md-inline fw-semibold"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($userName) ?></span>
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
            <div class="text-muted small text-uppercase fw-bold px-3 mb-2">Menu cá nhân</div>
            <nav class="nav flex-column">
                <a class="nav-link <?= str_ends_with($currentUri, '/dashboard') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/dashboard">
                    <i class="bi bi-grid-1x2"></i> Tổng quan
                </a>
                <a class="nav-link <?= str_contains($currentUri, '/activities') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/activities">
                    <i class="bi bi-compass"></i> Khám phá hoạt động
                    <span class="badge bg-warning text-dark ms-auto" style="font-size: 0.65rem;">Mới</span>
                </a>
                <a class="nav-link volunteer-schedule-link <?= str_ends_with($currentUri, '/schedule') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/schedule">
                    <i class="bi bi-calendar-check"></i> Lịch phân công
                    <?php if ($assignedShiftCount > 0): ?>
                        <span class="badge rounded-pill bg-danger volunteer-assignment-count"
                              aria-label="<?= $assignedShiftCount ?> ca đã được phân công"
                              title="<?= $assignedShiftCount ?> ca đã được phân công">
                            <?= $assignedShiftCount > 99 ? '99+' : $assignedShiftCount ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a class="nav-link <?= str_ends_with($currentUri, '/shifts') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/shifts">
                    <i class="bi bi-plus-circle"></i> Đăng ký ca trực
                </a>
                <a class="nav-link <?= str_ends_with($currentUri, '/my-registrations') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/my-registrations">
                    <i class="bi bi-card-checklist"></i> Ca đã đăng ký
                </a>
                <a class="nav-link <?= str_ends_with($currentUri, '/availability') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/availability">
                    <i class="bi bi-clock-history"></i> Khai báo lịch rảnh
                </a>
                <a class="nav-link <?= str_ends_with($currentUri, '/replacement') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/replacement">
                    <i class="bi bi-person-dash"></i> Báo vắng & Thay người
                </a>
            </nav>

            <div class="text-muted small text-uppercase fw-bold px-3 mt-4 mb-2">Tài khoản & Hồ sơ</div>
            <nav class="nav flex-column">
                <a class="nav-link <?= str_ends_with($currentUri, '/profile') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/profile">
                    <i class="bi bi-person-badge"></i> Hồ sơ cá nhân
                </a>
                <a class="nav-link <?= str_ends_with($currentUri, '/skills') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/skills">
                    <i class="bi bi-award"></i> Kỹ năng của tôi
                </a>
                <a class="nav-link <?= str_ends_with($currentUri, '/history') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/history">
                    <i class="bi bi-clock"></i> Lịch sử tham gia
                </a>
                <a class="nav-link <?= str_ends_with($currentUri, '/notifications') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/notifications">
                    <i class="bi bi-bell"></i> Thông báo
                </a>
                <a class="nav-link <?= str_ends_with($currentUri, '/change-password') ? 'active' : '' ?>" href="<?= $baseUrl ?>/volunteer/change-password">
                    <i class="bi bi-key"></i> Đổi mật khẩu
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
