<?php
/**
 * Danh sách hoạt động công khai (Activities) - VolunteerHub Modern UI
 */
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <span class="vh-section-badge">Cộng Đồng & Lan Tỏa</span>
            <h1 class="h2 fw-bold text-dark mb-1">Danh Sách Hoạt Động Tình Nguyện</h1>
            <p class="text-muted small mb-0">Khám phá và đăng ký tham gia các hoạt động cộng đồng ý nghĩa</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill font-monospace">
                <i class="bi bi-calendar2-check-fill me-1"></i> <?= (int)($pagination['total'] ?? count($activities)) ?> hoạt động
            </span>
        </div>
    </div>

    <!-- Filter & Sort Bar Card -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4" style="background: #ffffff;">
        <form method="GET" action="<?= $baseUrl ?>/activities" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="1">
            <input type="hidden" name="per_page" value="<?= (int)($pagination['per_page'] ?? 10) === 0 ? 'all' : (int)($pagination['per_page'] ?? 10) ?>">
            
            <div class="col-sm-5 col-md-4">
                <label for="activity-category" class="form-label small fw-semibold text-secondary mb-1">
                    <i class="bi bi-tag-fill me-1 text-success"></i> Danh mục hoạt động
                </label>
                <select id="activity-category" name="category" class="form-select rounded-3">
                    <option value="">Tất cả danh mục</option>
                    <?php foreach ($categories as $categoryOption): ?>
                        <option value="<?= htmlspecialchars($categoryOption, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedCategory === $categoryOption ? 'selected' : '' ?>>
                            <?= htmlspecialchars($categoryOption, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-sm-5 col-md-4">
                <label for="activity-sort" class="form-label small fw-semibold text-secondary mb-1">
                    <i class="bi bi-sort-down me-1 text-primary"></i> Sắp xếp theo
                </label>
                <select id="activity-sort" name="sort" class="form-select rounded-3">
                    <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Ngày diễn ra gần nhất</option>
                    <option value="category" <?= $sort === 'category' ? 'selected' : '' ?>>Danh mục (A – Z)</option>
                </select>
            </div>

            <div class="col-sm-2">
                <button type="submit" class="btn btn-success w-100 rounded-3 fw-semibold py-2" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                    <i class="bi bi-funnel-fill me-1"></i> Lọc kết quả
                </button>
            </div>

            <?php if (!empty($selectedCategory)): ?>
                <div class="col-12 mt-2">
                    <div class="d-flex align-items-center gap-2 small">
                        <span class="text-muted">Đang lọc theo:</span>
                        <span class="badge bg-success rounded-pill">
                            <?= htmlspecialchars($selectedCategory) ?>
                            <a href="<?= $baseUrl ?>/activities" class="text-white ms-1 text-decoration-none">&times;</a>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Activities Grid -->
    <?php if (empty($activities)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted" style="background: #ffffff;">
            <div class="display-3 text-secondary opacity-25 mb-3"><i class="bi bi-calendar-x"></i></div>
            <h3 class="h5 fw-bold text-dark">Chưa có hoạt động nào phù hợp</h3>
            <p class="small text-muted mb-3">Hiện chưa có hoạt động nào trong danh mục này hoặc tất cả hoạt động đã kết thúc đăng ký.</p>
            <div>
                <a href="<?= $baseUrl ?>/activities" class="btn btn-sm btn-outline-success rounded-pill px-4">
                    <i class="bi bi-arrow-repeat me-1"></i> Xem tất cả hoạt động
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-4">
            <?php foreach ($activities as $act): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="vh-activity-card">
                        <!-- Top Visual Area -->
                        <div class="vh-activity-cover-fallback">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="vh-activity-status-chip">
                                    <i class="bi bi-record-circle-fill me-1 <?= ($act['status'] ?? 'open') === 'ongoing' ? 'text-primary' : 'text-success' ?>"></i>
                                    <?= ($act['status'] ?? 'open') === 'ongoing' ? 'Đang diễn ra' : 'Đang mở' ?>
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
                            <h2 class="vh-activity-title h5 mb-2">
                                <a href="<?= $baseUrl ?>/activities/<?= $act['id'] ?>" class="text-dark text-decoration-none">
                                    <?= htmlspecialchars($act['title']) ?>
                                </a>
                            </h2>
                            <p class="vh-activity-desc">
                                <?= htmlspecialchars(mb_strimwidth($act['description'] ?? '', 0, 120, '...')) ?>
                            </p>

                            <!-- Meta info -->
                            <div class="vh-activity-meta">
                                <div>
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                    <span><?= htmlspecialchars($act['location'] ?? 'Chưa cập nhật') ?></span>
                                </div>
                                <div>
                                    <i class="bi bi-person-badge-fill text-primary me-1"></i>
                                    <span>Điều phối: <strong><?= htmlspecialchars($act['coordinator_name'] ?? 'BQL') ?></strong></span>
                                </div>
                            </div>

                            <!-- Action button -->
                            <a href="<?= $baseUrl ?>/activities/<?= $act['id'] ?>" class="vh-activity-btn">
                                <span>Xem chi tiết ca & Đăng ký</span>
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php
    $pagination = array_merge($pagination ?? [], [
        'url' => $baseUrl . '/activities',
        'params' => ['category' => $selectedCategory, 'sort' => $sort],
        'visible_count' => count($activities),
        'item_label' => 'hoạt động',
    ]);
    require VIEW_PATH . '/components/pagination.php';
    ?>
</div>
