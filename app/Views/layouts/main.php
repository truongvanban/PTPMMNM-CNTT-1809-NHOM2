<?php
/**
 * Layout công khai (Public) - VolunteerHub Modern UI
 */
use App\Core\Session;
$isLoggedIn = Session::isLoggedIn();
$userName   = Session::userName();
$baseUrl    = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$styleVersion = filemtime(dirname(__DIR__, 3) . '/public/assets/css/style.css');
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isHome = ($currentUri === '/' || $currentUri === '' || str_ends_with($currentUri, '/public') || str_ends_with($currentUri, '/public/'));
$isActivities = str_contains($currentUri, '/activities');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'VolunteerHub - Nền Tảng Tình Nguyện Viên') ?></title>
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
<body class="d-flex flex-column min-vh-100">
    <!-- Modern Frosted Glass Navbar -->
    <header class="sticky-top">
        <nav class="navbar navbar-expand-lg vh-navbar-public py-2 py-lg-3" id="mainNavbar">
            <div class="container">
                <a class="vh-navbar-brand" href="<?= $baseUrl ?>/">
                    <span class="vh-brand-badge">
                        <i class="bi bi-heart-pulse-fill"></i>
                    </span>
                    <span class="vh-brand-title">Volunteer<span>Hub</span></span>
                </a>
                
                <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPublic" aria-controls="navbarPublic" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarPublic">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-lg-1">
                        <li class="nav-item">
                            <a class="nav-link vh-nav-link <?= $isHome ? 'active' : '' ?>" href="<?= $baseUrl ?>/">
                                <i class="bi bi-house-door"></i> Trang chủ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link vh-nav-link <?= $isActivities ? 'active' : '' ?>" href="<?= $baseUrl ?>/activities">
                                <i class="bi bi-compass"></i> Hoạt động
                            </a>
                        </li>
                    </ul>

                    <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                        <?php if ($isLoggedIn): ?>
                            <?php if (Session::hasRole('admin')): ?>
                                <a href="<?= $baseUrl ?>/admin/dashboard" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1 rounded-pill px-3 shadow-sm">
                                    <i class="bi bi-shield-lock-fill"></i> Quản trị Admin
                                </a>
                            <?php elseif (Session::hasRole('coordinator')): ?>
                                <a href="<?= $baseUrl ?>/coordinator/dashboard" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 rounded-pill px-3 shadow-sm">
                                    <i class="bi bi-kanban-fill"></i> Điều phối viên
                                </a>
                            <?php else: ?>
                                <a href="<?= $baseUrl ?>/volunteer/dashboard" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 rounded-pill px-3 shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                                    <i class="bi bi-speedometer2"></i> Tình nguyện viên
                                </a>
                            <?php endif; ?>

                            <div class="vh-user-pill d-none d-md-inline-flex">
                                <i class="bi bi-person-circle text-muted"></i>
                                <span><?= htmlspecialchars($userName) ?></span>
                            </div>
                            
                            <form action="<?= $baseUrl ?>/logout" method="POST" class="d-inline m-0">
                                <input type="hidden" name="_csrf_token" value="<?= Session::generateCsrf() ?>">
                                <button type="submit" class="btn btn-light btn-sm text-secondary rounded-circle shadow-sm" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Đăng xuất">
                                    <i class="bi bi-box-arrow-right"></i>
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?= $baseUrl ?>/login" class="btn btn-sm btn-outline-secondary px-3 py-2 rounded-pill fw-semibold">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
                            </a>
                            <a href="<?= $baseUrl ?>/register" class="btn btn-sm text-white px-3 py-2 rounded-pill fw-semibold shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                                <i class="bi bi-person-plus-fill me-1"></i> Đăng ký tham gia
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Body Content -->
    <main class="flex-grow-1">
        <?php if (!empty($success)): ?>
            <div class="container mt-3">
                <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show rounded-3" role="alert" style="background-color: #ecfdf5; color: #065f46;">
                    <i class="bi bi-check-circle-fill text-success me-2 fs-5 align-middle"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="container mt-3">
                <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show rounded-3" role="alert" style="background-color: #fef2f2; color: #991b1b;">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-2 fs-5 align-middle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <!-- Modern Multi-Column Footer -->
    <footer class="vh-footer">
        <div class="container">
            <div class="row g-4 justify-content-between">
                <!-- Col 1: Brand -->
                <div class="col-lg-4 col-md-6">
                    <a href="<?= $baseUrl ?>/" class="vh-footer-brand">
                        <span class="vh-brand-badge" style="width: 2rem; height: 2rem; font-size: 1rem;">
                            <i class="bi bi-heart-pulse-fill"></i>
                        </span>
                        <span>VolunteerHub</span>
                    </a>
                    <p class="vh-footer-desc">
                        Nền tảng kết nối tình nguyện viên thông minh, tự động tối ưu hóa phân công ca trực dựa trên thuật toán Improved Greedy (MCF), đảm bảo công bằng và không trùng lịch.
                    </p>
                    <div class="d-flex align-items-center">
                        <a href="https://github.com" target="_blank" rel="noopener" class="vh-footer-social-btn" title="GitHub">
                            <i class="bi bi-github"></i>
                        </a>
                        <a href="https://facebook.com" target="_blank" rel="noopener" class="vh-footer-social-btn" title="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="mailto:contact@volunteerhub.vn" class="vh-footer-social-btn" title="Email liên hệ">
                            <i class="bi bi-envelope-fill"></i>
                        </a>
                        <a href="<?= $baseUrl ?>/activities" class="vh-footer-social-btn" title="Khám phá">
                            <i class="bi bi-compass-fill"></i>
                        </a>
                    </div>
                </div>

                <!-- Col 2: Dành cho Tình Nguyện Viên -->
                <div class="col-lg-2 col-md-6 col-6">
                    <h3 class="vh-footer-heading">Khám Phá</h3>
                    <ul class="vh-footer-links">
                        <li><a href="<?= $baseUrl ?>/"><i class="bi bi-chevron-right small"></i> Trang chủ</a></li>
                        <li><a href="<?= $baseUrl ?>/activities"><i class="bi bi-chevron-right small"></i> Danh sách hoạt động</a></li>
                        <li><a href="<?= $baseUrl ?>/register"><i class="bi bi-chevron-right small"></i> Đăng ký tình nguyện</a></li>
                        <li><a href="<?= $baseUrl ?>/login"><i class="bi bi-chevron-right small"></i> Đăng nhập hệ thống</a></li>
                    </ul>
                </div>

                <!-- Col 3: Dành cho Ban Quản Lý -->
                <div class="col-lg-3 col-md-6 col-6">
                    <h3 class="vh-footer-heading">Quản Trị & Thuật Toán</h3>
                    <ul class="vh-footer-links">
                        <li><a href="<?= $baseUrl ?>/coordinator/dashboard"><i class="bi bi-chevron-right small"></i> Bảng điều phối</a></li>
                        <li><a href="<?= $baseUrl ?>/coordinator/assignment/run"><i class="bi bi-chevron-right small"></i> Phân công tự động MCF</a></li>
                        <li><a href="<?= $baseUrl ?>/admin/reports/algorithm"><i class="bi bi-chevron-right small"></i> So sánh Benchmark</a></li>
                        <li><a href="<?= $baseUrl ?>/coordinator/reports"><i class="bi bi-chevron-right small"></i> Báo cáo & Xuất CSV</a></li>
                    </ul>
                </div>

                <!-- Col 4: Liên hệ & Thông tin -->
                <div class="col-lg-3 col-md-6">
                    <h3 class="vh-footer-heading">Hỗ Trợ & Liên Hệ</h3>
                    <ul class="vh-footer-links">
                        <li class="d-flex align-items-start gap-2 text-secondary small">
                            <i class="bi bi-geo-alt text-success mt-1"></i>
                            <span>Khu Công nghệ cao Hòa Lạc, Hà Nội</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 text-secondary small">
                            <i class="bi bi-telephone text-success"></i>
                            <span>Hotline: 1900 6868</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 text-secondary small">
                            <i class="bi bi-envelope text-success"></i>
                            <span>hotro@volunteerhub.vn</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 text-secondary small">
                            <i class="bi bi-shield-check text-success"></i>
                            <span>Bảo mật dữ liệu tuyệt đối</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="vh-footer-bottom">
                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                        &copy; <?= date('Y') ?> <strong class="text-white">VolunteerHub</strong>. Nền tảng Quản lý & Phân công Tình nguyện viên Thông minh.
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        Đồ án Phát triển Phần mềm Mã nguồn mở &bull; Made with <i class="bi bi-heart-fill text-danger mx-1"></i> for Community
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/main.js"></script>
</body>
</html>