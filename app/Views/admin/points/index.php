<?php $baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/'); ?>
<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">Cấu hình điểm & Thi đua</h1>
    <p class="text-muted mb-0">Thiết lập cách tính điểm, giới hạn hằng ngày và các kỳ học.</p>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <h2 class="h5 fw-bold mb-3">Quy tắc tính điểm</h2>
    <form method="POST" action="<?= $baseUrl ?>/admin/points/settings">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
        <div class="row g-3">
            <?php
            $fields = [
                'points_per_hour' => ['Điểm mỗi giờ thực tế', '0.1'],
                'difficult_shift_multiplier' => ['Hệ số ca khó (trước 07:00 / từ 22:00)', '0.1'],
                'skilled_shift_multiplier' => ['Hệ số ca có kỹ năng bắt buộc', '0.1'],
                'on_time_bonus' => ['Thưởng hoàn thành đúng giờ', '1'],
                'late_cancel_penalty' => ['Phạt báo vắng dưới 12 giờ', '1'],
                'no_show_penalty' => ['Phạt vắng mặt không phép', '1'],
                'daily_points_cap' => ['Giới hạn điểm dương mỗi ngày', '1'],
                'certificate_threshold' => ['Mốc điểm cấp chứng nhận', '1'],
            ];
            foreach ($fields as $key => [$label, $step]):
            ?>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="<?= $key ?>"><?= htmlspecialchars($label) ?></label>
                    <input class="form-control" type="number" step="<?= $step ?>" min="0" id="<?= $key ?>" name="<?= $key ?>" value="<?= htmlspecialchars((string)$settings[$key]) ?>" required>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-success mt-4" type="submit"><i class="bi bi-save me-1"></i>Lưu quy tắc</button>
    </form>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <h2 class="h5 fw-bold mb-3">Hệ số theo hoạt động</h2>
    <?php if (empty($activities)): ?>
        <p class="text-muted mb-0">Chưa có hoạt động.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Hoạt động</th><th>Ngày bắt đầu</th><th>Hệ số</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td><?= htmlspecialchars($activity['title']) ?></td>
                        <td><?= date('d/m/Y', strtotime($activity['date_start'])) ?></td>
                        <td><input form="activity-multiplier-<?= (int)$activity['id'] ?>" class="form-control form-control-sm" style="max-width: 120px" type="number" name="points_multiplier" min="0.1" max="10" step="0.1" value="<?= htmlspecialchars((string)$activity['points_multiplier']) ?>" required></td>
                        <td class="text-end">
                            <form id="activity-multiplier-<?= (int)$activity['id'] ?>" method="POST" action="<?= $baseUrl ?>/admin/points/activity-multiplier">
                                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="activity_id" value="<?= (int)$activity['id'] ?>">
                                <button class="btn btn-outline-success btn-sm" type="submit">Lưu</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4">
    <h2 class="h5 fw-bold mb-3">Kỳ học cho bảng xếp hạng</h2>
    <form method="POST" action="<?= $baseUrl ?>/admin/points/terms" class="row g-3 align-items-end mb-4">
        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="term_id" value="0">
        <div class="col-md-4"><label class="form-label" for="term_name">Tên kỳ</label><input class="form-control" id="term_name" name="name" maxlength="100" required></div>
        <div class="col-md-3"><label class="form-label" for="term_start">Từ ngày</label><input class="form-control" type="date" id="term_start" name="starts_on" required></div>
        <div class="col-md-3"><label class="form-label" for="term_end">Đến ngày</label><input class="form-control" type="date" id="term_end" name="ends_on" required></div>
        <div class="col-md-2"><button class="btn btn-outline-success w-100" type="submit">Thêm kỳ</button></div>
    </form>
    <?php if (empty($terms)): ?>
        <p class="text-muted mb-0">Chưa khai báo kỳ học.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Tên kỳ</th><th>Từ ngày</th><th>Đến ngày</th><th></th><th></th></tr></thead>
                <tbody>
                <?php foreach ($terms as $term): ?>
                    <tr>
                        <td><input form="term-<?= (int)$term['id'] ?>" class="form-control form-control-sm" name="name" maxlength="100" value="<?= htmlspecialchars($term['name']) ?>" required></td>
                        <td><input form="term-<?= (int)$term['id'] ?>" class="form-control form-control-sm" type="date" name="starts_on" value="<?= htmlspecialchars($term['starts_on']) ?>" required></td>
                        <td><input form="term-<?= (int)$term['id'] ?>" class="form-control form-control-sm" type="date" name="ends_on" value="<?= htmlspecialchars($term['ends_on']) ?>" required></td>
                        <td>
                            <form id="term-<?= (int)$term['id'] ?>" method="POST" action="<?= $baseUrl ?>/admin/points/terms">
                                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="term_id" value="<?= (int)$term['id'] ?>">
                                <button class="btn btn-outline-success btn-sm" type="submit">Lưu</button>
                            </form>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="<?= $baseUrl ?>/admin/points/terms/delete" class="d-inline" data-confirm="Xóa kỳ học này?">
                                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="term_id" value="<?= (int)$term['id'] ?>">
                                <button class="btn btn-outline-danger btn-sm" type="submit" aria-label="Xóa kỳ học"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
