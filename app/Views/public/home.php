<?php
/**
 * Trang chủ công khai (Home) - VolunteerHub Modern UI
 */
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>

<!-- Hero Banner Section -->
<section class="vh-hero text-center position-relative">
    <div class="container position-relative" style="z-index: 2;">
        <!-- Kicker Badge -->
        <div class="d-inline-block">
            <span class="vh-hero-kicker">
                <span class="vh-pulse-dot"></span>
                Nền tảng Tình Nguyện Viên Thông Minh 4.0
            </span>
        </div>

        <!-- Main Title -->
        <h1 class="vh-hero-title">
            Kết Nối Trái Tim — <br class="d-none d-md-block">
            <span class="vh-gradient-text">Tối Ưu Hóa Phân Công</span> Ca Trực
        </h1>

        <!-- Subtitle -->
        <p class="vh-hero-lead">
            Giải pháp số hóa toàn diện điều phối tình nguyện viên. Tự động đề xuất lịch trực thông minh 
            dựa trên thuật toán <strong>Improved Greedy (MCF)</strong>, ghép nối chính xác kỹ năng, 
            loại trừ 100% trùng lịch và cân bằng khối lượng công việc.
        </p>

        <!-- CTA Buttons -->
        <div class="vh-hero-buttons">
            <a href="<?= $baseUrl ?>/activities" class="vh-btn-hero-primary">
                <i class="bi bi-compass"></i> Khám phá hoạt động <i class="bi bi-arrow-right"></i>
            </a>
            <a href="<?= $baseUrl ?>/register" class="vh-btn-hero-outline">
                <i class="bi bi-person-plus"></i> Đăng ký tình nguyện viên
            </a>
        </div>

        <!-- Floating Live Stats Cards -->
        <div class="row g-3 vh-hero-stats-row justify-content-center">
            <div class="col-md-4 col-sm-6">
                <div class="vh-hero-stat-card">
                    <div class="vh-hero-stat-icon vh-stat-icon-emerald">
                        <i class="bi bi-cpu-fill"></i>
                    </div>
                    <div>
                        <div class="vh-hero-stat-number">Thuật Toán MCF</div>
                        <div class="vh-hero-stat-label">Cân bằng tải & Tối ưu ca khó</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="vh-hero-stat-card">
                    <div class="vh-hero-stat-icon vh-stat-icon-cyan">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <div class="vh-hero-stat-number">100% Trực Quan</div>
                        <div class="vh-hero-stat-label">Không trùng ca, kiểm soát xung đột</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="vh-hero-stat-card">
                    <div class="vh-hero-stat-icon vh-stat-icon-amber">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <div>
                        <div class="vh-hero-stat-number">Gamification</div>
                        <div class="vh-hero-stat-label">Huy hiệu thành tích & Điểm cống hiến</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Grid Section -->
