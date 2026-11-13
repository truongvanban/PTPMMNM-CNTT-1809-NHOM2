<?php
/**
 * Kỹ năng đã được duyệt và yêu cầu kỹ năng của tình nguyện viên
 */
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$statusLabels = [
    'pending' => ['Đang chờ duyệt', 'warning'],
    'approved' => ['Đã duyệt', 'success'],
    'rejected' => ['Bị từ chối', 'danger'],
];
$hasPendingSkillRequest = count(array_filter(
    $skillRequests,
    static fn (array $request): bool => $request['status'] === 'pending'
)) > 0;
?>
<div class="row g-4">
    <div class="col-lg-7">
        <section class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <span class="vh-section-badge">Hồ Sơ Năng Lực</span>
                    <h1 class="h3 fw-bold mb-1 text-dark"><i class="bi bi-patch-check-fill text-success me-2"></i>Kỹ năng của tôi</h1>
                    <p class="text-muted small mb-0">Chỉ kỹ năng đã được duyệt mới được sử dụng để ghép nối ca trực.</p>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                    <?= count($mySkills) ?> kỹ năng
                </span>
            </div>

            <?php if (empty($mySkills)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-award fs-2"></i>
                    <p class="mb-0 mt-2">Bạn chưa có kỹ năng nào được duyệt.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($mySkills as $skill): ?>
                        <?php
                        $levelLabels = [
                            'beginner' => 'Cơ bản',
                            'intermediate' => 'Trung bình',
                            'advanced' => 'Thành thạo',
                        ];
                        ?>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center gap-3">
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($skill['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <small class="text-muted"><?= htmlspecialchars($skill['category'] ?? 'Chung', ENT_QUOTES, 'UTF-8') ?></small>
                            </div>
                            <span class="badge bg-light text-dark border">
                                <?= htmlspecialchars($levelLabels[$skill['level']] ?? 'Cơ bản', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h2 class="h5 fw-bold mb-1"><i class="bi bi-patch-plus text-success me-2"></i>Đề xuất kỹ năng mới</h2>
            <p class="text-muted small mb-3">Tối đa 3 yêu cầu mỗi tháng và chỉ có thể có một yêu cầu đang chờ duyệt.</p>
            <?php if ($hasPendingSkillRequest): ?>
                <div class="alert alert-info mb-0">Bạn đang có yêu cầu chờ duyệt. Vui lòng đợi kết quả trước khi gửi yêu cầu mới.</div>
            <?php elseif ($monthlyRequestCount >= 3): ?>
                <div class="alert alert-warning mb-0">Bạn đã đạt giới hạn 3 yêu cầu trong tháng này.</div>
            <?php else: ?>
                <form action="<?= $baseUrl ?>/volunteer/skills/request" method="POST">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="skill_name">Tên kỹ năng</label>
                        <input class="form-control" id="skill_name" name="skill_name" maxlength="150" minlength="2" required
                               placeholder="VD: Sơ cứu viên">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="proof">Bằng chứng / mô tả</label>
                        <textarea class="form-control" id="proof" name="proof" rows="4" maxlength="5000" required
                                  placeholder="Mô tả kinh nghiệm hoặc chứng chỉ liên quan"></textarea>
                    </div>
                    <button class="btn btn-success w-100" type="submit">
                        <i class="bi bi-send me-1"></i>Gửi yêu cầu
                    </button>
                </form>
            <?php endif; ?>
        </section>

        <section class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h2 class="h5 fw-bold mb-3">Yêu cầu của tôi</h2>
            <?php if (empty($skillRequests)): ?>
                <p class="text-muted small mb-0">Bạn chưa gửi yêu cầu kỹ năng nào.</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($skillRequests as $request): ?>
                        <?php [$statusLabel, $statusColor] = $statusLabels[$request['status']] ?? ['Không xác định', 'secondary']; ?>
                        <article class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <strong><?= htmlspecialchars($request['skill_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span class="badge text-bg-<?= $statusColor ?>"><?= $statusLabel ?></span>
                            </div>
                            <p class="small text-muted mb-1 mt-2"><?= nl2br(htmlspecialchars($request['proof'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <?php if ($request['status'] === 'rejected' && !empty($request['admin_note'])): ?>
                                <p class="small text-danger mb-0"><strong>Lý do:</strong> <?= nl2br(htmlspecialchars($request['admin_note'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <?php endif; ?>
                            <small class="text-muted"><?= htmlspecialchars($request['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
