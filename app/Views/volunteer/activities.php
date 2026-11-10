<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$gradients = [
    'linear-gradient(135deg, #198754 0%, #20c997 100%)',
    'linear-gradient(135deg, #0d6efd 0%, #6610f2 100%)',
    'linear-gradient(135deg, #fd7e14 0%, #dc3545 100%)',
    'linear-gradient(135deg, #6f42c1 0%, #d63384 100%)',
    'linear-gradient(135deg, #0dcaf0 0%, #198754 100%)',
    'linear-gradient(135deg, #20c997 0%, #0d6efd 100%)',
];
?>
<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1"><i class="bi bi-compass text-success me-2"></i>Khám Phá Hoạt Động Tình Nguyện</h1>
        <p class="text-muted small mb-0">Tìm và đăng ký tham gia các hoạt động phù hợp với bạn</p>
    </div>
    <a href="<?= $baseUrl ?>/volunteer/shifts" class="btn btn-outline-success btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Đăng ký theo ca trực
    </a>
</div>

<form method="GET" action="<?= $baseUrl ?>/volunteer/activities" class="row g-2 align-items-end mb-4">
    <input type="hidden" name="page" value="1">
    <input type="hidden" name="per_page" value="<?= (int)($pagination['per_page'] ?? 10) === 0 ? 'all' : (int)($pagination['per_page'] ?? 10) ?>">
    <div class="col-sm-5 col-md-4">
        <label for="activity-category" class="form-label small fw-semibold">Danh mục</label>
        <select id="activity-category" name="category" class="form-select">
            <option value="">Tất cả danh mục</option>
            <?php foreach ($categories as $categoryOption): ?>
                <option value="<?= htmlspecialchars($categoryOption, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedCategory === $categoryOption ? 'selected' : '' ?>>
                    <?= htmlspecialchars($categoryOption, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-5 col-md-4">
        <label for="activity-sort" class="form-label small fw-semibold">Sắp xếp</label>
        <select id="activity-sort" name="sort" class="form-select">
            <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Ngày diễn ra</option>
            <option value="category" <?= $sort === 'category' ? 'selected' : '' ?>>Danh mục A–Z</option>
        </select>
    </div>
    <div class="col-sm-2">
        <button type="submit" class="btn btn-success w-100">Áp dụng</button>
    </div>
</form>

<?php if (empty($activities)): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
        <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
        <h2 class="h5 fw-bold mt-3">Chưa có hoạt động nào đang mở</h2>
        <p class="text-muted small">Hãy quay lại sau hoặc theo dõi thông báo mới từ Điều phối viên.</p>
        <a href="<?= $baseUrl ?>/volunteer/dashboard" class="btn btn-success btn-sm mt-2">
            <i class="bi bi-arrow-left me-1"></i> Về Tổng quan
        </a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($activities as $i => $act): ?>
            <?php $gradient = $gradients[$i % count($gradients)]; ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden activity-browse-card">
                    <!-- Gradient Header -->
                    <div class="p-4 text-white position-relative" style="background: <?= $gradient ?>; min-height: 120px;">
                        <div class="position-absolute top-0 end-0 m-3">
                            <span class="badge bg-white text-success fw-semibold shadow-sm">
                                <i class="bi bi-check-circle-fill me-1"></i>Đang mở
                            </span>
                        </div>
                        <div class="position-absolute bottom-0 start-0 p-3" style="width: 100%;">
                            <h3 class="h5 fw-bold mb-0 text-white"><?= htmlspecialchars($act['title']) ?></h3>
                        </div>
                        <!-- Decorative circles -->
                        <div class="position-absolute" style="top: -20px; right: -20px; width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.1);"></div>
                        <div class="position-absolute" style="bottom: -30px; left: 40%; width: 60px; height: 60px; border-radius: 50%; background: rgba(255,255,255,0.08);"></div>
                    </div>

                    <!-- Card Body -->
                    <div class="card-body d-flex flex-column p-4">
                        <p class="text-muted small mb-3">
                            <?= htmlspecialchars(mb_strimwidth($act['description'] ?? 'Chưa có mô tả chi tiết.', 0, 120, '...')) ?>
                        </p>

                        <div class="d-flex flex-column gap-2 mb-3 small">
                            <?php if (!empty($act['category'])): ?>
                                <div class="d-flex align-items-center text-muted">
                                    <i class="bi bi-tag-fill text-success me-2" style="width: 16px;"></i>
                                    <span><?= htmlspecialchars($act['category']) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex align-items-center text-muted">
                                <i class="bi bi-geo-alt-fill text-danger me-2" style="width: 16px;"></i>
                                <span><?= htmlspecialchars($act['location'] ?? 'Chưa xác định') ?></span>
                            </div>
                            <div class="d-flex align-items-center text-muted">
                                <i class="bi bi-calendar3 text-primary me-2" style="width: 16px;"></i>
                                <span><?= date('d/m/Y', strtotime($act['date_start'])) ?></span>
                            </div>
                            <div class="d-flex align-items-center text-muted">
                                <i class="bi bi-person-badge text-info me-2" style="width: 16px;"></i>
                                <span>ĐPV: <?= htmlspecialchars($act['coordinator_name']) ?></span>
                            </div>
                        </div>

                        <!-- Stats Row -->
                        <div class="d-flex gap-3 mb-3">
                            <div class="text-center flex-fill p-2 rounded-3" style="background: #e8f5e9;">
                                <div class="fw-bold text-success"><?= (int)$act['shift_count'] ?></div>
                                <div class="text-muted" style="font-size: 0.7rem;">Ca trực</div>
                            </div>
                            <div class="text-center flex-fill p-2 rounded-3" style="background: #e3f2fd;">
                                <div class="fw-bold text-primary"><?= (int)($act['registered_count'] ?? 0) ?></div>
                                <div class="text-muted" style="font-size: 0.7rem;">Đã ĐK</div>
                            </div>
                            <div class="text-center flex-fill p-2 rounded-3" style="background: #fff3e0;">
                                <div class="fw-bold text-warning"><?= (int)($act['max_volunteers'] ?? 0) ?: '∞' ?></div>
                                <div class="text-muted" style="font-size: 0.7rem;">Tối đa</div>
                            </div>
                        </div>

                        <!-- CTA Button -->
                        <a href="<?= $baseUrl ?>/volunteer/activities/<?= $act['id'] ?>" class="btn btn-success w-100 mt-auto rounded-3">
                            <i class="bi bi-eye me-1"></i> Xem chi tiết & Đăng ký
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>
<?php
$pagination = array_merge($pagination ?? [], [
    'url' => $baseUrl . '/volunteer/activities',
    'params' => ['category' => $selectedCategory, 'sort' => $sort],
    'visible_count' => count($activities),
    'item_label' => 'hoạt động',
]);
require VIEW_PATH . '/components/pagination.php';
?>
