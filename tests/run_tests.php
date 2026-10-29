<?php
/**
 * tests/run_tests.php
 * Bộ kiểm thử tự động toàn diện cho hệ thống VolunteerHub (TC01 -> TC05)
 * Chạy bằng lệnh: php tests/run_tests.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();

require_once __DIR__ . '/ConstraintValidator.php';

use App\Services\BaselineGreedy;
use App\Services\ImprovedGreedy;
use App\Services\AssignmentService;
use Tests\ConstraintValidator;

class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];

    public function runAll(): void
    {
        $this->printHeader("BẮT ĐẦU KIỂM THỬ TỰ ĐỘNG VOLUNTEERHUB");

        $this->testTC01_MandatorySkills();
        $this->testTC02_Availability();
        $this->testTC03_NoOverlap();
        $this->testTC04_CapacityLimit();
        $this->testTC05_AlgorithmComparison();
        $this->testDatabaseDatasets();

        $this->printSummary();
    }

    /**
     * TC01: Kiểm tra ràng buộc kỹ năng bắt buộc
     */
    private function testTC01_MandatorySkills(): void
    {
        $shifts = [
            [
                'id' => 1, 'name' => 'Ca Y tế', 'date' => '2024-03-20',
                'time_start' => '08:00:00', 'time_end' => '12:00:00',
                'min_volunteers' => 1, 'max_volunteers' => 2,
                'mandatory_skills' => [101] // Kỹ năng 101 là bắt buộc
            ]
        ];

        $volunteers = [
            // User 1: ĐỦ kỹ năng 101, rảnh cả ngày
            [
                'id' => 1, 'full_name' => 'Nguyễn Văn Có Kỹ Năng',
                'skills' => [101, 102],
                'availability' => [['date' => '2024-03-20', 'time_start' => '07:00:00', 'time_end' => '18:00:00']]
            ],
            // User 2: THIẾU kỹ năng 101, rảnh cả ngày
            [
                'id' => 2, 'full_name' => 'Trần Văn Thiếu Kỹ Năng',
                'skills' => [102], // chỉ có 102
                'availability' => [['date' => '2024-03-20', 'time_start' => '07:00:00', 'time_end' => '18:00:00']]
            ]
        ];

        $registrations = [1 => [1, 2]];

        $improved = new ImprovedGreedy();
        $res = $improved->execute($shifts, $volunteers, $registrations);

        $assigned = $res['assignments'][1] ?? [];
        $hasUser1 = in_array(1, $assigned, true);
        $hasUser2 = in_array(2, $assigned, true);

        // Xác thực qua validator độc lập
        $val = ConstraintValidator::validate($shifts, $volunteers, $res['assignments']);

        $pass = ($hasUser1 && !$hasUser2 && $val['is_valid']);
        $this->record("TC01 - Ràng buộc kỹ năng bắt buộc", $pass, 
            $pass ? "User 1 (đủ kỹ năng) được chọn, User 2 (thiếu kỹ năng) bị loại trừ." 
                  : "Lỗi: User thiếu kỹ năng vẫn bị phân công!");
    }

    /**
     * TC02: Tình nguyện viên đã đăng ký không cần khai báo rảnh toàn ca
     */
    private function testTC02_Availability(): void
    {
        $shifts = [
            [
                'id' => 1, 'name' => 'Ca Chiều', 'date' => '2024-03-20',
                'time_start' => '13:00:00', 'time_end' => '17:00:00',
                'min_volunteers' => 1, 'max_volunteers' => 3,
                'mandatory_skills' => []
            ]
        ];

        $volunteers = [
            // User 1: Rảnh toàn bộ ca (12:00 -> 18:00)
            [
                'id' => 1, 'full_name' => 'User Rảnh Đủ',
                'skills' => [],
                'availability' => [['date' => '2024-03-20', 'time_start' => '12:00:00', 'time_end' => '18:00:00']]
            ],
            // User 2: Chỉ khai báo rảnh nửa ca nhưng đã đăng ký
            [
                'id' => 2, 'full_name' => 'User Rảnh Thiếu Giờ',
                'skills' => [],
                'availability' => [['date' => '2024-03-20', 'time_start' => '13:00:00', 'time_end' => '15:00:00']]
            ],
            // User 3: Không khai báo rảnh đúng ngày nhưng đã đăng ký
            [
                'id' => 3, 'full_name' => 'User Không Rảnh',
                'skills' => [],
                'availability' => [['date' => '2024-03-21', 'time_start' => '08:00:00', 'time_end' => '18:00:00']]
            ]
        ];

        $registrations = [1 => [1, 2, 3]];

        $improved = new ImprovedGreedy();
        $res = $improved->execute($shifts, $volunteers, $registrations);

        $assigned = $res['assignments'][1] ?? [];
        $baseline = new BaselineGreedy();
        $baselineRes = $baseline->execute($shifts, $volunteers, $registrations);
        $baselineAssigned = $baselineRes['assignments'][1] ?? [];
        $pass = (count($assigned) === 3 && count($baselineAssigned) === 3
            && in_array(1, $assigned, true) && in_array(2, $assigned, true) && in_array(3, $assigned, true)
            && in_array(1, $baselineAssigned, true) && in_array(2, $baselineAssigned, true) && in_array(3, $baselineAssigned, true));

        $val = ConstraintValidator::validate($shifts, $volunteers, $res['assignments']);

        $pass = ($pass && $val['is_valid']);
        $this->record("TC02 - Không yêu cầu khai báo rảnh toàn ca với người đã đăng ký", $pass,
            $pass ? "Hai thuật toán phân công đủ cả 3 TNV đã đăng ký dù chỉ rảnh một phần hoặc chưa khai báo lịch rảnh."
                  : "Lỗi: TNV đã đăng ký nhưng bị loại do chưa khai báo rảnh toàn ca!");
    }

    /**
     * TC03: Kiểm tra ràng buộc chống trùng ca (thời gian chồng lấn)
     */
    private function testTC03_NoOverlap(): void
    {
        $shifts = [
            [
                'id' => 1, 'name' => 'Ca Sáng A', 'date' => '2024-03-20',
                'time_start' => '08:00:00', 'time_end' => '11:00:00',
                'min_volunteers' => 1, 'max_volunteers' => 1,
                'mandatory_skills' => []
            ],
            [
                'id' => 2, 'name' => 'Ca Sáng B (Trùng giờ Ca A)', 'date' => '2024-03-20',
                'time_start' => '10:00:00', 'time_end' => '12:00:00', // Giao thoa 10:00 - 11:00
                'min_volunteers' => 1, 'max_volunteers' => 1,
                'mandatory_skills' => []
            ],
        ];

        $volunteers = [
            // User 1: Đăng ký cả 2 ca, rảnh cả ngày
            [
                'id' => 1, 'full_name' => 'User Đăng Ký Cả 2 Ca Trùng',
                'skills' => [],
                'availability' => [['date' => '2024-03-20', 'time_start' => '07:00:00', 'time_end' => '18:00:00']]
            ]
        ];

        $registrations = [
            1 => [1],
            2 => [1],
        ];

        $improved = new ImprovedGreedy();
        $res = $improved->execute($shifts, $volunteers, $registrations);

        $inShift1 = in_array(1, $res['assignments'][1] ?? [], true);
        $inShift2 = in_array(1, $res['assignments'][2] ?? [], true);

        // User 1 KHÔNG THỂ có mặt ở cả 2 ca (chỉ được xếp vào tối đa 1 ca)
        $pass = !($inShift1 && $inShift2);

        $val = ConstraintValidator::validate($shifts, $volunteers, $res['assignments']);
        $pass = ($pass && $val['is_valid'] && $val['violations_count'] === 0);

        $this->record("TC03 - Ràng buộc chống trùng ca (Overlap)", $pass,
            $pass ? "User chỉ được xếp vào 1 trong 2 ca bị chồng lấn thời gian. 0 vi phạm trùng lặp."
                  : "Lỗi nghiêm trọng: Một người bị xếp vào 2 ca có giờ trùng nhau!");
    }

    /**
     * TC04: Kiểm tra giới hạn số người tối đa của ca
     */
    private function testTC04_CapacityLimit(): void
    {
        $shifts = [
            [
                'id' => 1, 'name' => 'Ca Giới Hạn Tối Đa 2', 'date' => '2024-03-20',
                'time_start' => '08:00:00', 'time_end' => '12:00:00',
                'min_volunteers' => 1, 'max_volunteers' => 2, // Tối đa chỉ 2 người
                'mandatory_skills' => []
            ]
        ];

        // 5 ứng viên đều hợp lệ và rảnh
        $volunteers = [];
        for ($i = 1; $i <= 5; $i++) {
            $volunteers[] = [
                'id' => $i, 'full_name' => "User $i",
                'skills' => [],
                'availability' => [['date' => '2024-03-20', 'time_start' => '07:00:00', 'time_end' => '18:00:00']]
            ];
        }

        $registrations = [1 => [1, 2, 3, 4, 5]];

        $improved = new ImprovedGreedy();
        $res = $improved->execute($shifts, $volunteers, $registrations);

        $assignedCount = count($res['assignments'][1] ?? []);
        $pass = ($assignedCount === 2);

        $val = ConstraintValidator::validate($shifts, $volunteers, $res['assignments']);
        $pass = ($pass && $val['is_valid']);

        $this->record("TC04 - Giới hạn ca (Capacity Limit)", $pass,
            $pass ? "Có 5 người đăng ký nhưng hệ thống chỉ phân công đúng 2 người (đạt max_volunteers)."
                  : "Lỗi: Số người phân công vượt quá max_volunteers ($assignedCount > 2)!");
    }

    /**
     * TC05: So sánh hai thuật toán trên cùng một bộ dữ liệu
     */
    private function testTC05_AlgorithmComparison(): void
    {
        // Tạo kịch bản thử nghiệm: 3 ca (1 ca khó ít ứng viên, 2 ca dễ)
        $shifts = [
            [
                'id' => 1, 'name' => 'Ca Khó (Yêu cầu Y tế)', 'date' => '2024-03-20',
                'time_start' => '08:00:00', 'time_end' => '12:00:00',
                'min_volunteers' => 2, 'max_volunteers' => 2,
                'mandatory_skills' => [1] // Kỹ năng 1
            ],
            [
                'id' => 2, 'name' => 'Ca Sáng Chung', 'date' => '2024-03-20',
                'time_start' => '08:00:00', 'time_end' => '12:00:00', // Trùng giờ với ca 1
                'min_volunteers' => 2, 'max_volunteers' => 2,
                'mandatory_skills' => []
            ],
            [
                'id' => 3, 'name' => 'Ca Chiều Chung', 'date' => '2024-03-20',
                'time_start' => '13:00:00', 'time_end' => '17:00:00',
                'min_volunteers' => 2, 'max_volunteers' => 2,
                'mandatory_skills' => []
            ]
        ];

        // 4 volunteers: User 1 có kỹ năng 1, User 2 có kỹ năng 1, User 3 & 4 không có kỹ năng 1
        $volunteers = [
            ['id' => 1, 'full_name' => 'Bác Sĩ 1', 'skills' => [1], 'availability' => [['date' => '2024-03-20', 'time_start' => '07:00:00', 'time_end' => '18:00:00']]],
            ['id' => 2, 'full_name' => 'Bác Sĩ 2', 'skills' => [1], 'availability' => [['date' => '2024-03-20', 'time_start' => '07:00:00', 'time_end' => '18:00:00']]],
            ['id' => 3, 'full_name' => 'TNV Chung 3', 'skills' => [], 'availability' => [['date' => '2024-03-20', 'time_start' => '07:00:00', 'time_end' => '18:00:00']]],
            ['id' => 4, 'full_name' => 'TNV Chung 4', 'skills' => [], 'availability' => [['date' => '2024-03-20', 'time_start' => '07:00:00', 'time_end' => '18:00:00']]],
        ];

        // Tất cả cùng đăng ký ca 2 (dễ) và ca 1 (khó)
        // Baseline Greedy xét Ca 1 rồi Ca 2 tuần tự.
        // Improved Greedy nhận diện Ca 1 khó nhất (ít ứng viên đủ kỹ năng nhất) -> ưu tiên giải quyết trước!
        $registrations = [
            1 => [1, 2],
            2 => [1, 2, 3, 4],
            3 => [1, 2, 3, 4],
        ];

        $baseline = new BaselineGreedy();
        $baseRes = $baseline->execute($shifts, $volunteers, $registrations);

        $improved = new ImprovedGreedy();
        $impRes = $improved->execute($shifts, $volunteers, $registrations);

        $valBase = ConstraintValidator::validate($shifts, $volunteers, $baseRes['assignments']);
        $valImp = ConstraintValidator::validate($shifts, $volunteers, $impRes['assignments']);

        // Ràng buộc cứng: Cả hai phải có 0 vi phạm
        $zeroViolations = ($valBase['violations_count'] === 0 && $valImp['violations_count'] === 0);

        // Improved Greedy phải đạt tỷ lệ đủ người >= Baseline Greedy
        $betterOrEqualRate = ($impRes['metrics']['sufficient_rate'] >= $baseRes['metrics']['sufficient_rate']);

        // Improved Greedy phải có độ lệch ca nhỏ hơn hoặc bằng (cân bằng tải tốt hơn)
        $workloadBalanced = ($impRes['metrics']['shift_deviation'] <= $baseRes['metrics']['shift_deviation']);

        $pass = ($zeroViolations && $betterOrEqualRate && $workloadBalanced);

        $detail = sprintf(
            "Baseline: Đủ %s%%, Lệch %d ca, Vi phạm: %d | Improved: Đủ %s%%, Lệch %d ca, Vi phạm: %d",
            $baseRes['metrics']['sufficient_rate'], $baseRes['metrics']['shift_deviation'], $valBase['violations_count'],
            $impRes['metrics']['sufficient_rate'], $impRes['metrics']['shift_deviation'], $valImp['violations_count']
        );

        $this->record("TC05 - So sánh thực nghiệm hai thuật toán", $pass, $detail);
    }

    /**
     * Kiểm thử trên 3 bộ dữ liệu thực tế từ Database (Bộ A, Bộ B, Bộ C)
     */
    private function testDatabaseDatasets(): void
    {
        try {
            $service = new AssignmentService();

            // Bộ A: Hoạt động 1 (Đủ người)
            $compA = $service->compareAlgorithms(1);
            $inputA = $service->prepareInputData(1);
            $valA = ConstraintValidator::validate($inputA['shifts'], $inputA['volunteers'], $compA['improved']['assignments']);
            $passA = ($valA['is_valid'] && $compA['improved']['metrics']['violations_count'] === 0
                && $compA['improved']['metrics']['sufficient_rate'] == 100);
            $this->record("Database Set A - Kịch bản Đủ người (Hoạt động 1)", $passA,
                "Tỷ lệ ca đủ người: {$compA['improved']['metrics']['sufficient_rate']}% | Validator vi phạm: {$valA['violations_count']}");

            // Bộ B: Hoạt động 2 (Thiếu người & Kỹ năng y tế)
            $compB = $service->compareAlgorithms(2);
            $inputB = $service->prepareInputData(2);
            $valB = ConstraintValidator::validate($inputB['shifts'], $inputB['volunteers'], $compB['improved']['assignments']);
            $passB = ($valB['is_valid'] && $compB['improved']['metrics']['violations_count'] === 0
                && $compB['improved']['metrics']['sufficient_rate'] < 100);
            $this->record("Database Set B - Kịch bản Thiếu người & Kỹ năng (Hoạt động 2)", $passB,
                "Tỷ lệ ca đủ người: {$compB['improved']['metrics']['sufficient_rate']}% | Validator vi phạm: {$valB['violations_count']}");

            // Bộ C: Hoạt động 3 (Trùng lịch sáng/chiều)
            $compC = $service->compareAlgorithms(3);
            $inputC = $service->prepareInputData(3);
            $valC = ConstraintValidator::validate($inputC['shifts'], $inputC['volunteers'], $compC['improved']['assignments']);
            $passC = ($valC['is_valid'] && $compC['improved']['metrics']['violations_count'] === 0);
            $this->record("Database Set C - Kịch bản Xung đột lịch (Hoạt động 3)", $passC,
                "Validator vi phạm: {$valC['violations_count']} | Ca đủ người: {$compC['improved']['metrics']['sufficient_rate']}%");

        } catch (Exception $e) {
            $this->record("Database Datasets Test", false, "Lỗi kết nối CSDL: " . $e->getMessage());
        }
    }

    private function record(string $name, bool $pass, string $detail = ''): void
    {
        if ($pass) {
            $this->passed++;
            $this->results[] = ["\033[32m✔ PASS\033[0m", $name, $detail];
        } else {
            $this->failed++;
            $this->results[] = ["\033[31m✖ FAIL\033[0m", $name, $detail];
        }
    }

    private function printHeader(string $title): void
    {
        echo "\n=======================================================\n";
        echo "  " . $title . "\n";
        echo "=======================================================\n\n";
    }

    private function printSummary(): void
    {
        echo "-------------------------------------------------------\n";
        echo "KẾT QUẢ KIỂM THỬ:\n";
        echo "-------------------------------------------------------\n";
        foreach ($this->results as [$status, $name, $detail]) {
            echo " $status : $name\n";
            if (!empty($detail)) {
                echo "          -> $detail\n";
            }
        }
        echo "-------------------------------------------------------\n";
        $total = $this->passed + $this->failed;
        echo "Tổng số test: $total | \033[32mPassed: {$this->passed}\033[0m | \033[31mFailed: {$this->failed}\033[0m\n";
        if ($this->failed === 0) {
            echo "\033[32m🎉 TẤT CẢ TEST CASES ĐỀU VƯỢT QUA 100% THÀNH CÔNG!\033[0m\n";
        } else {
            echo "\033[31m⚠ CÓ TEST CASE THẤT BẠI, VUI LÒNG KIỂM TRA LẠI!\033[0m\n";
        }
        echo "=======================================================\n\n";
    }
}

$runner = new TestRunner();
$runner->runAll();
