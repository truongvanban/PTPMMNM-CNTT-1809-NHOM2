<?php
/**
 * VolunteerHub - Seed Dữ liệu Mẫu
 * Chạy lệnh: php database/seeders/seed.php
 * Tạo: 30 tình nguyện viên, 5 kỹ năng, nhiều hoạt động, 12 ca, 
 *       lịch rảnh, đăng ký cho 3 bộ dữ liệu kiểm thử A/B/C
 */

declare(strict_types=1);

// Tải cấu hình
$envFile = __DIR__ . '/../../.env';
if (!file_exists($envFile)) {
    die("Lỗi: Không tìm thấy file .env. Hãy sao chép .env.example thành .env\n");
}

// Đọc .env
$env = parse_ini_file($envFile);
$host    = $env['DB_HOST'] ?? '127.0.0.1';
$port    = $env['DB_PORT'] ?? 3306;
$dbname  = $env['DB_NAME'] ?? 'volunteerhub';
$user    = $env['DB_USER'] ?? 'root';
$pass    = $env['DB_PASS'] ?? '';
$charset = $env['DB_CHARSET'] ?? 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset",
        $user, $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "✔ Kết nối database thành công\n";
} catch (PDOException $e) {
    die("✗ Lỗi kết nối: " . $e->getMessage() . "\n");
}

// Vô hiệu hoá FK checks tạm thời để xoá dữ liệu cũ
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

// Xoá dữ liệu cũ theo thứ tự
$tables = [
    'assignment_history', 'attendance', 'replacement_requests',
    'assignment_run_items', 'assignments', 'assignment_runs',
    'registrations', 'shift_required_skills', 'shifts',
    'activities', 'availability', 'volunteer_skills',
    'volunteer_profiles', 'user_roles', 'users',
    'skills', 'roles', 'notifications', 'audit_logs'
];
foreach ($tables as $table) {
    $pdo->exec("TRUNCATE TABLE `$table`");
}
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
echo "✔ Đã xoá dữ liệu cũ\n";

// =============================================
// 1. ROLES
// =============================================
$roles = [
    ['name' => 'volunteer',   'display_name' => 'Tình nguyện viên',   'description' => 'Người tham gia hoạt động tình nguyện'],
    ['name' => 'coordinator', 'display_name' => 'Điều phối viên',      'description' => 'Người tổ chức và quản lý hoạt động'],
    ['name' => 'admin',       'display_name' => 'Quản trị viên',       'description' => 'Quản trị toàn bộ hệ thống'],
];
$stmt = $pdo->prepare("INSERT INTO roles (name, display_name, description) VALUES (?, ?, ?)");
foreach ($roles as $r) {
    $stmt->execute([$r['name'], $r['display_name'], $r['description']]);
}
$roleIds = ['volunteer' => 1, 'coordinator' => 2, 'admin' => 3];
echo "✔ Đã tạo vai trò\n";

// =============================================
// 2. SKILLS (5 kỹ năng)
// =============================================
$skills = [
    ['name' => 'Y tế sơ cứu',       'category' => 'Y tế',       'description' => 'Kỹ năng sơ cứu cơ bản, CPR, băng bó'],
    ['name' => 'Tiếng Anh giao tiếp','category' => 'Ngoại ngữ', 'description' => 'Giao tiếp tiếng Anh cơ bản đến nâng cao'],
    ['name' => 'Lái xe máy',         'category' => 'Vận tải',    'description' => 'Có giấy phép lái xe máy hạng A'],
    ['name' => 'Công nghệ thông tin','category' => 'Kỹ thuật',   'description' => 'Tin học văn phòng, lập trình cơ bản'],
    ['name' => 'Dẫn chương trình',   'category' => 'Truyền thông','description' => 'Kỹ năng MC, dẫn chương trình sự kiện'],
];
$stmt = $pdo->prepare("INSERT INTO skills (name, category, description) VALUES (?, ?, ?)");
foreach ($skills as $s) {
    $stmt->execute([$s['name'], $s['category'], $s['description']]);
}
$skillIds = range(1, 5); // IDs 1-5
echo "✔ Đã tạo 5 kỹ năng\n";