<section class="container mb-5 pb-3">
    <div class="text-center mb-5">
        <span class="vh-section-badge">Tính Năng Vượt Trội</span>
        <h2 class="vh-section-title">Tại Sao Nên Chọn VolunteerHub?</h2>
        <p class="vh-section-lead mx-auto">
            Bộ công cụ mạnh mẽ được tối ưu hóa cho cả Tình nguyện viên, Điều phối viên và Ban quản lý
        </p>
    </div>

    <div class="row g-4">
        <!-- Feature 1 -->
        <div class="col-lg-3 col-md-6">
            <div class="vh-feature-card">
                <div class="vh-feature-icon-wrap" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                    <i class="bi bi-diagram-3-fill"></i>
                </div>
                <h3 class="vh-feature-title">Phân Công Thông Minh</h3>
                <p class="vh-feature-desc">
                    Thuật toán tự động hóa xếp lịch ưu tiên ca ít ứng viên (Most Constrained First), thẩm định kỹ năng bắt buộc và bảo đảm độ công bằng Jain's Index cao nhất.
                </p>
                <span class="vh-feature-tag"><i class="bi bi-check2-circle me-1"></i> Tối ưu MCF</span>
            </div>
        </div>

        <!-- Feature 2 -->
        <div class="col-lg-3 col-md-6">
            <div class="vh-feature-card">
                <div class="vh-feature-icon-wrap" style="background: rgba(6, 182, 212, 0.12); color: #06b6d4;">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <h3 class="vh-feature-title">Báo Vắng & Gợi Ý Thay Thế</h3>
                <p class="vh-feature-desc">
                    TNV gửi yêu cầu xin vắng trong 1 chạm. Hệ thống thông minh gợi ý ngay danh sách TNV rảnh và đủ điều kiện để điều phối viên duyệt tức thì.
                </p>
                <span class="vh-feature-tag"><i class="bi bi-lightning-charge me-1"></i> Xử lý tức thì</span>
            </div>
        </div>

        <!-- Feature 3 -->
        <div class="col-lg-3 col-md-6">
            <div class="vh-feature-card">
                <div class="vh-feature-icon-wrap" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <h3 class="vh-feature-title">Hệ Thống Gamification</h3>
                <p class="vh-feature-desc">
                    Tích lũy Điểm Cống Hiến sau mỗi ca trực, mở khóa các danh hiệu cấp bậc và bộ 4 huy hiệu thành tích vinh danh đóng góp cho cộng đồng.
                </p>
                <span class="vh-feature-tag"><i class="bi bi-star-fill me-1"></i> Truyền cảm hứng</span>
            </div>
        </div>

        <!-- Feature 4 -->
        <div class="col-lg-3 col-md-6">
            <div class="vh-feature-card">
                <div class="vh-feature-icon-wrap" style="background: rgba(99, 102, 241, 0.12); color: #6366f1;">
                    <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                </div>
                <h3 class="vh-feature-title">Báo Cáo & Xuất CSV UTF-8</h3>
                <p class="vh-feature-desc">
                    Xuất danh sách phân công ra file CSV đính kèm BOM UTF-8 mở trực tiếp trên Excel không lỗi font, kèm bảng biểu đồ đối sánh thuật toán thời gian thực.
                </p>
                <span class="vh-feature-tag"><i class="bi bi-graph-up me-1"></i> Chuẩn UTF-8 BOM</span>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section (3 bước) -->
<section class="py-5 bg-light mb-5">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="vh-section-badge">Quy Trình 3 Bước</span>
            <h2 class="vh-section-title">Tham Gia Dễ Dàng Chỉ Trong Vài Phút</h2>
            <p class="vh-section-lead mx-auto">
                Bắt đầu hành trình lan tỏa điều tử tế qua 3 bước tiện lợi và hoàn toàn tự động
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="vh-step-card">
                    <div class="vh-step-badge">1</div>
                    <h3 class="vh-step-title">Khám Phá & Chọn Ca Trực</h3>
                    <p class="vh-step-desc">
                        Lựa chọn các hoạt động tình nguyện ý nghĩa gần bạn, tìm ca trực phù hợp với thời gian rảnh và kỹ năng của bản thân.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="vh-step-card">
                    <div class="vh-step-badge">2</div>
                    <h3 class="vh-step-title">Hệ Thống Phân Công Tự Động</h3>
                    <p class="vh-step-desc">
                        Thuật toán tự động xếp lịch thông minh, bảo đảm không trùng lịch và ưu tiên theo độ phù hợp chuyên môn của bạn.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="vh-step-card">
                    <div class="vh-step-badge">3</div>
                    <h3 class="vh-step-title">Xác Nhận & Cùng Lan Tỏa</h3>
                    <p class="vh-step-desc">
                        Xem lịch trực trên bảng điều khiển cá nhân, bấm xác nhận tham gia và nhận điểm cống hiến cùng các huy hiệu danh giá.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Activities Section -->
