# Hướng Dẫn Đóng Góp (Contributing Guide) — VolunteerHub

Cảm ơn bạn đã quan tâm đến việc đóng góp cho dự án mã nguồn mở **VolunteerHub**! Tài liệu này cung cấp các nguyên tắc và quy trình giúp bạn tham gia phát triển một cách thuận tiện nhất.

---

## 1. Quy Trình Làm Việc Với Git (Git Workflow)

1. **Fork** repository này về tài khoản GitHub của bạn.
2. **Clone** repository về máy tính:
   ```bash
   git clone https://github.com/your-username/VolunteerHub.git
   cd VolunteerHub
   ```
3. **Tạo nhánh tính năng** (feature branch) từ nhánh `main`:
   ```bash
   git checkout -b feature/ten-tinh-nang-moi
   # hoặc: git checkout -b fix/ten-loi-can-sua
   ```
4. **Viết mã và tuân thủ coding standards**:
   - Tuân thủ PHP 8.x, PSR-4 và PSR-12.
   - Luôn sử dụng PDO Prepared Statements để phòng chống SQL Injection.
   - Mọi biểu mẫu POST đều phải có CSRF Token (`_csrf_token`).
   - Mọi dữ liệu hiển thị ra HTML đều phải được escape bằng `htmlspecialchars()`.
5. **Chạy kiểm thử trước khi commit**:
   ```bash
   php tests/run_tests.php
   ```
6. **Commit và Push**:
   ```bash
   git commit -m "feat: cải thiện gợi ý phân công tình nguyện viên"
   git push origin feature/ten-tinh-nang-moi
   ```
7. **Tạo Pull Request (PR)** trên GitHub kèm mô tả chi tiết các thay đổi.

---

## 2. Tiêu Chuẩn Viết Mã (Code Standards)

- **Backend**: PHP 8.x thuần, mô hình MVC rõ ràng:
  - `app/Controllers/`: Tiếp nhận request, gọi Service/Model và render View.
  - `app/Models/`: Thao tác dữ liệu với PDO.
  - `app/Services/`: Xử lý logic nghiệp vụ và thuật toán phân công.
  - `app/Views/`: Giao diện Blade-like thuần PHP.
- **Frontend**: HTML5, Bootstrap 5, Chart.js, Vanilla JavaScript, CSS tùy biến theo chủ đề xanh lá / xanh ngọc.
- **Bảo mật**: Không bao giờ hardcode mật khẩu hay dữ liệu giả trong view.

---

## 3. Báo Lỗi & Đề Xuất Tính Năng (Issues)

Nếu bạn phát hiện lỗi hoặc có ý tưởng cải tiến thuật toán phân công, hãy mở một Issue trên GitHub với tiêu đề rõ ràng và các bước tái hiện (reproduce steps).
