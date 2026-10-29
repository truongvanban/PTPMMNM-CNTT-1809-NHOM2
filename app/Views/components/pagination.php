<?php
$paginationUrl = $pagination['url'];
$paginationParams = $pagination['params'] ?? [];
$paginationPageParam = $pagination['page_param'] ?? 'page';
$paginationSizeId = $pagination['size_id'] ?? 'management-page-size';
$paginationCurrentPage = (int)$pagination['current_page'];
$paginationLastPage = (int)$pagination['last_page'];
$paginationTotal = (int)$pagination['total'];
$paginationPerPage = (int)$pagination['per_page'];
$paginationItemLabel = $pagination['item_label'] ?? 'mục';
$paginationSizeValue = $paginationPerPage === 0 ? 'all' : (string)$paginationPerPage;
$paginationLink = static function (int $targetPage) use (
    $paginationUrl,
    $paginationParams,
    $paginationSizeValue,
    $paginationPageParam
): string {
    return $paginationUrl . '?' . http_build_query(array_merge($paginationParams, [
        $paginationPageParam => $targetPage,
        'per_page' => $paginationSizeValue,
    ]));
};
?>
<div class="management-pagination">
    <div class="management-pagination-summary" aria-live="polite">
        Hiển thị <strong><?= (int)$pagination['visible_count'] ?></strong> / <strong><?= $paginationTotal ?></strong>
        <?= htmlspecialchars($paginationItemLabel, ENT_QUOTES, 'UTF-8') ?>
        <span class="mx-1">·</span> Mỗi trang
        <form action="<?= htmlspecialchars($paginationUrl, ENT_QUOTES, 'UTF-8') ?>" method="GET" class="d-inline">
            <?php foreach ($paginationParams as $paramName => $paramValue): ?>
                <?php if (!in_array($paramName, ['page', $paginationPageParam, 'per_page'], true) && is_scalar($paramValue)): ?>
                    <input type="hidden" name="<?= htmlspecialchars((string)$paramName, ENT_QUOTES, 'UTF-8') ?>"
                           value="<?= htmlspecialchars((string)$paramValue, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <input type="hidden" name="<?= htmlspecialchars($paginationPageParam, ENT_QUOTES, 'UTF-8') ?>" value="1">
            <label class="visually-hidden" for="<?= htmlspecialchars($paginationSizeId, ENT_QUOTES, 'UTF-8') ?>">Số mục mỗi trang</label>
            <select id="<?= htmlspecialchars($paginationSizeId, ENT_QUOTES, 'UTF-8') ?>" name="per_page" class="form-select form-select-sm management-page-size"
                    onchange="this.form.submit()">
                <?php foreach ([5, 10, 15, 20] as $pageSizeOption): ?>
                    <option value="<?= $pageSizeOption ?>" <?= $paginationSizeValue === (string)$pageSizeOption ? 'selected' : '' ?>>
                        <?= $pageSizeOption ?>
                    </option>
                <?php endforeach; ?>
                <option value="all" <?= $paginationSizeValue === 'all' ? 'selected' : '' ?>>Tất cả</option>
            </select>
        </form>
        mục
    </div>

    <?php if ($paginationLastPage > 1): ?>
        <?php
        $paginationStart = max(1, $paginationCurrentPage - 2);
        $paginationEnd = min($paginationLastPage, $paginationCurrentPage + 2);
        ?>
        <nav aria-label="Phân trang <?= htmlspecialchars($paginationItemLabel, ENT_QUOTES, 'UTF-8') ?>">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $paginationCurrentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($paginationLink(max(1, $paginationCurrentPage - 1)), ENT_QUOTES, 'UTF-8') ?>"
                       aria-label="Trang trước">
                        <i class="bi bi-chevron-left"></i><span class="d-none d-sm-inline ms-1">Trước</span>
                    </a>
                </li>
                <?php if ($paginationStart > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($paginationLink(1), ENT_QUOTES, 'UTF-8') ?>">1</a></li>
                    <?php if ($paginationStart > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                <?php endif; ?>
                <?php for ($pageNumber = $paginationStart; $pageNumber <= $paginationEnd; $pageNumber++): ?>
                    <li class="page-item <?= $pageNumber === $paginationCurrentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars($paginationLink($pageNumber), ENT_QUOTES, 'UTF-8') ?>"
                           <?= $pageNumber === $paginationCurrentPage ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($paginationEnd < $paginationLastPage): ?>
                    <?php if ($paginationEnd < $paginationLastPage - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($paginationLink($paginationLastPage), ENT_QUOTES, 'UTF-8') ?>"><?= $paginationLastPage ?></a></li>
                <?php endif; ?>
                <li class="page-item <?= $paginationCurrentPage >= $paginationLastPage ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($paginationLink(min($paginationLastPage, $paginationCurrentPage + 1)), ENT_QUOTES, 'UTF-8') ?>"
                       aria-label="Trang sau">
                        <span class="d-none d-sm-inline me-1">Sau</span><i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
