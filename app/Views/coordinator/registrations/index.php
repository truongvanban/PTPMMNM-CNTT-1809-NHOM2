<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$statusCounts = $statusCounts ?? [];
$perPageValue = $perPage === 0 ? 'all' : (string)$perPage;
$statusTabs = [
    '' => ['label' => 'Tất cả', 'icon' => 'bi-grid-fill', 'class' => 'all'],
    'pending' => ['label' => 'Chờ duyệt', 'icon' => 'bi-clock', 'class' => 'pending'],
    'approved' => ['label' => 'Đã duyệt', 'icon' => 'bi-check-circle-fill', 'class' => 'approved'],
    'rejected' => ['label' => 'Từ chối', 'icon' => 'bi-x-circle-fill', 'class' => 'rejected'],
];
$tabUrl = static function (string $tabStatus) use ($baseUrl, $keyword, $sort, $perPageValue): string {
    return $baseUrl . '/coordinator/registrations?' . http_build_query([
        'status' => $tabStatus,
        'q' => $keyword,
        'sort' => $sort,
        'per_page' => $perPageValue,
        'page' => 1,
    ]);
};
?>
<section class="coordinator-registration-page">
    <header class="coordinator-registration-hero">
        <div class="coordinator-registration-hero-icon"><i class="bi bi-clipboard2-check"></i></div>
        <div>
            <h1>Quản lý đơn đăng ký</h1>
            <p>Xem và phê duyệt các đơn đăng ký tham gia hoạt động của tình nguyện viên</p>
        </div>
    </header>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <div class="coordinator-registration-panel">
        <nav class="coordinator-registration-tabs" aria-label="Lọc đơn theo trạng thái">
            <?php foreach ($statusTabs as $tabStatus => $tab): ?>
                <?php
                $countKey = $tabStatus === '' ? 'all_count' : $tabStatus . '_count';
                $tabActive = $status === $tabStatus;
                ?>
                <a href="<?= htmlspecialchars($tabUrl($tabStatus), ENT_QUOTES, 'UTF-8') ?>"
                   class="coordinator-registration-tab <?= $tab['class'] ?> <?= $tabActive ? 'active' : '' ?>"
                   <?= $tabActive ? 'aria-current="page"' : '' ?>>
                    <i class="bi <?= $tab['icon'] ?>"></i>
                    <span><?= $tab['label'] ?></span>
                    <span class="coordinator-registration-tab-count"><?= (int)($statusCounts[$countKey] ?? 0) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <form method="GET" action="<?= $baseUrl ?>/coordinator/registrations" class="coordinator-registration-filters">
            <input type="hidden" name="page" value="1">
            <input type="hidden" name="per_page" value="<?= htmlspecialchars($perPageValue, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
            <label class="coordinator-registration-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <span class="visually-hidden">Tìm đơn đăng ký</span>
                <input type="search" name="q" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Tìm tình nguyện viên, hoạt động, ca trực...">
            </label>
            <label class="coordinator-registration-sort">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <span class="visually-hidden">Sắp xếp đơn đăng ký</span>
                <select name="sort" aria-label="Sắp xếp đơn đăng ký">
                    <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Mới đăng ký trước</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Cũ đăng ký trước</option>
                    <option value="shift_date" <?= $sort === 'shift_date' ? 'selected' : '' ?>>Ngày ca trực</option>
                </select>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </label>
            <button type="submit" class="btn coordinator-registration-apply">
                <i class="bi bi-funnel me-1"></i> Lọc đơn
            </button>
            <a href="<?= htmlspecialchars($tabUrl($status), ENT_QUOTES, 'UTF-8') ?>"
               class="btn coordinator-registration-reset" title="Làm mới bộ lọc">
                <i class="bi bi-arrow-clockwise"></i><span class="visually-hidden">Làm mới bộ lọc</span>
            </a>
        </form>

        <?php if (empty($registrations)): ?>
            <div class="coordinator-registration-empty">
                <i class="bi bi-inbox"></i>
                <h2><?= $keyword !== '' || $status !== '' ? 'Không tìm thấy đơn đăng ký phù hợp' : 'Chưa có đơn đăng ký nào' ?></h2>
                <p><?= $keyword !== '' || $status !== ''
                    ? 'Thử thay đổi từ khóa hoặc trạng thái lọc.'
                    : 'Đơn đăng ký của tình nguyện viên sẽ hiển thị tại đây.' ?></p>
                <?php if ($keyword !== '' || $status !== ''): ?>
                    <a href="<?= $baseUrl ?>/coordinator/registrations" class="btn coordinator-registration-apply">Xóa bộ lọc</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive coordinator-registration-table-wrap">
                <table class="table coordinator-registration-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="registration-number">#</th>
                            <th>Tình nguyện viên</th>
                            <th>Ca & hoạt động</th>
                            <th>Thời gian</th>
                            <th>Ngày đăng ký</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $index => $r): ?>
                            <?php
                            $rowNumber = (($page - 1) * ($perPage ?: count($registrations))) + $index + 1;
                            $statusLabel = match ($r['status']) {
                                'approved' => ['Đã duyệt', 'approved'],
                                'pending' => ['Chờ duyệt', 'pending'],
                                'rejected' => ['Từ chối', 'rejected'],
                                'withdrawn' => ['Đã rút', 'muted'],
                                'cancelled' => ['Đã hủy', 'muted'],
                                default => [ucfirst((string)$r['status']), 'muted'],
                            };
                            ?>
                            <tr>
                                <td class="registration-number"><?= (int)$rowNumber ?></td>
                                <td>
                                    <div class="coordinator-registration-volunteer">
                                        <span class="coordinator-registration-avatar">
                                            <?= htmlspecialchars(mb_strtoupper(mb_substr($r['volunteer_name'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span>
                                            <strong><?= htmlspecialchars($r['volunteer_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small><?= htmlspecialchars($r['volunteer_email'], ENT_QUOTES, 'UTF-8') ?></small>
                                            <?php if (!empty($r['phone'])): ?>
                                                <small><i class="bi bi-telephone"></i> <?= htmlspecialchars($r['phone'], ENT_QUOTES, 'UTF-8') ?></small>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="coordinator-registration-activity">
                                        <?php if (!empty($r['image_url'])): ?>
                                            <img src="<?= $baseUrl . htmlspecialchars($r['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                                        <?php else: ?>
                                            <span class="coordinator-registration-activity-placeholder"><i class="bi bi-heart-pulse"></i></span>
                                        <?php endif; ?>
                                        <span>
                                            <strong><?= htmlspecialchars($r['activity_title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small><?= htmlspecialchars($r['shift_name'], ENT_QUOTES, 'UTF-8') ?></small>
                                            <?php if (!empty($r['note'])): ?>
                                                <small class="coordinator-registration-note">Ghi chú: <?= htmlspecialchars($r['note'], ENT_QUOTES, 'UTF-8') ?></small>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="coordinator-registration-time">
                                    <strong><?= htmlspecialchars(substr($r['time_start'], 0, 5), ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(substr($r['time_end'], 0, 5), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small><?= date('d/m/Y', strtotime($r['shift_date'])) ?></small>
                                </td>
                                <td class="coordinator-registration-time">
                                    <strong><?= date('d/m/Y', strtotime($r['registered_at'])) ?></strong>
                                    <small><?= date('H:i', strtotime($r['registered_at'])) ?></small>
                                </td>
                                <td><span class="coordinator-registration-status <?= $statusLabel[1] ?>"><i class="bi bi-circle-fill"></i><?= $statusLabel[0] ?></span></td>
                                <td class="text-end">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <div class="coordinator-registration-actions">
                                            <form action="<?= $baseUrl ?>/coordinator/registrations/approve" method="POST" class="m-0">
                                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                <input type="hidden" name="return_status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="return_page" value="<?= (int)$page ?>">
                                                <input type="hidden" name="return_per_page" value="<?= htmlspecialchars($perPageValue, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="return_keyword" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="return_sort" value="<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>">
                                                <button type="submit" class="btn coordinator-registration-approve" title="Duyệt đơn">
                                                    <i class="bi bi-check-lg"></i><span>Duyệt</span>
                                                </button>
                                            </form>
                                            <form action="<?= $baseUrl ?>/coordinator/registrations/reject" method="POST" class="m-0">
                                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                <input type="hidden" name="return_status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="return_page" value="<?= (int)$page ?>">
                                                <input type="hidden" name="return_per_page" value="<?= htmlspecialchars($perPageValue, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="return_keyword" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="return_sort" value="<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>">
                                                <button type="submit" class="btn coordinator-registration-reject" title="Từ chối đơn">
                                                    <i class="bi bi-x-lg"></i><span class="visually-hidden">Từ chối</span>
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="coordinator-registration-no-action">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php
    $pagination = [
        'url' => $baseUrl . '/coordinator/registrations',
        'params' => ['status' => $status, 'q' => $keyword, 'sort' => $sort],
        'current_page' => $page,
        'last_page' => $lastPage,
        'total' => $total,
        'per_page' => $perPage,
        'visible_count' => count($registrations),
        'item_label' => 'đơn đăng ký',
    ];
    require VIEW_PATH . '/components/pagination.php';
    ?>
</section>
