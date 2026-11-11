<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<div class="card border-0 shadow-sm rounded-4 p-4" style="max-width: 800px;">
    <div class="mb-4">
        <h1 class="h4 fw-bold mb-1"><i class="bi bi-database-gear text-danger me-2"></i>Quản Lý Dữ Liệu Mẫu (Demo Data)</h1>
        <p class="text-muted small mb-0">Thiết lập lại cơ sở dữ liệu về trạng thái ban đầu để kiểm thử và bảo vệ đồ án</p>
    </div>

    <div class="alert alert-info rounded-3 p-3 mb-4">
        <h2 class="h6 fw-bold mb-2"><i class="bi bi-info-circle-fill me-1"></i> Thông Tin 3 Bộ Dữ Liệu Mẫu Thử Nghiệm:</h2>
        <ul class="mb-0 small ps-3">
            <li class="mb-1"><strong>Bộ A (Đủ người - Hiến máu tình nguyện):</strong> 4 ca trực, 20+ tình nguyện viên đăng ký và đáp ứng yêu cầu kỹ năng. Thuật toán phân công đủ 100%.</li>
            <li class="mb-1"><strong>Bộ B (Thiếu người - Khám bệnh vùng cao):</strong> 3 ca trực, yêu cầu kỹ năng bắt buộc "Y tế sơ cứu" & "Tiếng Anh". Thuật toán xác định chính xác nguyên nhân thiếu người do thiếu ứng viên đạt kỹ năng.</li>
            <li class="mb-0"><strong>Bộ C (Trùng lịch - Hội chợ từ thiện):</strong> 4 ca trực, các ca sáng diễn ra cùng khung giờ. Thuật toán kiểm soát ràng buộc cứng không để bất kỳ TNV nào bị xếp trùng 2 ca cùng lúc.</li>
        </ul>
    </div>

    <div class="card p-4 border-danger border-opacity-50 rounded-4 bg-danger bg-opacity-10 mb-4">
        <h2 class="h5 fw-bold text-danger mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Tái Tạo Toàn Bộ Dữ Liệu</h2>
        <p class="small text-muted mb-3">
            Thao tác này sẽ xóa sạch dữ liệu hiện tại và chèn lại 30 tình nguyện viên, 5 kỹ năng, 4 hoạt động và 12 ca trực chuẩn.
        </p>

        <form action="<?= $baseUrl ?>/admin/seed/run" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
            <button type="submit" class="btn btn-danger px-4 fw-bold" data-confirm="CẢNH BÁO: Dữ liệu hiện tại sẽ bị đặt lại về ban đầu. Bạn có chắc chắn muốn thực hiện?">
                <i class="bi bi-arrow-repeat me-1"></i> Tái Tạo Dữ Liệu Mẫu Ngay
            </button>
        </form>
    </div>
</div>
