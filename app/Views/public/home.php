<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<!-- Hero Banner -->
<section class="hero-section">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">Kết Nối & Phân Công Tình Nguyện Viên</h1>
        <p class="lead mb-4 mx-auto" style="max-width: 720px;">
            Nền tảng quản lý tình nguyện viên hiện đại, tự động đề xuất phân công ca trực dựa trên kỹ năng, 
            lịch rảnh và thuật toán tối ưu hóa cân bằng khối lượng công việc.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="<?= $baseUrl ?>/activities" class="btn btn-light btn-lg text-success fw-bold px-4 shadow-sm">
                <i class="bi bi-compass me-1"></i> Khám phá hoạt động
            </a>
            <a href="<?= $baseUrl ?>/register" class="btn btn-outline-light btn-lg fw-bold px-4">
                <i class="bi bi-person-plus me-1"></i> Đăng ký tình nguyện viên
            </a>
        </div>
    </div>
</section>

<!-- Features Grid -->
<div class="container mb-5">
    <div class="row g-4 text-center">
        <div class="col-md-4">
            <div class="card h-100 p-4 stat-card">
                <div class="stat-icon bg-primary-soft mx-auto mb-3">
                    <i class="bi bi-calendar2-range"></i>
                </div>
                <h3 class="h5 fw-bold">Quản Lý Ca Trực Linh Hoạt</h3>
                <p class="text-muted small">
                    Thiết lập ca trực với thời gian, giới hạn số lượng và yêu cầu kỹ năng cụ thể cho từng vị trí.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 p-4 stat-card">
                <div class="stat-icon bg-success-soft mx-auto mb-3">
                    <i class="bi bi-cpu"></i>
                </div>
                <h3 class="h5 fw-bold">Thuật Toán Phân Công Tự Động</h3>
                <p class="text-muted small">
                    Thuật toán Improved Greedy (MCF) phân công tự động, không trùng lịch, ưu tiên ca khó và cân bằng khối lượng.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 p-4 stat-card">
                <div class="stat-icon bg-warning-soft mx-auto mb-3">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <h3 class="h5 fw-bold">Báo Vắng & Tìm Người Thay</h3>
                <p class="text-muted small">
                    Quy trình duyệt báo vắng nhanh chóng với gợi ý danh sách tình nguyện viên rảnh và đủ kỹ năng thay thế.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Featured Activities -->
<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 fw-bold mb-1">Hoạt Động Đang Mở Đăng Ký</h2>
            <p class="text-muted mb-0 small">Tham gia ngay để cùng lan tỏa giá trị tích cực đến cộng đồng</p>
        </div>
        <a href="<?= $baseUrl ?>/activities" class="btn btn-outline-success btn-sm">Xem tất cả <i class="bi bi-arrow-right"></i></a>
    </div>

    <?php if (empty($activities)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="bi bi-info-circle me-1"></i> Hiện tại chưa có hoạt động nào đang mở đăng ký.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($activities as $act): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 activity-card">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-success badge-status">Đang mở đăng ký</span>
                                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('d/m/Y', strtotime($act['date_start'])) ?></small>
                            </div>
                            <h3 class="h5 card-title fw-bold text-dark mb-2"><?= htmlspecialchars($act['title']) ?></h3>
                            <p class="card-text text-muted small flex-grow-1">
                                <?= htmlspecialchars(mb_strimwidth($act['description'] ?? '', 0, 110, '...')) ?>
                            </p>
                            <div class="border-top pt-3 mt-2 text-muted small">
                                <div class="mb-1"><i class="bi bi-geo-alt text-danger me-1"></i> <?= htmlspecialchars($act['location'] ?? 'Chưa xác định') ?></div>
                                <div class="mb-2"><i class="bi bi-grid text-primary me-1"></i> <?= (int)$act['shift_count'] ?> ca trực có sẵn</div>
                                <a href="<?= $baseUrl ?>/activities/<?= $act['id'] ?>" class="btn btn-success btn-sm w-100 mt-2">
                                    Xem chi tiết & Đăng ký <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