// =============================================
// 3. USERS (admin, 2 coordinator, 30 volunteer)
// =============================================
$pass_demo = password_hash('Demo@123', PASSWORD_BCRYPT);
$pass_admin = password_hash('Admin@2024', PASSWORD_BCRYPT);
$pass_coord = password_hash('Coord@123', PASSWORD_BCRYPT);

// Admin
$pdo->prepare("INSERT INTO users (email, password_hash, full_name, phone, status, email_verified) VALUES (?,?,?,?,?,?)")
    ->execute(['admin@volunteerhub.vn', $pass_admin, 'Quản Trị Viên', '0901234567', 'active', 1]);
$adminId = $pdo->lastInsertId();

// Coordinator 1
$pdo->prepare("INSERT INTO users (email, password_hash, full_name, phone, status, email_verified) VALUES (?,?,?,?,?,?)")
    ->execute(['coord1@volunteerhub.vn', $pass_coord, 'Nguyễn Thị Hoa', '0912345678', 'active', 1]);
$coord1Id = $pdo->lastInsertId();

// Coordinator 2
$pdo->prepare("INSERT INTO users (email, password_hash, full_name, phone, status, email_verified) VALUES (?,?,?,?,?,?)")
    ->execute(['coord2@volunteerhub.vn', $pass_coord, 'Trần Văn Nam', '0923456789', 'active', 1]);
$coord2Id = $pdo->lastInsertId();

// Gán role Admin
$pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?,?)")->execute([$adminId, $roleIds['admin']]);
// Gán role Coordinator
$pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?,?)")->execute([$coord1Id, $roleIds['coordinator']]);
$pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?,?)")->execute([$coord2Id, $roleIds['coordinator']]);

// 30 Tình nguyện viên
$volunteers = [
    ['Phạm Văn An',       '0934111001', 'IT', 'CNTT2022', [1,2]],
    ['Lê Thị Bình',       '0934111002', 'Y tế', 'YT2021',  [1,3]],
    ['Hoàng Văn Cường',   '0934111003', 'Kinh tế', 'KT2022',[3,4]],
    ['Trần Thị Dung',     '0934111004', 'Ngoại ngữ','NN2022',[2,5]],
    ['Nguyễn Văn Em',     '0934111005', 'IT', 'CNTT2022',  [4]],
    ['Đỗ Thị Phương',     '0934111006', 'Y tế', 'YT2022',  [1]],
    ['Bùi Văn Giang',     '0934111007', 'Luật', 'LU2021',  [2,3]],
    ['Vũ Thị Hằng',       '0934111008', 'Kinh tế','KT2021', [5]],
    ['Phan Văn Hùng',     '0934111009', 'IT', 'CNTT2021',  [4,5]],
    ['Đinh Thị Lan',      '0934111010', 'Y tế', 'YT2020',  [1,2,5]],
    ['Lý Văn Minh',       '0934111011', 'IT', 'CNTT2023',  [4]],
    ['Cao Thị Ngọc',      '0934111012', 'Ngoại ngữ','NN2021',[2]],
    ['Tô Văn Oanh',       '0934111013', 'Kinh tế','KT2023', [3,5]],
    ['Mai Thị Phúc',      '0934111014', 'Y tế','YT2023',    [1,3]],
    ['Chu Văn Quân',      '0934111015', 'IT', 'CNTT2022',  [4,2]],
    ['Đinh Thị Rạng',     '0934111016', 'Luật','LU2022',   [5]],
    ['Trương Văn Sơn',    '0934111017', 'Kinh tế','KT2022', [3]],
    ['Ngô Thị Tuyết',     '0934111018', 'Ngoại ngữ','NN2023',[2,5]],
    ['Hồ Văn Uy',         '0934111019', 'IT','CNTT2021',   [4,1]],
    ['Đặng Thị Vân',      '0934111020', 'Y tế','YT2021',   [1,2]],
    ['Kiều Văn Xuân',     '0934111021', 'Kinh tế','KT2022', [3,5]],
    ['Nông Thị Yến',      '0934111022', 'Ngoại ngữ','NN2022',[2]],
    ['Lưu Văn Zùng',      '0934111023', 'IT','CNTT2022',   [4,3]],
    ['Hà Thị An',         '0934111024', 'Y tế','YT2023',    [1]],
    ['Đoàn Văn Bình',     '0934111025', 'Luật','LU2021',   [2,4]],
    ['Mạc Thị Châu',      '0934111026', 'Kinh tế','KT2021', [5,3]],
    ['Lương Văn Dũng',    '0934111027', 'IT','CNTT2022',   [4]],
    ['Huỳnh Thị Oanh',    '0934111028', 'Y tế','YT2022',   [1,5]],
    ['Trịnh Văn Phong',   '0934111029', 'Luật','LU2023',   [2,3]],
    ['Phùng Thị Quỳnh',   '0934111030', 'Ngoại ngữ','NN2023',[2,5]],
];

