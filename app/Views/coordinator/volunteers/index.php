<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="mb-4">
        <h1 class="h4 fw-bold mb-1"><i class="bi bi-people text-success me-2"></i>Danh Sách Tình Nguyện Viên & Kỹ Năng</h1>
        <p class="text-muted small mb-0">Tra cứu hồ sơ, chuyên môn và số ca đã tham gia của các tình nguyện viên</p>
    </div>

    <!-- Search / Filter form -->
    <form action="<?= $baseUrl ?>/coordinator/volunteers" method="GET" class="row g-2 mb-4">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="Tìm theo tên, email, MSSV...">
            </div>
        </div>
        <div class="col-md-4">
            <select class="form-select" name="skill_id">
                <option value="">-- Lọc theo kỹ năng --</option>
                <?php foreach ($allSkills as $sk): ?>
                    <option value="<?= $sk['id'] ?>" <?= $skillId === (int)$sk['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sk['name']) ?> (<?= htmlspecialchars($sk['category']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" name="per_page" value="<?= $perPage === 0 ? 'all' : (int)$perPage ?>">
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Lọc</button>
            <a href="<?= $baseUrl ?>/coordinator/volunteers" class="btn btn-outline-secondary btn-sm">Đặt lại</a>
        </div>
    </form>

    <?php if (empty($volunteers)): ?>
        <div class="p-5 text-center text-muted">
            <i class="bi bi-person-x fs-1"></i>
            <h2 class="h5 mt-2">Không tìm thấy tình nguyện viên phù hợp</h2>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-custom mb-0">
                <thead>
                    <tr>
                        <th>Tình nguyện viên</th>
                        <th>MSSV & Khoa/Lớp</th>
                        <th>Liên hệ</th>
                        <th>Kỹ năng đã khai báo</th>
                        <th class="text-center">Số ca đã tham gia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($volunteers as $v): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($v['full_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($v['email']) ?></small>
                            </td>
                            <td>
                                <div><span class="badge bg-light text-dark border"><?= htmlspecialchars($v['student_id'] ?? '-') ?></span></div>
                                <small class="text-muted"><?= htmlspecialchars($v['faculty'] ?? '') ?> - <?= htmlspecialchars($v['class_name'] ?? '') ?></small>
                            </td>
                            <td>
                                <div class="small"><i class="bi bi-telephone text-success me-1"></i><?= htmlspecialchars($v['phone'] ?? '-') ?></div>
                            </td>
                            <td>
                                <?php if (!empty($v['skills_list'])): ?>
                                    <small class="text-dark"><?= htmlspecialchars($v['skills_list']) ?></small>
                                <?php else: ?>
                                    <span class="text-muted small">Chưa khai báo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success-soft text-success px-3 py-2 fs-6">
                                    <?= (int)$v['total_shifts'] ?> ca
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php
    $pagination = [
        'url' => $baseUrl . '/coordinator/volunteers',
        'params' => ['keyword' => $keyword, 'skill_id' => $skillId ?: ''],
        'current_page' => $page,
        'last_page' => $lastPage,
        'total' => $total,
        'per_page' => $perPage,
        'visible_count' => count($volunteers),
        'item_label' => 'tình nguyện viên',
    ];
    require VIEW_PATH . '/components/pagination.php';
    ?>
</div>
