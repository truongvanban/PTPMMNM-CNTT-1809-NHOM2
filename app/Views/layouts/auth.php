<?php
/**
 * Layout cho trang Đăng nhập / Đăng ký
 */
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Xác Thực | VolunteerHub') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
</head>
<body class="auth-body">
    <main class="auth-shell">
        <aside class="auth-visual">
            <a href="<?= $baseUrl ?>/" class="auth-brand" aria-label="VolunteerHub - Trang chủ">
                <span class="auth-brand-icon"><i class="bi bi-heart-pulse-fill"></i></span>
                <span>Volunteer<span>Hub</span></span>
            </a>

            <div class="auth-visual-content">
                <span class="auth-kicker"><i class="bi bi-stars me-2"></i>Kết nối để tạo nên điều tốt đẹp</span>
                <h1>Cùng nhau<br>lan tỏa <span>giá trị.</span></h1>
                <p>Mỗi đóng góp đều tạo nên khác biệt. Tham gia cộng đồng tình nguyện và tìm những hoạt động phù hợp với bạn.</p>

                <div class="auth-visual-note">
                    <span class="auth-note-icon"><i class="bi bi-people-fill"></i></span>
                    <div>
                        <strong>Một cộng đồng, nhiều điều tử tế</strong>
                        <span>Kết nối tình nguyện viên với các hoạt động ý nghĩa.</span>
                    </div>
                </div>
            </div>

            <div class="auth-visual-footer">
                <span><i class="bi bi-geo-alt me-1"></i> Bắt đầu từ nơi bạn đang sống</span>
                <span>VolunteerHub &copy; <?= date('Y') ?></span>
            </div>
            <span class="auth-decoration auth-decoration-one"></span>
            <span class="auth-decoration auth-decoration-two"></span>
        </aside>

        <section class="auth-main">
            <div class="auth-main-top">
                <a href="<?= $baseUrl ?>/" class="auth-mobile-brand">
                    <i class="bi bi-heart-pulse-fill"></i> Volunteer<span>Hub</span>
                </a>
                <a href="<?= $baseUrl ?>/" class="auth-back-link"><i class="bi bi-arrow-left me-1"></i> Trang chủ</a>
            </div>

            <div class="auth-content">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show auth-alert" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show auth-alert" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="auth-card">
                    <?= $content ?>
                </div>
                <p class="auth-privacy"><i class="bi bi-shield-check me-1"></i> Thông tin của bạn được bảo vệ an toàn</p>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/main.js"></script>
</body>
</html>