$userStmt = $pdo->prepare(
    "INSERT INTO users (email, password_hash, full_name, phone, status, email_verified) VALUES (?,?,?,?,?,?)"
);
$profileStmt = $pdo->prepare(
    "INSERT INTO volunteer_profiles (user_id, student_id, faculty, class_name) VALUES (?,?,?,?)"
);
$roleStmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?,?)");
$skillStmt = $pdo->prepare("INSERT INTO volunteer_skills (user_id, skill_id, level) VALUES (?,?,?)");

$levels = ['beginner', 'intermediate', 'advanced'];
$volunteerIds = [];

foreach ($volunteers as $i => $v) {
    $idx = $i + 1;
    $email = "tnv{$idx}@volunteerhub.vn";
    $userStmt->execute([$email, $pass_demo, $v[0], $v[1], 'active', 1]);
    $uid = $pdo->lastInsertId();
    $volunteerIds[] = $uid;

    $profileStmt->execute([$uid, "SV{$idx}2022", $v[2], $v[3]]);
    $roleStmt->execute([$uid, $roleIds['volunteer']]);

    // Gán kỹ năng
    foreach ($v[4] as $skillId) {
        $lvl = $levels[array_rand($levels)];
        $skillStmt->execute([$uid, $skillId, $lvl]);
    }
}
echo "✔ Đã tạo 30 tình nguyện viên\n";

// =============================================
// 4. ACTIVITIES & SHIFTS
// =============================================

// Đặt hoạt động mẫu trong tương lai để luôn còn hạn đăng ký khi seed lại.
$baseDate = date('Y-m-d', strtotime('+7 days'));

// Hoạt động 1: Ngày hội hiến máu (Bộ A - đủ người)
$pdo->prepare(
    "INSERT INTO activities (coordinator_id, title, description, location, date_start, date_end, registration_deadline, status) 
     VALUES (?,?,?,?,?,?,?,?)"
)->execute([
    $coord1Id,
    'Ngày Hội Hiến Máu Tình Nguyện',
    'Chương trình hiến máu tình nguyện thường niên, hưởng ứng phong trào "Mỗi giọt máu cho đi, một cuộc đời ở lại".',
    'Hội trường A, Trường Đại học XYZ',
    "$baseDate 07:00:00",
    "$baseDate 17:00:00",
    date('Y-m-d H:i:s', strtotime("$baseDate -2 days")),
    'open'
]);
$act1Id = $pdo->lastInsertId();

