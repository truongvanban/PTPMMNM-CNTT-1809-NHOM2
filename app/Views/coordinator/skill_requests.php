<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$requestActionBase = '/coordinator/skill-requests';
?>
<section class="card border-0 shadow-sm rounded-4 p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-patch-plus-fill text-success me-2"></i>Duyệt yêu cầu kỹ năng</h1>
            <p class="text-muted small mb-0">Duyệt để thêm kỹ năng vào danh mục và hồ sơ TNV, hoặc từ chối kèm lý do.</p>
        </div>
        <span class="badge text-bg-secondary"><?= (int)$total ?> yêu cầu</span>
    </div>
    <?php require VIEW_PATH . '/components/skill_requests_table.php'; ?>
    <?php
    $pagination = [
        'url' => $baseUrl . '/coordinator/skill-requests',
        'params' => [],
        'current_page' => $page,
        'last_page' => $lastPage,
        'total' => $total,
        'per_page' => $perPage,
        'visible_count' => count($skillRequests),
        'item_label' => 'yêu cầu kỹ năng',
    ];
    require VIEW_PATH . '/components/pagination.php';
    ?>
</section>
