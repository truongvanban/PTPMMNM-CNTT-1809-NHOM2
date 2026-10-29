<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="mb-4">
        <h1 class="h4 fw-bold mb-1"><i class="bi bi-arrow-left-right text-warning me-2"></i>Duyệt Yêu Cầu Báo Vắng & Tìm Người Thay</h1>
        <p class="text-muted small mb-0">Hệ thống tự động lọc danh sách tình nguyện viên rảnh, đủ kỹ năng và không bị trùng lịch để đề xuất thay thế</p>
    </div>

    <?php if (empty($requests)): ?>
        <div class="p-5 text-center text-muted">
            <i class="bi bi-check-circle fs-1 text-success"></i>
            <h2 class="h5 mt-2">Hiện tại không có yêu cầu báo vắng nào cần duyệt</h2>
            <p class="small">Tất cả các ca trực đang được đảm bảo đầy đủ nhân sự.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($requests as $req): ?>
                <div class="col-12">
                    <div class="card border rounded-4 p-4 bg-light">
                        <div class="row g-3">
                            <!-- Cột thông tin người báo vắng -->
                            <div class="col-lg-5 border-end">
                                <span class="badge bg-warning text-dark mb-2">Chờ xử lý</span>
                                <h2 class="h5 fw-bold text-dark mb-1"><?= htmlspecialchars($req['requester_name']) ?></h2>
                                <div class="small text-muted mb-2">
                                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($req['requester_email']) ?> | 
                                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($req['requester_phone'] ?? '-') ?>
                                </div>

                                <div class="p-3 bg-white rounded border mb-3">
                                    <div class="small fw-semibold text-secondary">Ca trực xin vắng:</div>
                                    <div class="fw-bold text-success"><?= htmlspecialchars($req['shift_name']) ?></div>
                                    <div class="small text-muted">
                                        <?= date('d/m/Y', strtotime($req['shift_date'])) ?> (<?= substr($req['time_start'], 0, 5) ?> - <?= substr($req['time_end'], 0, 5) ?>)
                                    </div>
                                    <div class="small text-muted mt-1">Hoạt động: <?= htmlspecialchars($req['activity_title']) ?></div>
                                </div>

                                <div>
                                    <span class="small fw-bold text-danger"><i class="bi bi-chat-quote-fill me-1"></i>Lý do báo vắng:</span>
                                    <p class="small text-dark mb-0 fst-italic">"<?= htmlspecialchars($req['reason']) ?>"</p>
                                </div>
                            </div>

                            <!-- Cột chọn người thay & thao tác duyệt -->
                            <div class="col-lg-7">
                                <h2 class="h6 fw-bold text-uppercase text-secondary mb-2">
                                    <i class="bi bi-person-check-fill text-success me-1"></i> Ứng viên thay thế phù hợp (Đủ kỹ năng & rảnh)
                                </h2>

                                <form action="<?= $baseUrl ?>/coordinator/replacement/approve" method="POST" class="mb-3">
                                    <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">

                                    <div class="mb-3">
                                        <?php if (empty($req['candidates'])): ?>
                                            <div class="alert alert-warning py-2 small mb-2">
                                                <i class="bi bi-exclamation-triangle me-1"></i> Không tìm thấy ứng viên nào rảnh và đủ kỹ năng bắt buộc. Bạn có thể duyệt vắng không cần người thay.
                                            </div>
                                            <input type="hidden" name="replacement_user_id" value="0">
                                        <?php else: ?>
                                            <select class="form-select" name="replacement_user_id" required>
                                                <option value="">-- Chọn tình nguyện viên thay thế --</option>
                                                <?php foreach ($req['candidates'] as $cand): ?>
                                                    <option value="<?= $cand['id'] ?>">
                                                        <?= htmlspecialchars($cand['full_name']) ?> (<?= htmlspecialchars($cand['student_id'] ?? '') ?>)
                                                        <?= $cand['is_available'] ? ' - [ĐÃ KHAI BÁO RẢNH]' : '' ?>
                                                        [Đang có: <?= (int)$cand['current_assignments_count'] ?> ca]
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <input type="text" class="form-control form-control-sm" name="coordinator_note" placeholder="Ghi chú của điều phối viên (tùy chọn)">
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold">
                                            <i class="bi bi-check2-circle me-1"></i> Duyệt & Phân công người thay
                                        </button>
                                </form>

                                        <form action="<?= $baseUrl ?>/coordinator/replacement/reject" method="POST" class="d-inline">
                                            <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                            <input type="hidden" name="coordinator_note" value="Yêu cầu bị từ chối do không bố trí được người thay.">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Bạn có chắc chắn muốn từ chối yêu cầu này?">
                                                <i class="bi bi-x-circle me-1"></i> Từ chối
                                            </button>
                                        </form>
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
    $pagination = [
        'url' => $baseUrl . '/coordinator/replacement',
        'params' => [],
        'current_page' => $page,
        'last_page' => $lastPage,
        'total' => $total,
        'per_page' => $perPage,
        'visible_count' => count($requests),
        'item_label' => 'yêu cầu',
    ];
    require VIEW_PATH . '/components/pagination.php';
    ?>
</div>