// Hoạt động 2: Hiến máu 2 ngày (Bộ B - thiếu người/kỹ năng)
$date2 = date('Y-m-d', strtotime("$baseDate +7 days"));
$pdo->prepare(
    "INSERT INTO activities (coordinator_id, title, description, location, date_start, date_end, registration_deadline, status)
     VALUES (?,?,?,?,?,?,?,?)"
)->execute([
    $coord1Id,
    'Hỗ Trợ Y Tế Cộng Đồng',
    'Chương trình khám sức khỏe miễn phí cho người dân vùng ngoại thành, yêu cầu tình nguyện viên có kỹ năng y tế.',
    'Trạm y tế phường Tân Phú, Quận 7',
    "$date2 08:00:00",
    "$date2 16:00:00",
    date('Y-m-d H:i:s', strtotime("$date2 -3 days")),
    'open'
]);
$act2Id = $pdo->lastInsertId();

// Hoạt động 3: Sự kiện giao lưu (Bộ C - trùng lịch)
$date3 = date('Y-m-d', strtotime("$baseDate +14 days"));
$pdo->prepare(
    "INSERT INTO activities (coordinator_id, title, description, location, date_start, date_end, registration_deadline, status)
     VALUES (?,?,?,?,?,?,?,?)"
)->execute([
    $coord2Id,
    'Giao Lưu Sinh Viên Quốc Tế',
    'Sự kiện giao lưu, trao đổi văn hóa với sinh viên quốc tế, cần tình nguyện viên thông thạo tiếng Anh.',
    'Khu vực B - Trung tâm Hội nghị',
    "$date3 09:00:00",
    "$date3 18:00:00",
    date('Y-m-d H:i:s', strtotime("$date3 -1 day")),
    'open'
]);
$act3Id = $pdo->lastInsertId();

// Hoạt động 4: Sự kiện bổ sung
$date4 = date('Y-m-d', strtotime("$baseDate +21 days"));
$pdo->prepare(
    "INSERT INTO activities (coordinator_id, title, description, location, date_start, date_end, registration_deadline, status)
     VALUES (?,?,?,?,?,?,?,?)"
)->execute([
    $coord2Id,
    'Chương Trình Xanh - Trồng Cây Môi Trường',
    'Trồng cây xanh và dọn vệ sinh môi trường tại công viên thành phố.',
    'Công viên Tao Đàn',
    "$date4 07:30:00",
    "$date4 11:30:00",
    date('Y-m-d H:i:s', strtotime("$date4 -2 days")),
    'open'
]);
$act4Id = $pdo->lastInsertId();

echo "✔ Đã tạo 4 hoạt động\n";

// =============================================
// 5. SHIFTS (12 ca trực)
// =============================================
$shiftStmt = $pdo->prepare(
    "INSERT INTO shifts (activity_id, name, description, date, time_start, time_end, min_volunteers, max_volunteers, status)
     VALUES (?,?,?,?,?,?,?,?,?)"
);
$srsStmt = $pdo->prepare("INSERT INTO shift_required_skills (shift_id, skill_id, is_mandatory) VALUES (?,?,?)");

// --- Hoạt động 1: Hiến máu (Bộ A - đủ người) ---
// Ca 1: Tiếp đón (sáng)
$shiftStmt->execute([$act1Id, 'Tiếp Đón Buổi Sáng', 'Hướng dẫn, tiếp đón người hiến máu', $baseDate, '07:30:00', '11:30:00', 3, 6, 'open']);
$s1 = $pdo->lastInsertId();
$srsStmt->execute([$s1, 5, 1]); // Dẫn chương trình - bắt buộc

// Ca 2: Y tế sáng
$shiftStmt->execute([$act1Id, 'Hỗ Trợ Y Tế Buổi Sáng', 'Sơ cứu, theo dõi sức khỏe người hiến máu', $baseDate, '07:30:00', '11:30:00', 2, 4, 'open']);
$s2 = $pdo->lastInsertId();
$srsStmt->execute([$s2, 1, 1]); // Y tế sơ cứu - bắt buộc

