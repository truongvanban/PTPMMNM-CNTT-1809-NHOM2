<?php
/**
 * Quản lý kỹ năng của tình nguyện viên - VolunteerHub Modern UI
 */
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white" style="max-width: 900px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <span class="vh-section-badge">Hồ Sơ Năng Lực</span>
            <h1 class="h3 fw-bold mb-1 text-dark">
                <i class="bi bi-patch-check-fill text-success me-2"></i>Quản Lý Kỹ Năng Của Bạn
            </h1>
            <p class="text-muted small mb-0">
                Thuật toán <strong>Improved Greedy (MCF)</strong> sẽ sử dụng các kỹ năng này để tự động ghép nối và ưu tiên xếp bạn vào các ca trực phù hợp nhất.
            </p>
        </div>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill font-monospace">
            <?= count($mySkillsMap ?? []) ?> kỹ năng đã chọn
        </span>
    </div>

    <form action="<?= $baseUrl ?>/volunteer/skills" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">

        <div class="table-responsive mb-4">
            <table class="table table-hover align-middle table-custom mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">Chọn</th>
                        <th>Tên kỹ năng</th>
                        <th>Danh mục & Mô tả</th>
                        <th style="width: 200px;">Trình độ chuyên môn</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allSkills as $sk): ?>
                        <?php 
                            $skId = (int)$sk['id'];
                            $isChecked = isset($mySkillsMap[$skId]);
                            $currentLevel = $mySkillsMap[$skId] ?? 'beginner';
                        ?>
                        <tr class="<?= $isChecked ? 'table-success-subtle' : '' ?>">
                            <td class="text-center">
                                <input class="form-check-input" type="checkbox" name="skills[]" value="<?= $skId ?>" id="sk_<?= $skId ?>" <?= $isChecked ? 'checked' : '' ?> style="cursor: pointer; width: 1.15rem; height: 1.15rem;">
                            </td>
                            <td>
                                <label class="form-check-label fw-bold text-dark d-flex align-items-center gap-2" for="sk_<?= $skId ?>" style="cursor: pointer;">
                                    <i class="bi bi-award text-warning"></i>
                                    <span><?= htmlspecialchars($sk['name']) ?></span>
                                </label>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small"><?= htmlspecialchars($sk['category'] ?? 'Chung') ?></span>
                                <div class="small text-muted mt-1"><?= htmlspecialchars($sk['description'] ?? '') ?></div>
                            </td>
                            <td>
                                <select class="form-select form-select-sm rounded-3" name="levels[<?= $skId ?>]">
                                    <option value="beginner" <?= $currentLevel === 'beginner' ? 'selected' : '' ?>>⭐ Cơ bản (Beginner)</option>
                                    <option value="intermediate" <?= $currentLevel === 'intermediate' ? 'selected' : '' ?>>⭐⭐ Trung bình (Intermediate)</option>
                                    <option value="advanced" <?= $currentLevel === 'advanced' ? 'selected' : '' ?>>⭐⭐⭐ Thành thạo (Advanced)</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
            <span class="text-muted small">
                <i class="bi bi-shield-check text-success me-1"></i> Thông tin được lưu an toàn vào hồ sơ cá nhân
            </span>
            <button type="submit" class="btn btn-success px-4 py-2 rounded-pill fw-semibold text-white shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                <i class="bi bi-check2-all me-1"></i> Lưu danh sách kỹ năng
            </button>
        </div>
    </form>
</div>
