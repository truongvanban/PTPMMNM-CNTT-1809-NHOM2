<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 fw-bold mb-1">Ca Trực Đang Mở Đăng Ký</h1>
            <p class="text-muted small mb-0">Lựa chọn các ca phù hợp với thời gian và kỹ năng của bạn để đăng ký tham gia</p>
        </div>
        <a href="<?= $baseUrl ?>/volunteer/my-registrations" class="btn btn-outline-success btn-sm">
            <i class="bi bi-card-checklist me-1"></i> Xem ca đã đăng ký
        </a>
    </div>

    <?php if (empty($shifts)): ?>
        <div class="p-5 text-center text-muted">
            <i class="bi bi-calendar-x fs-1"></i>
            <h2 class="h5 mt-2">Hiện tại không có ca trực nào đang mở đăng ký</h2>
            <p class="small">Hãy quay lại sau hoặc theo dõi các thông báo mới từ Điều phối viên.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-custom mb-0">
                <thead>
                    <tr>
                        <th>Ca trực & Hoạt động</th>
                        <th>Địa điểm & Ngày</th>
                        <th>Thời gian ca</th>
                        <th>Yêu cầu kỹ năng</th>
                        <th>Đăng ký & phân công</th>
                        <th class="text-center">Chi tiết & đăng ký</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shifts as $s): ?>
                        <?php
                            $isRegistered = !empty($s['my_reg_id']) && in_array($s['my_reg_status'], ['approved', 'pending'], true);
                            $registeredCount = (int)$s['registered_count'];
                            $assignedCount = (int)$s['assigned_count'];
                            $maxVolunteers = (int)$s['max_volunteers'];
                            $isFull = $registeredCount >= $maxVolunteers;
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($s['name']) ?></div>
                                <small class="text-success fw-semibold"><i class="bi bi-flag me-1"></i><?= htmlspecialchars($s['activity_title']) ?></small>
                            </td>
                            <td>
                                <div><i class="bi bi-calendar3 me-1 text-primary"></i><?= date('d/m/Y', strtotime($s['date'])) ?></div>
                                <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($s['activity_location'] ?? 'Chưa rõ') ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= substr($s['time_start'], 0, 5) ?> - <?= substr($s['time_end'], 0, 5) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($s['required_skills'])): ?>
                                    <span class="badge bg-warning-soft text-dark"><i class="bi bi-star-fill text-warning me-1"></i><?= htmlspecialchars($s['required_skills']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">Mọi tình nguyện viên</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold <?= $isFull ? 'text-danger' : 'text-dark' ?>">
                                    Đã đăng ký: <?= $registeredCount ?> / <?= $maxVolunteers ?>
                                </div>
                                <small class="text-muted d-block">
                                    Đã phân công: <?= $assignedCount ?> / <?= $maxVolunteers ?>
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm px-3"
                                        data-bs-toggle="modal" data-bs-target="#shift-detail-<?= (int)$s['id'] ?>">
                                    <i class="bi bi-info-circle me-1"></i> Chi tiết
                                </button>
                                <?php if ($isRegistered): ?>
                                    <span class="badge bg-success-soft text-success"><i class="bi bi-check2 me-1"></i> Đã đăng ký</span>
                                <?php elseif ($isFull): ?>
                                    <button type="button" class="btn btn-secondary btn-sm px-3" disabled>
                                        <i class="bi bi-people-fill me-1"></i> Đã đủ đăng ký
                                    </button>
                                <?php else: ?>
                                    <form action="<?= $baseUrl ?>/volunteer/register" method="POST" class="d-inline">
                                        <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="shift_id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm px-3">
                                            <i class="bi bi-plus-lg me-1"></i> Đăng ký
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
        <?php foreach ($shifts as $s): ?>
            <?php
            $shiftId = (int)$s['id'];
            $isRegistered = !empty($s['my_reg_id']) && in_array($s['my_reg_status'], ['approved', 'pending'], true);
            $registeredCount = (int)$s['registered_count'];
            $maxVolunteers = (int)$s['max_volunteers'];
            $isFull = $registeredCount >= $maxVolunteers;
            ?>
            <div class="modal fade" id="shift-detail-<?= $shiftId ?>" tabindex="-1"
                 aria-labelledby="shift-detail-label-<?= $shiftId ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header">
                            <div>
                                <span class="small text-success fw-semibold"><?= htmlspecialchars($s['activity_title'], ENT_QUOTES, 'UTF-8') ?></span>
                                <h2 class="modal-title fs-5 fw-bold" id="shift-detail-label-<?= $shiftId ?>">
                                    <?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?>
                                </h2>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <?php if (!empty($s['description'])): ?>
                                <p class="mb-3"><?= nl2br(htmlspecialchars($s['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($s['activity_description'])): ?>
                                <div class="mb-3">
                                    <h3 class="h6 fw-bold">Giới thiệu hoạt động</h3>
                                    <p class="text-secondary mb-0"><?= nl2br(htmlspecialchars($s['activity_description'], ENT_QUOTES, 'UTF-8')) ?></p>
                                </div>
                            <?php endif; ?>
                            <dl class="row small mb-0">
                                <dt class="col-5"><i class="bi bi-calendar3 me-1 text-primary"></i>Ngày</dt>
                                <dd class="col-7"><?= date('d/m/Y', strtotime($s['date'])) ?></dd>
                                <dt class="col-5"><i class="bi bi-clock me-1 text-primary"></i>Thời gian</dt>
                                <dd class="col-7"><?= substr($s['time_start'], 0, 5) ?> - <?= substr($s['time_end'], 0, 5) ?></dd>
                                <dt class="col-5"><i class="bi bi-geo-alt me-1 text-primary"></i>Địa điểm</dt>
                                <dd class="col-7"><?= htmlspecialchars($s['location'] ?: ($s['activity_location'] ?? 'Chưa rõ'), ENT_QUOTES, 'UTF-8') ?></dd>
                                <dt class="col-5"><i class="bi bi-people me-1 text-primary"></i>Số lượng</dt>
                                <dd class="col-7"><?= (int)$s['min_volunteers'] ?>–<?= $maxVolunteers ?> người</dd>
                                <dt class="col-5"><i class="bi bi-check2-circle me-1 text-primary"></i>Đã đăng ký</dt>
                                <dd class="col-7"><?= $registeredCount ?> / <?= $maxVolunteers ?> người</dd>
                                <dt class="col-5"><i class="bi bi-star me-1 text-primary"></i>Kỹ năng</dt>
                                <dd class="col-7"><?= htmlspecialchars($s['required_skills'] ?: 'Không yêu cầu kỹ năng cụ thể', ENT_QUOTES, 'UTF-8') ?></dd>
                                <?php if (!empty($s['registration_deadline'])): ?>
                                    <dt class="col-5"><i class="bi bi-hourglass-split me-1 text-primary"></i>Hạn đăng ký</dt>
                                    <dd class="col-7"><?= date('H:i d/m/Y', strtotime($s['registration_deadline'])) ?></dd>
                                <?php endif; ?>
                            </dl>
                        </div>
                        <div class="modal-footer">
                            <?php if ($isRegistered): ?>
                                <span class="badge bg-success-soft text-success"><i class="bi bi-check2 me-1"></i>Đã đăng ký</span>
                            <?php elseif ($isFull): ?>
                                <button type="button" class="btn btn-secondary" disabled>Đã đủ đăng ký</button>
                            <?php else: ?>
                                <form action="<?= $baseUrl ?>/volunteer/register" method="POST" class="d-inline">
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="shift_id" value="<?= $shiftId ?>">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-plus-lg me-1"></i> Đăng ký ca này
                                    </button>
                                </form>
                            <?php endif; ?>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