// Ca 3: Tiếp đón chiều
$shiftStmt->execute([$act1Id, 'Tiếp Đón Buổi Chiều', 'Hướng dẫn, tiếp đón người hiến máu buổi chiều', $baseDate, '13:00:00', '17:00:00', 3, 6, 'open']);
$s3 = $pdo->lastInsertId();
$srsStmt->execute([$s3, 5, 1]); // Dẫn chương trình - bắt buộc

// Ca 4: Y tế chiều
$shiftStmt->execute([$act1Id, 'Hỗ Trợ Y Tế Buổi Chiều', 'Sơ cứu, theo dõi sức khỏe người hiến máu chiều', $baseDate, '13:00:00', '17:00:00', 2, 4, 'open']);
$s4 = $pdo->lastInsertId();
$srsStmt->execute([$s4, 1, 1]); // Y tế sơ cứu - bắt buộc

// --- Hoạt động 2: Y tế cộng đồng (Bộ B - thiếu người/kỹ năng) ---
// Ca 5: Khám bệnh chuyên sâu - yêu cầu kỹ năng y tế cao
$shiftStmt->execute([$act2Id, 'Khám Sức Khoẻ Buổi Sáng', 'Hỗ trợ y bác sĩ khám sức khoẻ cơ bản', $date2, '08:00:00', '12:00:00', 5, 8, 'open']);
$s5 = $pdo->lastInsertId();
$srsStmt->execute([$s5, 1, 1]); // Y tế sơ cứu - bắt buộc (chỉ ~7 người có)
$srsStmt->execute([$s5, 3, 0]); // Lái xe máy - ưu tiên

// Ca 6: Phát thuốc và tư vấn
$shiftStmt->execute([$act2Id, 'Phát Thuốc và Tư Vấn', 'Phát thuốc miễn phí và tư vấn sức khoẻ', $date2, '09:00:00', '15:00:00', 4, 6, 'open']);
$s6 = $pdo->lastInsertId();
$srsStmt->execute([$s6, 1, 1]); // Y tế sơ cứu - bắt buộc
$srsStmt->execute([$s6, 2, 1]); // Tiếng Anh - bắt buộc (người nước ngoài)
// Bộ B: ít người đủ CẢ 2 kỹ năng => thiếu người

// Ca 7: Hỗ trợ vận chuyển
$shiftStmt->execute([$act2Id, 'Hỗ Trợ Vận Chuyển', 'Vận chuyển thiết bị y tế và đưa đón bệnh nhân', $date2, '07:00:00', '17:00:00', 3, 5, 'open']);
$s7 = $pdo->lastInsertId();
$srsStmt->execute([$s7, 3, 1]); // Lái xe máy - bắt buộc (chỉ ~5 người có)

// --- Hoạt động 3: Giao lưu (Bộ C - trùng lịch) ---
// Ca 8-10: Nhiều ca cùng ngày, cùng giờ - tạo xung đột lịch
$shiftStmt->execute([$act3Id, 'Đón Tiếp Sinh Viên Quốc Tế', 'Đón và hướng dẫn sinh viên nước ngoài', $date3, '09:00:00', '12:00:00', 3, 5, 'open']);
$s8 = $pdo->lastInsertId();
$srsStmt->execute([$s8, 2, 1]); // Tiếng Anh - bắt buộc

$shiftStmt->execute([$act3Id, 'Phiên Dịch Buổi Sáng', 'Phiên dịch tại hội trường chính', $date3, '09:30:00', '12:00:00', 2, 4, 'open']);
$s9 = $pdo->lastInsertId();
$srsStmt->execute([$s9, 2, 1]); // Tiếng Anh - bắt buộc (trùng giờ với Ca 8)

$shiftStmt->execute([$act3Id, 'Giao Lưu Văn Hóa Buổi Chiều', 'Tổ chức hoạt động giao lưu văn hóa', $date3, '14:00:00', '18:00:00', 4, 8, 'open']);
$s10 = $pdo->lastInsertId();
$srsStmt->execute([$s10, 2, 1]); // Tiếng Anh - bắt buộc
$srsStmt->execute([$s10, 5, 1]); // Dẫn chương trình - bắt buộc