<section class="container mb-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-2">
        <div>
            <span class="vh-section-badge">Cơ Hội Cống Hiến</span>
            <h2 class="vh-section-title mb-1">Hoạt Động Đang Mở Đăng Ký</h2>
            <p class="text-muted mb-0 small">Tham gia ngay các hoạt động cộng đồng nổi bật để cùng chung tay tạo dựng giá trị</p>
        </div>
        <a href="<?= $baseUrl ?>/activities" class="btn btn-outline-success btn-sm rounded-pill px-3 py-2 fw-semibold">
            Xem tất cả hoạt động <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <?php if (empty($activities)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted" style="background: #ffffff;">
            <div class="display-4 text-success opacity-50 mb-3"><i class="bi bi-calendar-heart"></i></div>
            <h3 class="h5 fw-bold text-dark">Hiện tại chưa có hoạt động nào đang mở đăng ký</h3>
            <p class="small text-muted mb-0">Các hoạt động mới sẽ sớm được cập nhật. Bạn vui lòng quay lại sau nhé!</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($activities as $act): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="vh-activity-card">
                        <!-- Top Visual Area -->
                        <div class="vh-activity-cover-fallback">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="vh-activity-status-chip">
                                    <i class="bi bi-record-circle-fill me-1 text-success"></i> Đang mở
                                </span>
                                <?php if (!empty($act['category'])): ?>
                                    <span class="badge bg-white bg-opacity-25 text-white" style="font-size: 0.72rem; border: 1px solid rgba(255,255,255,0.3);">
                                        <?= htmlspecialchars($act['category']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="vh-activity-date-chip">
                                    <i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($act['date_start'])) ?>
                                </span>
                                <span class="badge bg-dark bg-opacity-50 text-white" style="font-size: 0.72rem;">
                                    <i class="bi bi-grid-fill me-1"></i> <?= (int)$act['shift_count'] ?> ca trực
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="vh-activity-body">
                            <h3 class="vh-activity-title">
                                <a href="<?= $baseUrl ?>/activities/<?= $act['id'] ?>" class="text-dark text-decoration-none">
                                    <?= htmlspecialchars($act['title']) ?>
                                </a>
                            </h3>
                            <p class="vh-activity-desc">
                                <?= htmlspecialchars(mb_strimwidth($act['description'] ?? '', 0, 115, '...')) ?>
                            </p>

                            <!-- Meta info -->
                            <div class="vh-activity-meta">
                                <div>
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                    <span><?= htmlspecialchars($act['location'] ?? 'Chưa xác định') ?></span>
                                </div>
                                <?php if (!empty($act['coordinator_name'])): ?>
                                    <div>
                                        <i class="bi bi-person-badge-fill text-primary me-1"></i>
                                        <span>Điều phối: <strong><?= htmlspecialchars($act['coordinator_name']) ?></strong></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Action button -->
                            <a href="<?= $baseUrl ?>/activities/<?= $act['id'] ?>" class="vh-activity-btn">
                                <span>Xem chi tiết & Đăng ký</span>
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Impact Numbers Banner -->
<section class="container">
    <div class="vh-impact-banner">
        <div class="row g-4 text-center">
            <div class="col-6 col-lg-3">
                <div class="vh-impact-number">1,250+</div>
                <div class="vh-impact-label">Giờ cống hiến vì cộng đồng</div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="vh-impact-number">100%</div>
                <div class="vh-impact-label">Không trùng lịch (Conflict-Free)</div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="vh-impact-number">98.5%</div>
                <div class="vh-impact-label">Tỷ lệ lấp đầy ca trực</div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="vh-impact-number">4.9 / 5</div>
                <div class="vh-impact-label">Mức độ hài lòng của TNV</div>
            </div>
        </div>
    </div>
</section>

<!-- Bottom CTA Banner -->
<section class="container">
    <div class="vh-cta-card">
        <h2 class="vh-cta-title">Sẵn Sàng Tạo Nên Sự Khác Biệt?</h2>
        <p class="vh-cta-lead">
            Hàng trăm hoạt động tình nguyện ý nghĩa đang chờ đón sự chung tay của bạn. 
            Đăng ký tài khoản ngay hôm nay để trở thành một mảnh ghép nhân ái trong cộng đồng!
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="<?= $baseUrl ?>/register" class="btn btn-light text-success fw-bold px-4 py-2 rounded-pill shadow-sm">
                <i class="bi bi-person-plus-fill me-1"></i> Đăng ký tình nguyện viên ngay
            </a>
            <a href="<?= $baseUrl ?>/activities" class="btn btn-outline-light fw-bold px-4 py-2 rounded-pill">
                <i class="bi bi-compass me-1"></i> Khám phá các hoạt động
            </a>
        </div>
    </div>
</section>
