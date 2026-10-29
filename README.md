# VOLUNTEERHUB — HỆ THỐNG TỔ CHỨC HOẠT ĐỘNG & PHÂN CÔNG TÌNH NGUYỆN VIÊN (ĐỀ TÀI 6)

> Tên repository trước đó: `REPO-GIT-FIT4016_Backend_18-01_La_Van_Hai`

[![PHP Version](https://img.shields.io/badge/PHP-8.x-777bb4.svg)](https://php.net)
[![Database](https://img.shields.io/badge/MariaDB-10.4-003545.svg)](https://mariadb.org)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952b3.svg)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/Tests-8%20Passed%20(100%25)-success.svg)](tests/run_tests.php)

---

## 1. Giới Thiệu Dự Án

**VolunteerHub** là hệ thống web mã nguồn mở được thiết kế cho các trường đại học, câu lạc bộ hoặc tổ chức thiện nguyện nhằm giải quyết bài toán:
- Quản lý hồ sơ, kỹ năng và lịch rảnh của tình nguyện viên (TNV).
- Quản lý hoạt động và các ca trực với chỉ tiêu số lượng và yêu cầu chuyên môn.
- **Tự động đề xuất phân công ca trực** bằng thuật toán tối ưu **Improved Greedy (Most Constrained First & Workload Balancing)**, đảm bảo 0 vi phạm ràng buộc cứng (kỹ năng bắt buộc, lịch rảnh trọn ca, không chồng lấn thời gian, giới hạn ca).
- Xử lý quy trình báo vắng đột xuất và tự động gợi ý ứng viên thay thế phù hợp.
- Cung cấp màn hình đối chứng thực nghiệm (Benchmark) với **Baseline Greedy** kèm biểu đồ trực quan (Chart.js) và xuất báo cáo CSV UTF-8.

---

## 2. Công Nghệ & Môi Trường Bắt Buộc

- **Ngôn ngữ Backend**: PHP 8.x (PHP 8.2 trên XAMPP), kiến trúc MVC thuần, chuẩn PSR-4.
- **Cơ sở dữ liệu**: MySQL / MariaDB (đi kèm XAMPP), kết nối qua PDO Prepared Statements.
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla ES6), Bootstrap 5.3, Bootstrap Icons, Chart.js.
- **Bảo mật**: `password_hash`/`verify`, CSRF Token cho mọi biểu mẫu POST, chống XSS, kiểm tra quyền sở hữu backend, `session_regenerate_id()`.
- **Múi giờ**: `Asia/Ho_Chi_Minh`.
- **Hỗ trợ xuất file**: CSV UTF-8 kèm BOM hiển thị tiếng Việt chuẩn trên Excel.

---

## 3. Cấu Trúc Thư Mục Dự Án

```
VolunteerHub/
├── app/
│   ├── Controllers/
│   │   ├── Admin/             # Controllers dành riêng cho Quản trị viên
│   │   ├── Coordinator/       # Controllers dành riêng cho Điều phối viên
│   │   ├── Volunteer/         # Controllers dành cho Tình nguyện viên
│   │   ├── AuthController.php # Xử lý đăng nhập, đăng ký, đăng xuất
│   │   ├── ErrorController.php# Trang lỗi 403, 404, 500
│   │   └── PublicController.php
│   ├── Core/
│   │   ├── Autoloader.php     # Bộ nạp lớp tự động PSR-4
│   │   ├── Controller.php     # Base Controller (render, redirect, CSRF)
│   │   ├── Router.php         # Bộ điều hướng URL & Middleware phân quyền
│   │   └── Session.php        # Quản lý phiên, Flash message & CSRF Token
│   ├── Models/                # Tầng truy xuất dữ liệu PDO (BaseModel, UserModel, ...)
│   ├── Services/
│   │   ├── AssignmentService.php # Dịch vụ phân công & Benchmark
│   │   ├── BaselineGreedy.php    # Thuật toán cơ sở đối chứng
│   │   └── ImprovedGreedy.php    # Thuật toán cải tiến (MCF + Cân bằng tải)
│   └── Views/                 # Giao diện Bootstrap 5 phân theo vai trò
├── config/
│   ├── app.php                # Cấu hình nạp .env, múi giờ, hằng số
│   └── database.php           # Cấu hình kết nối PDO MariaDB
├── database/
│   ├── schema.sql             # 19 bảng quan hệ toàn vẹn dữ liệu
│   └── seeders/seed.php       # Seeder tạo 30 TNV, 12 ca trực, 3 bộ dữ liệu mẫu
├── public/
│   ├── assets/                # CSS, JS tùy biến, biểu đồ
│   ├── .htaccess              # URL Rewriting cho Apache
│   └── index.php              # Front Controller duy nhất
├── tests/
│   ├── ConstraintValidator.php# Bộ kiểm tra 4 ràng buộc cứng độc lập
│   └── run_tests.php          # CLI Test Runner cho TC01 -> TC05
├── .env.example               # Mẫu cấu hình; không chứa thông tin xác thực
├── composer.json
├── CONTRIBUTING.md
├── LICENSE
└── README.md
```

---

## 4. Hướng Dẫn Cài Đặt & Chạy Trên XAMPP (Từ A đến Z)

### Bước 1: Đặt mã nguồn vào XAMPP
Sao chép thư mục dự án vào thư mục `htdocs` của XAMPP:
```
E:\xampp\htdocs\VolunteerHub
```

### Bước 2: Khởi động Apache và MySQL trên XAMPP
Mở **XAMPP Control Panel** và nhấn **Start** tại 2 dịch vụ:
- **Apache** (Cổng 80)
- **MySQL** (Cổng 3306)

### Bước 3: Cấu hình file `.env`
Sao chép `.env.example` thành `.env` trong thư mục gốc và điền thông tin riêng của môi trường. Không đưa `.env` vào Git, gói ZIP/chia sẻ, ảnh chụp hoặc log; chỉ chia sẻ `.env.example`. Nếu thông tin xác thực từng được đưa vào tệp chia sẻ hoặc kho mã, hãy thay/thu hồi thông tin đó trước khi triển khai.
Không dùng tài khoản demo hoặc dữ liệu seed trên production; hãy xóa/đổi mật khẩu các tài khoản mẫu trước khi đưa hệ thống lên môi trường thật.
```ini
APP_NAME=VolunteerHub
APP_URL=http://localhost/VolunteerHub/public
APP_ENV=development
APP_DEBUG=true
APP_TIMEZONE=Asia/Ho_Chi_Minh

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=volunteerhub
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
```

### Bước 4: Nhập cơ sở dữ liệu và dữ liệu mẫu
Mở Terminal / PowerShell và chạy 2 lệnh sau:
```powershell
# 1. Nhập Schema cấu trúc 19 bảng:
& "E:\xampp\mysql\bin\mysql.exe" -u root -e "source E:/xampp/htdocs/VolunteerHub/database/schema.sql"

# 2. Chạy Seeder nạp 30 TNV và 3 bộ dữ liệu thử nghiệm:
& "E:\xampp\php\php.exe" "E:\xampp\htdocs\VolunteerHub\database\seeders\seed.php"
```

### Bước 5: Truy cập Website
Mở trình duyệt web (Chrome, Edge, Firefox) và truy cập:
👉 **[http://localhost/VolunteerHub/public](http://localhost/VolunteerHub/public)**

---

## 5. Danh Sách Tài Khoản Thử Nghiệm (Demo Accounts)

Giao diện đăng nhập tại `/login` đã được tích hợp **nút bấm 1 chạm tự điền thông tin đăng nhập nhanh**:

| Vai trò | Email đăng nhập | Mật khẩu | Chức năng kiểm thử |
| :--- | :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin@volunteerhub.vn` | `Admin@2024` | Quản trị tài khoản, danh mục kỹ năng, xem nhật ký Audit Logs, Benchmark thuật toán. |
| **Điều phối viên (Coordinator)** | `coord1@volunteerhub.vn` | `Coord@123` | Quản lý ca trực, chạy thuật toán phân công, xem cảnh báo ca thiếu người, duyệt báo vắng. |
| **Tình nguyện viên (Volunteer)** | `tnv1@volunteerhub.vn`<br>*(đến `tnv30@volunteerhub.vn`)* | `Demo@123` | Khai báo kỹ năng, lịch rảnh, đăng ký ca, xem lịch cá nhân, xác nhận tham gia / báo vắng. |

---

## 6. Mô Tả 3 Bộ Dữ Liệu Thử Nghiệm

Hệ thống đã chuẩn bị sẵn 3 kịch bản dữ liệu mẫu trong CSDL để phục vụ bảo vệ đồ án:

- **Bộ A — Kịch bản Đủ người (Hoạt động 1: Ngày Hội Hiến Máu)**:
  - Gồm 4 ca trực với yêu cầu kỹ năng sơ cứu và hướng dẫn.
  - Có hơn 20 tình nguyện viên rảnh và đáp ứng kỹ năng đăng ký.
  - **Kết quả thuật toán**: Đạt tỷ lệ đủ người **100%**, 0 vi phạm.

- **Bộ B — Kịch bản Thiếu người & Kỹ năng (Hoạt động 2: Hỗ Trợ Y Tế Vùng Cao)**:
  - Gồm 3 ca trực, trong đó ca số 6 yêu cầu cả 2 kỹ năng bắt buộc: "Y tế sơ cứu" và "Tiếng Anh giao tiếp".
  - Số lượng TNV có cả 2 kỹ năng này rất ít.
  - **Kết quả thuật toán**: Nhận diện chính xác ca thiếu người, hiển thị rõ nguyên nhân thiếu nhân sự (thiếu kỹ năng bắt buộc), không bịa đặt TNV giả.

- **Bộ C — Kịch bản Trùng lịch (Hoạt động 3: Hội Chợ Giao Lưu Văn Hóa)**:
  - Gồm 4 ca trực, trong đó Ca 8 và Ca 9 diễn ra cùng khung giờ sáng.
  - Có nhóm TNV đăng ký cả 2 ca.
  - **Kết quả thuật toán**: Kiểm soát ràng buộc cứng Overlap triệt để, đảm bảo 0 TNV nào bị phân công vào 2 ca cùng lúc.

---

## 7. Hai Kịch Bản Demo Bảo Vệ Đồ Án (Step-by-Step Script)

### 🎬 Kịch bản 1: Phân công tự động và xử lý ca thiếu người
1. Đăng nhập với tài khoản **Điều phối viên** (`coord1@volunteerhub.vn` / `Coord@123`).
2. Vào mục **"Phân công tự động (MCF)"** (`/coordinator/assignment/run`).
3. Chọn hoạt động **"Hỗ Trợ Y Tế Cộng Đồng Tháng 3"** (Bộ B), chọn thuật toán **Improved Greedy** và bấm **Bắt đầu chạy**.
4. Màn hình **Xem trước phương án** hiển thị:
   - Các ca đủ người hiển thị badge xanh.
   - Ca thiếu người hiển thị badge đỏ kèm nguyên nhân cụ thể: *"Thiếu ứng viên thỏa mãn cả kỹ năng bắt buộc Y tế và Tiếng Anh"*.
   - Số vi phạm ràng buộc: **0**.
5. Bấm **"Xác Nhận & Lưu Phương Án"** -> Hệ thống chuyển trạng thái phân công chính thức và gửi thông báo tới các TNV được chọn.

### 🎬 Kịch bản 2: Báo vắng đột xuất và đề xuất người thay thế
1. Đăng nhập tài khoản **Tình nguyện viên** (`tnv1@volunteerhub.vn` / `Demo@123`).
2. Vào **Lịch phân công cá nhân** (`/volunteer/schedule`), thấy ca trực được gán.
3. Chọn **"Báo bận đột xuất"** -> Điền lý do: *"Trùng lịch thi học kỳ"* -> Bấm gửi.
4. Đăng nhập lại tài khoản **Điều phối viên** (`coord1@volunteerhub.vn`).
5. Vào mục **"Duyệt báo vắng & Thay người"** (`/coordinator/replacement`):
   - Thấy yêu cầu báo vắng của `tnv1`.
   - Hệ thống tự động lọc và gợi ý danh sách các TNV **đang rảnh trọn ca này, có đủ kỹ năng bắt buộc và không bị trùng lịch**.
   - Điều phối viên chọn 1 ứng viên thay thế và bấm **"Duyệt & Phân công người thay"**.
6. Hệ thống tự động chuyển phân công, cập nhật trạng thái và gửi thông báo tới người thay thế.

---

## 8. Thuật Toán Improved Greedy (MCF) & Đối Chứng Thực Nghiệm

### Nguyên lý hoạt động:
1. **Kiểm tra chuẩn hóa**: Lọc danh sách ứng viên đã đăng ký cho từng ca.
2. **Sắp xếp theo độ khó (Most Constrained First - MCF)**:
   - Ca có ít ứng viên hợp lệ hơn được xếp trước.
   - Tiêu chí phụ 1: Ca yêu cầu nhiều kỹ năng bắt buộc hơn xếp trước.
   - Tiêu chí phụ 2: Ca có thời gian bắt đầu sớm hơn xếp trước.
   - Tiêu chí phụ 3: ID ổn định (đảm bảo tính tất định - deterministic).
3. **Cân bằng tải (Workload Balancing)**:
   - Khi chọn ứng viên cho một ca, ưu tiên người đang có ít ca phân công nhất.
4. **Kiểm tra ràng buộc cứng**: Kỹ năng bắt buộc $\in$ Kỹ năng của TNV; Lịch rảnh bao trùm toàn bộ ca; Không chồng lấn thời gian với bất kỳ ca nào đã phân công trước đó; Không vượt `max_volunteers`.

### Bảng kết quả so sánh (Kỹ thuật 2):
| Chỉ số thực nghiệm | Baseline Greedy (Tuần tự) | Improved Greedy (MCF) | Ý nghĩa thực tế |
| :--- | :---: | :---: | :--- |
| **Tỷ lệ ca đủ người** | Thấp hơn trên dữ liệu gắt | **Cao hơn hoặc bằng** | Tận dụng tối ưu nhân lực chuyên môn hiếm cho ca khó |
| **Số vi phạm ràng buộc** | **0 vi phạm** | **0 vi phạm** | Tuyệt đối an toàn về ràng buộc nghiệp vụ |
| **Độ lệch khối lượng ca** | Lớn (người nhiều người ít) | **Nhỏ (rất đồng đều)** | Công bằng cho các tình nguyện viên |
| **Thời gian thực thi** | $< 5\text{ ms}$ | $< 8\text{ ms}$ | Tốc độ xử lý gần như tức thời |

---

## 9. Chạy Kiểm Thử Tự Động (Test Suite)

Dự án tích hợp sẵn bộ kiểm thử độc lập kiểm tra toàn bộ 5 test cases bắt buộc:
```powershell
php tests/run_tests.php
```

**Kết quả kiểm thử:**
- `TC01`: Ràng buộc kỹ năng bắt buộc (100% Passed)
- `TC02`: Ràng buộc lịch rảnh toàn ca (100% Passed)
- `TC03`: Ràng buộc chống trùng ca / Overlap (100% Passed)
- `TC04`: Giới hạn ca / Capacity limit (100% Passed)
- `TC05`: So sánh hai thuật toán trên cùng dữ liệu (100% Passed)
- `Database Sets`: Bộ A (Đủ người), Bộ B (Thiếu người), Bộ C (Trùng lịch) đều Passed.

---

## 10. Checklist Đối Chiếu Rubric Đồ Án

- [x] **Môi trường**: PHP 8.x, MariaDB, XAMPP, Bootstrap 5, Git/GitHub.
- [x] **Nhóm 1**: CRUD tài khoản, kỹ năng, hồ sơ, lịch rảnh, hoạt động và ca trực.
- [x] **Nhóm 2**: Đăng ký ca, rút đăng ký, kiểm tra điều kiện, xem lịch cá nhân.
- [x] **Nhóm 3**: Thuật toán phân công tự động (Baseline Greedy & Improved Greedy MCF).
- [x] **Nhóm 4**: Chỉnh sửa phân công thủ công, kiểm tra ràng buộc ở backend, concurrency control.
- [x] **Nhóm 5**: Duyệt yêu cầu báo vắng, đề xuất người thay phù hợp, xuất CSV UTF-8.
- [x] **Kỹ thuật 1**: Improved Greedy với cơ chế Most Constrained First và cân bằng tải.
- [x] **Kỹ thuật 2**: So sánh thực nghiệm 2 thuật toán trên cùng tập dữ liệu với 5 chỉ số đo lường và biểu đồ Chart.js.
- [x] **Kiểm thử**: Đầy đủ TC01 -> TC05 có script chạy CLI độc lập.
- [x] **Mã nguồn mở**: LICENSE MIT, CONTRIBUTING.md, composer.json, tài liệu README chi tiết.
