<?php
/**
 * Danh sách thông báo của tình nguyện viên - VolunteerHub Modern UI
 */
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white" style="max-width: 850px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <span class="vh-section-badge">Hộp Thư Thông Báo</span>
            <h1 class="h3 fw-bold mb-1 text-dark">
                <i class="bi bi-bell-fill text-danger me-2"></i>Thông Báo Của Bạn
            </h1>
            <p class="text-muted small mb-0">Cập nhật tin tức phân công, thay đổi ca trực và thông báo hệ thống</p>
        </div>
        <form action="<?= $baseUrl ?>/volunteer/notifications/read" method="POST" class="m-0">
            <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="page" value="<?= (int)($pagination['current_page'] ?? 1) ?>">
            <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-check2-all me-1"></i> Đánh dấu đã đọc tất cả
            </button>
        </form>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="p-5 text-center text-muted border rounded-3 bg-light">
            <i class="bi bi-bell-slash fs-1 text-secondary opacity-50 mb-2 d-block"></i>
            <h2 class="h5 fw-bold text-dark">Không có thông báo mới</h2>
            <p class="small text-muted mb-0">Các cập nhật quan trọng về lịch phân công và ca trực sẽ hiển thị tại đây.</p>
        </div>
    <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $n): ?>
                <?php
                $isRejection = (bool)preg_match('/từ chối|chưa được chấp nhận/iu', ($n['title'] ?? '') . ' ' . ($n['message'] ?? ''));
                $isUnreadRejection = $isRejection && !(bool)$n['is_read'];
                ?>
                <div class="list-group-item px-3 py-3 rounded-3 mb-2 volunteer-notification <?= !$n['is_read'] ? 'is-unread' : 'border' ?> <?= $isUnreadRejection ? 'is-rejection' : '' ?>">
                    <div class="d-flex w-100 justify-content-between align-items-start mb-1 gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center <?= $isRejection ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?>" style="width: 32px; height: 32px; font-size: 1rem; flex-shrink: 0;">
                                <i class="bi <?= $isRejection ? 'bi-exclamation-circle-fill' : 'bi-bell-fill' ?>"></i>
                            </span>
                            <h2 class="h6 mb-0 fw-bold text-dark volunteer-notification-title">
                                <?= htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8') ?>
                            </h2>
                            <?php if (!$n['is_read']): ?>
                                <span class="badge <?= $isUnreadRejection ? 'bg-danger' : 'bg-success' ?> rounded-pill" style="font-size: 0.65rem;">Mới</span>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted text-nowrap" style="font-size: 0.75rem;">
                            <i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?>
                        </small>
                    </div>
                    <p class="mb-2 text-secondary small ps-4 ms-2"><?= htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8') ?></p>
                    
                    <div class="d-flex align-items-center gap-2 ps-4 ms-2">
                        <?php if (!empty($n['link'])): ?>
                            <form action="<?= $baseUrl ?>/volunteer/notifications/view" method="POST" class="m-0">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                                <button type="submit" class="btn btn-sm <?= $isUnreadRejection ? 'btn-outline-danger' : 'btn-outline-success' ?> rounded-pill py-0 px-3" style="font-size: 0.78rem;">
                                    Xem chi tiết <i class="bi bi-arrow-right"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if (!$n['is_read']): ?>
                            <form action="<?= $baseUrl ?>/volunteer/notifications/read" method="POST" class="d-inline m-0">
                                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                <input type="hidden" name="page" value="<?= (int)($pagination['current_page'] ?? 1) ?>">
                                <button type="submit" class="btn btn-sm btn-link text-muted p-0 text-decoration-none" style="font-size: 0.78rem;">
                                    Đánh dấu đã đọc
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        $pagination = array_merge($pagination ?? [], [
            'url' => $baseUrl . '/volunteer/notifications',
            'params' => [],
            'visible_count' => count($notifications),
            'item_label' => 'thông báo',
        ]);
        require VIEW_PATH . '/components/pagination.php';
        ?>
    <?php endif; ?>
</div>