$shiftStmt->execute([$act3Id, 'Hỗ Trợ Kỹ Thuật', 'Âm thanh, ánh sáng, IT cho sự kiện', $date3, '08:00:00', '18:00:00', 2, 3, 'open']);
$s11 = $pdo->lastInsertId();
$srsStmt->execute([$s11, 4, 1]); // CNTT - bắt buộc

// --- Hoạt động 4: Trồng cây ---
$shiftStmt->execute([$act4Id, 'Trồng Cây Buổi Sáng', 'Trồng cây, dọn vệ sinh khu vực A và B', $date4, '07:30:00', '11:30:00', 5, 12, 'open']);
$s12 = $pdo->lastInsertId();
// Không yêu cầu kỹ năng đặc biệt

$shiftIds = [$s1, $s2, $s3, $s4, $s5, $s6, $s7, $s8, $s9, $s10, $s11, $s12];
echo "✔ Đã tạo 12 ca trực\n";

// =============================================
// 6. AVAILABILITY (Lịch rảnh)
// =============================================
$availStmt = $pdo->prepare(
    "INSERT INTO availability (user_id, date, time_start, time_end, note) VALUES (?,?,?,?,?)"
);

// Hàm tạo lịch rảnh cho một ngày và nhiều TNV
function addAvailability(PDOStatement $stmt, array $userIds, string $date, string $tStart, string $tEnd, string $note = ''): void {
    foreach ($userIds as $uid) {
        $stmt->execute([$uid, $date, $tStart, $tEnd, $note]);
    }
}

// Bộ A - Ngày $baseDate: Hầu hết TNV đều rảnh cả ngày
$allDayUsers = array_slice($volunteerIds, 0, 20); // 20 TNV đầu rảnh cả ngày
addAvailability($availStmt, $allDayUsers, $baseDate, '07:00:00', '18:00:00', 'Rảnh cả ngày - Bộ A');

// Một số TNV chỉ rảnh buổi sáng hoặc chiều
addAvailability($availStmt, array_slice($volunteerIds, 20, 5), $baseDate, '07:00:00', '12:00:00', 'Rảnh buổi sáng - Bộ A');
addAvailability($availStmt, array_slice($volunteerIds, 25, 5), $baseDate, '13:00:00', '18:00:00', 'Rảnh buổi chiều - Bộ A');

// Bộ B - Ngày $date2: Ít người rảnh (tạo tình huống thiếu người)
// Chỉ 8 người rảnh, trong đó ít người có kỹ năng y tế
addAvailability($availStmt, array_slice($volunteerIds, 0, 8), $date2, '07:00:00', '17:00:00', 'Rảnh cả ngày - Bộ B');
addAvailability($availStmt, array_slice($volunteerIds, 8, 3), $date2, '08:00:00', '12:00:00', 'Rảnh sáng - Bộ B');

// Bộ C - Ngày $date3: Nhiều người rảnh nhưng ca trùng giờ
// 15 người rảnh nhưng nhiều ca cùng lúc
addAvailability($availStmt, array_slice($volunteerIds, 0, 15), $date3, '08:00:00', '18:00:00', 'Rảnh cả ngày - Bộ C');
addAvailability($availStmt, array_slice($volunteerIds, 15, 5), $date3, '09:00:00', '12:00:00', 'Rảnh sáng - Bộ C');
addAvailability($availStmt, array_slice($volunteerIds, 20, 5), $date3, '14:00:00', '18:00:00', 'Rảnh chiều - Bộ C');

// Ngày $date4: Trồng cây - nhiều người rảnh
addAvailability($availStmt, array_slice($volunteerIds, 0, 25), $date4, '07:00:00', '12:00:00', 'Rảnh sáng - Trồng cây');

echo "✔ Đã tạo lịch rảnh\n";

