<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="row g-4">
    <!-- Form Chạy Thuật Toán -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-cpu text-primary me-2"></i>Chạy Thuật Toán Phân Công</h1>
            <p class="text-muted small mb-4">
                Hệ thống đề xuất phân công đơn đã duyệt, đồng thời kiểm tra <strong>kỹ năng bắt buộc</strong>, <strong>trùng giờ</strong> và <strong>giới hạn ca</strong>. Tình nguyện viên vẫn cần xác nhận sau khi được phân công.
            </p>

            <div class="alert alert-info d-flex align-items-start gap-2 small" role="note">
                <i class="bi bi-info-circle-fill mt-1"></i>
                <div>
                    Khi duyệt đơn đăng ký một ca cụ thể, hệ thống tự phân công nếu ca còn chỗ và tình nguyện viên đáp ứng điều kiện. Dùng thuật toán để đề xuất phân công hàng loạt hoặc sắp xếp các trường hợp còn thiếu; tình nguyện viên vẫn phải xác nhận sau khi được phân công.
                    <a href="<?= $baseUrl ?>/coordinator/registrations?status=pending" class="alert-link d-block mt-1">Xem các đơn đang chờ duyệt</a>
                </div>
            </div>

            <form action="<?= $baseUrl ?>/coordinator/assignment/run" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">

                <div class="mb-3">
                    <label for="activity_id" class="form-label fw-semibold">Chọn hoạt động cần phân công <span class="text-danger">*</span></label>
                    <select class="form-select" id="activity_id" name="activity_id" required>
                        <option value="">-- Chọn hoạt động --</option>
                        <?php foreach ($activities as $act): ?>
                            <option value="<?= $act['id'] ?>" <?= $selectedActId === (int)$act['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($act['title']) ?> (<?= (int)$act['shift_count'] ?> ca)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Lựa chọn thuật toán <span class="text-danger">*</span></label>
                    
                    <div class="card p-3 mb-2 border-primary border-opacity-50 bg-primary-soft">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="algorithm" id="algo_improved" value="improved_greedy" checked>
                            <label class="form-check-label fw-bold text-dark" for="algo_improved">
                                Thuật toán cải tiến (Improved Greedy - MCF & Workload Balancing)
                                <span class="badge bg-success ms-1">Khuyên dùng</span>
                            </label>
                            <p class="text-muted small mb-0 mt-1">
                                Sắp xếp ca khó trước (ít ứng viên, nhiều kỹ năng bắt buộc). Ưu tiên người có ít ca hơn để cân bằng khối lượng công việc.
                            </p>
                        </div>
                    </div>

                    <div class="card p-3 border">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="algorithm" id="algo_baseline" value="baseline_greedy">
                            <label class="form-check-label fw-bold text-dark" for="algo_baseline">
                                Thuật toán cơ sở (Baseline Greedy)
                            </label>
                            <p class="text-muted small mb-0 mt-1">
                                Duyệt tuần tự theo thứ tự thời gian ca và danh sách đăng ký ban đầu. Dùng để đối chứng thực nghiệm.
                            </p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-play-circle me-1"></i> Bắt đầu chạy & Xem trước kết quả
                </button>
            </form>
        </div>
    </div>

    <!-- Thông Tin Ràng Buộc & Phiên Chạy Cũ -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h2 class="h5 fw-bold mb-3"><i class="bi bi-shield-check text-success me-2"></i>Ràng Buộc Cứng Được Kiểm Tra</h2>
            <ul class="list-unstyled small mb-0">
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Kỹ năng:</strong> Không phân công người thiếu kỹ năng bắt buộc của ca.</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Đăng ký:</strong> Chỉ phân công người có đơn đã được duyệt; đơn duyệt xác nhận họ có thể tham gia ca.</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Xung đột lịch:</strong> Tuyệt đối không phân công một người vào 2 ca bị chồng lấn giờ.</li>
                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Giới hạn:</strong> Không vượt quá số người tối đa cho phép của ca.</li>
                <li class="mb-0"><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Tính trung thực:</strong> Không tự tạo tình nguyện viên giả để lấp ca thiếu.</li>
            </ul>
        </div>

        <?php if (!empty($recentRuns)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h2 class="h6 fw-bold text-uppercase mb-3">Các phiên chạy gần nhất</h2>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentRuns as $run): ?>
                        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge <?= $run['algorithm'] === 'improved_greedy' ? 'bg-success' : 'bg-secondary' ?> me-1">
                                    <?= $run['algorithm'] ?>
                                </span>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($run['created_at'])) ?></small>
                                <div class="small fw-semibold mt-1">Trạng thái: <?= $run['status'] ?></div>
                            </div>
                            <a href="<?= $baseUrl ?>/coordinator/assignment/preview/<?= $run['id'] ?>" class="btn btn-sm btn-outline-primary">
                                Xem lại
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
