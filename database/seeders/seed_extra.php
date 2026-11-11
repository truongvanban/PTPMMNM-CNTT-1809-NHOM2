<?php
/**
 * Thêm dữ liệu hoạt động mẫu mà không xóa dữ liệu hiện có.
 * Chạy lệnh: php database/seeders/seed_extra.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$appConfig = require $root . '/config/app.php';
if (getenv('APP_ENV') !== 'development' || $appConfig['env'] !== 'development') {
    fwrite(STDERR, "Chỉ được chạy seeder dữ liệu mẫu trong môi trường development.\n");
    exit(1);
}

$databaseConfig = require $root . '/config/database.php';
$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $databaseConfig['host'],
        $databaseConfig['port'],
        $databaseConfig['dbname'],
        $databaseConfig['charset']
    ),
    $databaseConfig['user'],
    $databaseConfig['pass'],
    $databaseConfig['options']
);

$activities = [
    [
        'title' => 'Ngày hội sách và tri thức cộng đồng',
        'category' => 'Giáo dục',
        'description' => 'Tổ chức ngày hội đọc sách, hướng dẫn khách tham quan và hỗ trợ đăng ký thư viện cộng đồng.',
        'location' => 'Nhà văn hóa Thanh Niên',
        'day_offset' => 10,
        'shifts' => [
            ['name' => 'Đón tiếp và hướng dẫn', 'description' => 'Đón khách, hướng dẫn khu vực trưng bày sách.', 'start' => '08:00:00', 'end' => '12:00:00', 'skills' => ['Dẫn chương trình']],
            ['name' => 'Hỗ trợ đăng ký thư viện', 'description' => 'Hỗ trợ nhập liệu và đăng ký thẻ thư viện.', 'start' => '13:00:00', 'end' => '17:00:00', 'skills' => ['Công nghệ thông tin']],
        ],
    ],
    [
        'title' => 'Ngày hội tư vấn sức khỏe cộng đồng',
        'category' => 'Y tế',
        'description' => 'Hỗ trợ đội ngũ y tế tổ chức tư vấn sức khỏe và hướng dẫn người dân tại địa phương.',
        'location' => 'Trung tâm sinh hoạt cộng đồng Quận 7',
        'day_offset' => 14,
        'shifts' => [
            ['name' => 'Hỗ trợ tư vấn buổi sáng', 'description' => 'Hướng dẫn người dân và hỗ trợ khu vực sơ cứu.', 'start' => '08:00:00', 'end' => '12:00:00', 'skills' => ['Y tế sơ cứu']],
            ['name' => 'Điều phối khu vực buổi chiều', 'description' => 'Hỗ trợ giao tiếp và điều phối các bàn tư vấn.', 'start' => '13:00:00', 'end' => '17:00:00', 'skills' => ['Tiếng Anh giao tiếp']],
        ],
    ],
    [
        'title' => 'Tập huấn kỹ năng số cho thanh thiếu niên',
        'category' => 'Giáo dục',
        'description' => 'Hướng dẫn kỹ năng sử dụng máy tính và hỗ trợ các nhóm học viên thực hành.',
        'location' => 'Thư viện Khoa học Tổng hợp',
        'day_offset' => 18,
        'shifts' => [
            ['name' => 'Hướng dẫn máy tính cơ bản', 'description' => 'Hỗ trợ học viên thực hành tin học cơ bản.', 'start' => '08:00:00', 'end' => '12:00:00', 'skills' => ['Công nghệ thông tin']],
            ['name' => 'Hỗ trợ tài liệu song ngữ', 'description' => 'Hỗ trợ học viên và khách quốc tế trong buổi thực hành.', 'start' => '13:00:00', 'end' => '17:00:00', 'skills' => ['Tiếng Anh giao tiếp']],
        ],
    ],
    [
        'title' => 'Chủ nhật xanh tại kênh Nhiêu Lộc',
        'category' => 'Môi trường',
        'description' => 'Thu gom rác, làm sạch khu vực công cộng và tuyên truyền bảo vệ môi trường.',
        'location' => 'Tuyến đường Hoàng Sa, Quận 3',
        'day_offset' => 22,
        'shifts' => [
            ['name' => 'Thu gom và phân loại rác', 'description' => 'Thu gom rác và phân loại vật liệu tái chế.', 'start' => '07:30:00', 'end' => '11:30:00', 'skills' => []],
            ['name' => 'Truyền thông môi trường', 'description' => 'Hướng dẫn người dân và hỗ trợ truyền thông tại chỗ.', 'start' => '13:00:00', 'end' => '17:00:00', 'skills' => ['Dẫn chương trình']],
        ],
    ],
];

try {
    $coordinatorIds = $pdo->query(
        "SELECT u.id
         FROM users u
         JOIN user_roles ur ON ur.user_id = u.id
         JOIN roles r ON r.id = ur.role_id
         WHERE r.name = 'coordinator' AND u.status = 'active'
         ORDER BY u.id"
    )->fetchAll(PDO::FETCH_COLUMN);
    $volunteerIds = $pdo->query(
        "SELECT u.id
         FROM users u
         JOIN user_roles ur ON ur.user_id = u.id
         JOIN roles r ON r.id = ur.role_id
         WHERE r.name = 'volunteer' AND u.status = 'active'
         ORDER BY u.id"
    )->fetchAll(PDO::FETCH_COLUMN);

    if ($coordinatorIds === [] || $volunteerIds === []) {
        throw new RuntimeException('Cần có ít nhất một điều phối viên và một tình nguyện viên đang hoạt động.');
    }

    $skillRows = $pdo->query(
        'SELECT user_id, skill_id FROM volunteer_skills ORDER BY user_id, skill_id'
    )->fetchAll();
    $volunteerSkills = [];
    foreach ($skillRows as $row) {
        $volunteerSkills[(int)$row['user_id']][(int)$row['skill_id']] = true;
    }
    $skillIdsByName = $pdo->query(
        'SELECT name, id FROM skills WHERE is_active = 1'
    )->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($activities as $activity) {
        foreach ($activity['shifts'] as $shift) {
            foreach ($shift['skills'] as $skillName) {
                if (!isset($skillIdsByName[$skillName])) {
                    throw new RuntimeException("Thiếu kỹ năng mẫu đang hoạt động: {$skillName}");
                }
            }
        }
    }

    $today = new DateTimeImmutable('today');
    $findActivity = $pdo->prepare('SELECT id FROM activities WHERE title = ? LIMIT 1');
    $insertActivity = $pdo->prepare(
        'INSERT INTO activities
            (coordinator_id, title, description, category, location, date_start, date_end, registration_deadline, status, max_volunteers)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'open\', 30)'
    );
    $insertShift = $pdo->prepare(
        'INSERT INTO shifts
            (activity_id, name, description, date, time_start, time_end, min_volunteers, max_volunteers, status)
         VALUES (?, ?, ?, ?, ?, ?, 2, 8, \'open\')'
    );
    $insertSkill = $pdo->prepare(
        'INSERT INTO shift_required_skills (shift_id, skill_id, is_mandatory) VALUES (?, ?, 1)'
    );
    $insertRegistration = $pdo->prepare(
        'INSERT INTO registrations (user_id, shift_id, status, note) VALUES (?, ?, ?, ?)'
    );

    $createdActivities = 0;
    $createdShifts = 0;
    $createdRegistrations = 0;
    $pdo->beginTransaction();

    foreach ($activities as $activityIndex => $activity) {
        $findActivity->execute([$activity['title']]);
        if ($findActivity->fetchColumn() !== false) {
            continue;
        }

        $eventDate = $today->modify('+' . $activity['day_offset'] . ' days');
        $date = $eventDate->format('Y-m-d');
        $coordinatorId = (int)$coordinatorIds[$activityIndex % count($coordinatorIds)];
        $insertActivity->execute([
            $coordinatorId,
            $activity['title'],
            $activity['description'],
            $activity['category'],
            $activity['location'],
            $date . ' 07:00:00',
            $date . ' 18:00:00',
            $eventDate->modify('-2 days')->format('Y-m-d') . ' 23:59:59',
        ]);
        $activityId = (int)$pdo->lastInsertId();
        $createdActivities++;

        foreach ($activity['shifts'] as $shift) {
            $insertShift->execute([
                $activityId,
                $shift['name'],
                $shift['description'],
                $date,
                $shift['start'],
                $shift['end'],
            ]);
            $shiftId = (int)$pdo->lastInsertId();
            $createdShifts++;

            $requiredSkillIds = [];
            foreach ($shift['skills'] as $skillName) {
                $requiredSkillId = (int)$skillIdsByName[$skillName];
                $requiredSkillIds[] = $requiredSkillId;
                $insertSkill->execute([$shiftId, $requiredSkillId]);
            }

            $eligibleVolunteers = array_values(array_filter(
                array_map('intval', $volunteerIds),
                static function (int $volunteerId) use ($requiredSkillIds, $volunteerSkills): bool {
                    foreach ($requiredSkillIds as $skillId) {
                        if (!isset($volunteerSkills[$volunteerId][$skillId])) {
                            return false;
                        }
                    }
                    return true;
                }
            ));

            foreach (array_slice($eligibleVolunteers, 0, 5) as $index => $volunteerId) {
                $status = match ($index) {
                    0, 1, 2 => 'approved',
                    3 => 'pending',
                    default => 'rejected',
                };
                $insertRegistration->execute([
                    $volunteerId,
                    $shiftId,
                    $status,
                    'Đăng ký mẫu cho hoạt động cộng đồng.',
                ]);
                $createdRegistrations++;
            }
        }
    }

    $pdo->commit();
    echo "Đã thêm dữ liệu mẫu mà không xóa dữ liệu hiện có:\n";
    echo "- Hoạt động mới: {$createdActivities}\n";
    echo "- Ca trực mới: {$createdShifts}\n";
    echo "- Đăng ký mới: {$createdRegistrations}\n";
    if ($createdActivities === 0) {
        echo "Các hoạt động mẫu đã tồn tại; không thêm bản ghi trùng.\n";
    }
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Không thể thêm dữ liệu mẫu: ' . $exception->getMessage() . "\n");
    exit(1);
}