// =============================================
// 7. REGISTRATIONS (Đăng ký ca)
// =============================================
$regStmt = $pdo->prepare(
    "INSERT INTO registrations (user_id, shift_id, status, note, registered_at) VALUES (?,?,?,?,?)"
);
$regDate = date('Y-m-d H:i:s', strtotime($baseDate . ' -5 days'));

// Helper: đăng ký nhiều người vào một ca
function registerUsers(PDOStatement $stmt, array $userIds, int|string $shiftId, string $status = 'approved', string $date = ''): void {
    foreach ($userIds as $uid) {
        try {
            $stmt->execute([(int)$uid, (int)$shiftId, $status, 'Đăng ký tham gia', $date ?: date('Y-m-d H:i:s')]);
        } catch (Exception $e) {
            // Bỏ qua nếu đã đăng ký (UNIQUE constraint)
        }
    }
}

// Bộ A - Ca 1 (Tiếp đón sáng): Cần 3-6 người, có kỹ năng MC (skill 5)
// Người có skill 5: v4(Dung), v8(Hằng), v9(Hùng), v13(Oanh), v16(Rạng), v18(Tuyết), v21(Xuân), v26(Châu), v29(Phong), v30(Quỳnh)
$mcUsers = [$volunteerIds[3], $volunteerIds[7], $volunteerIds[8], $volunteerIds[12], $volunteerIds[15]];
registerUsers($regStmt, $mcUsers, $s1, 'approved', $regDate);

// Ca 2 (Y tế sáng): Cần 2-4 người, có kỹ năng Y tế (skill 1)
$medUsers = [$volunteerIds[0], $volunteerIds[1], $volunteerIds[5], $volunteerIds[9], $volunteerIds[13]];
registerUsers($regStmt, $medUsers, $s2, 'approved', $regDate);

// Ca 3 (Tiếp đón chiều): Đủ người
$mc2Users = [$volunteerIds[17], $volunteerIds[20], $volunteerIds[25], $volunteerIds[28], $volunteerIds[29]];
registerUsers($regStmt, $mc2Users, $s3, 'approved', $regDate);

// Ca 4 (Y tế chiều): Đủ người
$med2Users = [$volunteerIds[19], $volunteerIds[23], $volunteerIds[27], $volunteerIds[6]];
registerUsers($regStmt, $med2Users, $s4, 'approved', $regDate);

// Bộ B - Ca 5 (Khám bệnh): Yêu cầu Y tế, thiếu người đăng ký
$regDate2 = date('Y-m-d H:i:s', strtotime($date2 . ' -5 days'));
$med3Users = [$volunteerIds[0], $volunteerIds[1], $volunteerIds[5]]; // chỉ 3 người (cần tối thiểu 5)
registerUsers($regStmt, $med3Users, $s5, 'approved', $regDate2);

// Ca 6 (Phát thuốc): Yêu cầu Y tế VÀ Tiếng Anh - rất ít người đủ cả hai
// Đinh Thị Lan (idx 9) có skill 1,2,5
$dual1Users = [$volunteerIds[9], $volunteerIds[19]]; // chỉ 2 người (cần 4)
registerUsers($regStmt, $dual1Users, $s6, 'approved', $regDate2);

// Ca 7 (Vận chuyển): Yêu cầu lái xe (skill 3)
$driveUsers = [$volunteerIds[2], $volunteerIds[6], $volunteerIds[12]]; // 3 người = vừa đủ min
registerUsers($regStmt, $driveUsers, $s7, 'approved', $regDate2);

