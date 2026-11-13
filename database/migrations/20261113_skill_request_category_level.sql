-- Thêm cột category và level vào bảng skill_requests
-- Cho phép TNV chọn danh mục và trình độ khi gửi yêu cầu,
-- và Cho phép Admin/Điều phối viên ghi đè khi duyệt.

ALTER TABLE `skill_requests`
    ADD COLUMN `category` VARCHAR(100) DEFAULT NULL COMMENT 'Danh mục kỹ năng được đề xuất' AFTER `skill_name`,
    ADD COLUMN `level` ENUM('beginner','intermediate','advanced') DEFAULT NULL COMMENT 'Trình độ kỹ năng được đề xuất' AFTER `category`;
