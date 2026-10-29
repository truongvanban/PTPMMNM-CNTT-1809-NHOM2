<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/coordinator/activities">Hoạt động</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($activity['title']) ?></li>
                </ol>
            </nav>
            <h1 class="h4 fw-bold mb-0">Quản Lý Ca Trực: <?= htmlspecialchars($activity['title']) ?></h1>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $baseUrl ?>/coordinator/shifts/create?activity_id=<?= $activity['id'] ?>" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Thêm ca trực mới
            </a>
            <a href="<?= $baseUrl ?>/coordinator/assignment/run?activity_id=<?= $activity['id'] ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-cpu me-1"></i> Chạy phân công ca này
            </a>
        </div>
    </div>

    <?php if (empty($shifts)): ?>
        <div class="p-5 text-center text-muted">
            <i class="bi bi-calendar-x fs-1"></i>
            <h2 class="h5 mt-2">Hoạt động này chưa có ca trực nào</h2>
            <p class="small">Hãy tạo các ca trực buổi sáng, chiều hoặc tối để tiếp nhận đăng ký.</p>
            <a href="<?= $baseUrl ?>/coordinator/shifts/create?activity_id=<?= $activity['id'] ?>" class="btn btn-success btn-sm">
                Thêm ca trực đầu tiên
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-custom mb-0">
                <thead>
                    <tr>
                        <th>Tên ca & Mô tả</th>
                        <th>Ngày & Giờ</th>
                        <th>Đăng ký / Phân công</th>
                        <th>Kỹ năng yêu cầu</th>
                        <th>Trạng thái</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shifts as $s): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($s['name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($s['description'] ?? '') ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold text-success"><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($s['date'])) ?></div>
                                <small class="text-muted"><?= substr($s['time_start'], 0, 5) ?> - <?= substr($s['time_end'], 0, 5) ?></small>
                            </td>
                            <td>
                                <?php 
                                    $assigned = (int)$s['assigned_count'];
                                    $min = (int)$s['min_volunteers'];
                                    $max = (int)$s['max_volunteers'];
                                    $isShortage = $assigned < $min;
                                ?>
                                <?php
                                    $registered = (int)$s['registered_count'];
                                    $pending = (int)$s['pending_count'];
                                ?>
                                <div class="fw-semibold">
                                    Đăng ký: <?= $registered ?> / <?= $max ?>
                                </div>
                                <div class="small text-muted">
                                    Chờ duyệt: <?= $pending ?>
                                </div>
                                <div class="small mt-1">
                                    Đã phân công: <?= $assigned ?> / <?= $max ?>
                                </div>
                                <?php
                                    if (!$isShortage) {
                                        $statusLabel = 'Đủ phân công tối thiểu';
                                        $statusClass = 'bg-success-soft text-success';
                                    } elseif ($registered >= $min) {
                                        $statusLabel = $pending > 0
                                            ? 'Đủ đăng ký, chờ duyệt / phân công'
                                            : 'Đủ đăng ký, chờ phân công';
                                        $statusClass = 'bg-warning-soft text-dark';
                                    } else {
                                        $statusLabel = 'Thiếu ' . ($min - $registered) . ' đăng ký';
                                        $statusClass = 'bg-danger-soft text-danger';
                                    }
                                ?>
                                <span class="badge <?= $statusClass ?> p-2">
                                    <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <div class="small text-muted mt-1">
                                    Tối thiểu: <?= $min ?> phân công
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($s['required_skills'])): ?>
                                    <span class="badge bg-warning-soft text-dark"><i class="bi bi-star-fill text-warning me-1"></i><?= htmlspecialchars($s['required_skills']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">Không</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['status'] === 'open'): ?>
                                    <span class="badge bg-success">Mở</span>
                                <?php elseif ($s['status'] === 'full'): ?>
                                    <span class="badge bg-primary">Đã đủ</span>
                                <?php elseif ($s['status'] === 'closed'): ?>
                                    <span class="badge bg-secondary">Đã đóng</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($s['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="shift-actions">
                                    <a href="<?= $baseUrl ?>/coordinator/assignment/manual/<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary" title="Điều chỉnh phân công">
                                        <i class="bi bi-people-fill"></i> Phân công
                                    </a>
                                    <a href="<?= $baseUrl ?>/coordinator/shifts/<?= $s['id'] ?>/edit" class="btn btn-sm btn-outline-secondary" title="Sửa ca">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if (in_array($s['status'], ['open', 'full'], true)): ?>
                                        <form action="<?= $baseUrl ?>/coordinator/shifts/<?= $s['id'] ?>/close" method="POST" class="shift-action-form" onsubmit="return confirm('Bạn có chắc muốn đóng ca trực này?');">
                                            <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Đóng ca trực">
                                                <i class="bi bi-lock"></i> Đóng ca
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php
    $pagination = [
        'url' => $baseUrl . '/coordinator/activities/' . (int)$activity['id'] . '/shifts',
        'params' => [],
        'current_page' => $page,
        'last_page' => $lastPage,
        'total' => $total,
        'per_page' => $perPage,
        'visible_count' => count($shifts),
        'item_label' => 'ca trực',
    ];
    require VIEW_PATH . '/components/pagination.php';
    ?>
</div>