// Bộ C - Ca 8,9 trùng giờ - dùng chung pool người có Tiếng Anh
$regDate3 = date('Y-m-d H:i:s', strtotime($date3 . ' -2 days'));
// Người có skill 2 (Tiếng Anh): v2(Bình? No), v4(Dung), v7(Giang), v10(Lan), v12(Ngọc), v15(Quân), v18(Tuyết), v22(Yến), v25(Bình), v29(Phong), v30(Quỳnh)
$engUsers = [$volunteerIds[3], $volunteerIds[6], $volunteerIds[9], $volunteerIds[11], $volunteerIds[14]];
registerUsers($regStmt, $engUsers, $s8, 'approved', $regDate3); // Ca 8
// Ca 9 trùng giờ với Ca 8 - một số người đăng ký cả hai (sẽ bị phát hiện xung đột)
$eng2Users = [$volunteerIds[3], $volunteerIds[17], $volunteerIds[21], $volunteerIds[28]]; // v4 đăng ký cả s8 và s9
registerUsers($regStmt, $eng2Users, $s9, 'approved', $regDate3);

// Ca 10 (giao lưu chiều): Cần Tiếng Anh + MC
$engMcUsers = [$volunteerIds[3], $volunteerIds[8], $volunteerIds[14], $volunteerIds[17]];
registerUsers($regStmt, $engMcUsers, $s10, 'approved', $regDate3);

// Ca 11 (CNTT)
$itUsers = [$volunteerIds[0], $volunteerIds[4], $volunteerIds[8], $volunteerIds[10]];
registerUsers($regStmt, $itUsers, $s11, 'approved', $regDate3);

// Ca 12 (Trồng cây - không yêu cầu kỹ năng)
$regDate4 = date('Y-m-d H:i:s', strtotime($date4 . ' -3 days'));
registerUsers($regStmt, array_slice($volunteerIds, 0, 20), $s12, 'approved', $regDate4);

echo "✔ Đã tạo đăng ký\n";

// =============================================
// 8. NOTIFICATIONS mẫu
// =============================================
$notifStmt = $pdo->prepare(
    "INSERT INTO notifications (user_id, type, title, message, link, is_read) VALUES (?,?,?,?,?,?)"
);
$notifStmt->execute([
    $volunteerIds[0], 'system',
    'Chào mừng đến với VolunteerHub!',
    'Tài khoản của bạn đã được tạo thành công. Hãy cập nhật hồ sơ và đăng ký tham gia hoạt động.',
    '/volunteer/profile',
    0
]);
$notifStmt->execute([
    $volunteerIds[0], 'assignment',
    'Bạn đã được phân công vào Ca trực',
    'Bạn được phân công vào ca "Hỗ Trợ Y Tế Buổi Sáng" ngày ' . $baseDate . '. Vui lòng xác nhận tham gia.',
    '/volunteer/assignments',
    0
]);

echo "✔ Đã tạo thông báo mẫu\n";

// =============================================
// 9. AUDIT LOG mẫu
// =============================================
$pdo->prepare(
    "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, new_data, ip_address) VALUES (?,?,?,?,?,?)"
)->execute([
    $adminId, 'system_init', 'database', null,
    json_encode(['message' => 'Khởi tạo dữ liệu mẫu', 'timestamp' => date('c')]),
    '127.0.0.1'
]);

echo "✔ Đã tạo nhật ký mẫu\n";

// =============================================
// SUMMARY
// =============================================
echo "\n============================================\n";
echo "✅ SEED HOÀN THÀNH!\n";
echo "============================================\n";
echo "Roles:          " . count($roles) . "\n";
echo "Skills:         " . count($skills) . "\n";
echo "Admin:          1 (admin@volunteerhub.vn / Admin@2024)\n";
echo "Coordinators:   2 (coord1@volunteerhub.vn / Coord@123)\n";
echo "Volunteers:     " . count($volunteers) . " (tnv1@volunteerhub.vn .. tnv30@..  / Demo@123)\n";
echo "Activities:     4\n";
echo "Shifts:         12\n";
echo "\nBộ A (Đủ người): Hoạt động $act1Id - Ca $s1, $s2, $s3, $s4\n";
echo "Bộ B (Thiếu người): Hoạt động $act2Id - Ca $s5, $s6, $s7\n";
echo "Bộ C (Trùng lịch): Hoạt động $act3Id - Ca $s8, $s9, $s10, $s11\n";
echo "============================================\n";
