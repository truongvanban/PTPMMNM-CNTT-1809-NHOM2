<?php
/**
 * Layout cho khu vực Quản trị viên (Admin)
 */
use App\Core\Session;
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$mainJsVersion = filemtime(dirname(__DIR__, 3) . '/public/assets/js/main.js');
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$userName = Session::userName();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Quản Trị Viên | VolunteerHub') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
</head>
<body>
    <!-- Topbar -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-shell-navbar sticky-top shadow-sm py-2">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand d-flex align-items-center gap-2 text-white" href="<?= $baseUrl ?>/admin/dashboard">
                <i class="bi bi-shield-fill-check text-danger fs-4"></i>
                <span class="fw-bold">VolunteerHub</span>
                <span class="badge bg-danger text-white ms-2 small">Administrator</span>
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
            <div class="text-muted small text-uppercase fw-bold px-3 mb-2">Hệ thống & Người dùng</div>
            <nav class="nav flex-column">
                <a class="nav-link <?= str_ends_with($currentUri, '/dashboard') ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/dashboard">
                    <i class="bi bi-speedometer2"></i> Dashboard Admin
                </a>
                <a class="nav-link <?= str_contains($currentUri, '/admin/users') ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/users">
                    <i class="bi bi-person-lines-fill"></i> Quản lý tài khoản
                </a>
                <a class="nav-link <?= str_contains($currentUri, '/admin/skills') ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/skills">
                    <i class="bi bi-tags"></i> Quản lý danh mục kỹ năng
                </a>
                <a class="nav-link <?= str_contains($currentUri, '/admin/activities') ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/activities">
                    <i class="bi bi-calendar3"></i> Quản lý toàn bộ hoạt động
                </a>
            </nav>

            <div class="text-muted small text-uppercase fw-bold px-3 mt-4 mb-2">Báo cáo & Thuật toán</div>
            <nav class="nav flex-column">
                <a class="nav-link <?= str_contains($currentUri, '/admin/reports/algorithm') ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/reports/algorithm">
                    <i class="bi bi-graph-up-arrow"></i> So sánh thuật toán (Benchmark)
                </a>
                <a class="nav-link <?= str_contains($currentUri, '/admin/logs') ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/logs">
                    <i class="bi bi-journal-text"></i> Nhật ký hệ thống (Audit Logs)
                </a>
                <a class="nav-link <?= str_contains($currentUri, '/admin/seed') ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/seed">
                    <i class="bi bi-database-gear"></i> Quản lý dữ liệu mẫu
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
    <script src="<?= $baseUrl ?>/assets/js/main.js?v=<?= $mainJsVersion ?>"></script>
</body>
</html>
