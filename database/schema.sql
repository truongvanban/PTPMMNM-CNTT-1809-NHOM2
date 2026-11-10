-- VolunteerHub Database Schema
-- Phiên bản: 1.0
-- MySQL/MariaDB 10.4+
-- Tạo ngày: 2024
-- Mã hóa: UTF-8MB4 (hỗ trợ tiếng Việt đầy đủ)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Tạo database
CREATE DATABASE IF NOT EXISTS `volunteerhub`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `volunteerhub`;

-- ===========================================
-- BẢNG 1: roles - Vai trò người dùng
-- ===========================================
CREATE TABLE IF NOT EXISTS `roles` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(50) NOT NULL COMMENT 'volunteer, coordinator, admin',
    `display_name` VARCHAR(100) NOT NULL COMMENT 'Tên hiển thị tiếng Việt',
    `description` TEXT DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Vai trò trong hệ thống';

-- ===========================================
-- BẢNG 2: users - Tài khoản người dùng
-- ===========================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`           VARCHAR(191) NOT NULL,
    `password_hash`   VARCHAR(255) NOT NULL,
    `full_name`       VARCHAR(200) NOT NULL,
    `phone`           VARCHAR(20) DEFAULT NULL,
    `status`          ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
    `email_verified`  TINYINT(1) NOT NULL DEFAULT 0,
    `last_login_at`   DATETIME DEFAULT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`),
    KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tài khoản người dùng';

-- ===========================================
-- BẢNG 3: user_roles - Phân vai trò cho user
-- ===========================================
CREATE TABLE IF NOT EXISTS `user_roles` (
    `user_id`    INT UNSIGNED NOT NULL,
    `role_id`    INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `role_id`),
    KEY `idx_user_roles_role` (`role_id`),
    CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng liên kết user - vai trò';

-- ===========================================
-- BẢNG 4: volunteer_profiles - Hồ sơ TNV
-- ===========================================
CREATE TABLE IF NOT EXISTS `volunteer_profiles` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED NOT NULL,
    `student_id`    VARCHAR(50) DEFAULT NULL COMMENT 'Mã sinh viên/nhân viên',
    `date_of_birth` DATE DEFAULT NULL,
    `gender`        ENUM('male','female','other') DEFAULT NULL,
    `address`       VARCHAR(500) DEFAULT NULL,
    `faculty`       VARCHAR(200) DEFAULT NULL COMMENT 'Khoa/Phòng ban',
    `class_name`    VARCHAR(100) DEFAULT NULL COMMENT 'Lớp/Bộ phận',
    `bio`           TEXT DEFAULT NULL COMMENT 'Giới thiệu bản thân',
    `avatar_url`    VARCHAR(500) DEFAULT NULL,
    `total_hours`   DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng giờ tình nguyện',
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_vp_user` (`user_id`),
    CONSTRAINT `fk_vp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hồ sơ tình nguyện viên';

-- ===========================================
-- BẢNG 5: skills - Danh mục kỹ năng
-- ===========================================
CREATE TABLE IF NOT EXISTS `skills` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150) NOT NULL,
    `category`    VARCHAR(100) DEFAULT NULL COMMENT 'Nhóm kỹ năng',
    `description` TEXT DEFAULT NULL,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_skills_name` (`name`),
    KEY `idx_skills_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh mục kỹ năng';

-- ===========================================
-- BẢNG 6: volunteer_skills - Kỹ năng của TNV
-- ===========================================
CREATE TABLE IF NOT EXISTS `volunteer_skills` (
    `user_id`    INT UNSIGNED NOT NULL,
    `skill_id`   INT UNSIGNED NOT NULL,
    `level`      ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `skill_id`),
    KEY `idx_vs_skill` (`skill_id`),
    CONSTRAINT `fk_vs_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`  (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_vs_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kỹ năng của từng tình nguyện viên';

-- ===========================================
-- BẢNG 7: availability - Lịch rảnh TNV
-- ===========================================
CREATE TABLE IF NOT EXISTS `availability` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NOT NULL,
    `date`        DATE NOT NULL,
    `time_start`  TIME NOT NULL,
    `time_end`    TIME NOT NULL,
    `note`        VARCHAR(255) DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_avail_user_date` (`user_id`, `date`),
    CONSTRAINT `fk_avail_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_avail_time` CHECK (`time_end` > `time_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch rảnh của tình nguyện viên';

-- ===========================================
-- BẢNG 8: activities - Hoạt động
-- ===========================================
CREATE TABLE IF NOT EXISTS `activities` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `coordinator_id`  INT UNSIGNED NOT NULL COMMENT 'Điều phối viên quản lý',
    `title`           VARCHAR(300) NOT NULL,
    `description`     TEXT DEFAULT NULL,
    `category`        VARCHAR(100) DEFAULT NULL,
    `location`        VARCHAR(500) DEFAULT NULL,
    `date_start`      DATETIME NOT NULL,
    `date_end`        DATETIME NOT NULL,
    `registration_deadline` DATETIME DEFAULT NULL COMMENT 'Hạn đăng ký',
    `status`          ENUM('draft','open','closed','ongoing','completed','cancelled')
                      NOT NULL DEFAULT 'draft',
    `max_volunteers`  INT UNSIGNED DEFAULT NULL COMMENT 'Tổng tối đa TNV',
    `image_url`       VARCHAR(500) DEFAULT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_act_status` (`status`),
    KEY `idx_act_coordinator` (`coordinator_id`),
    KEY `idx_act_dates` (`date_start`, `date_end`),
    CONSTRAINT `fk_act_coord` FOREIGN KEY (`coordinator_id`) REFERENCES `users` (`id`),
    CONSTRAINT `chk_act_dates` CHECK (`date_end` > `date_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hoạt động tình nguyện';

CREATE TABLE IF NOT EXISTS `activity_required_skills` (
    `activity_id` INT UNSIGNED NOT NULL,
    `skill_id`    INT UNSIGNED NOT NULL,
    PRIMARY KEY (`activity_id`, `skill_id`),
    KEY `idx_activity_required_skills_skill` (`skill_id`),
    CONSTRAINT `fk_activity_required_skills_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_activity_required_skills_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kỹ năng yêu cầu chung của hoạt động';

CREATE TABLE IF NOT EXISTS `activity_attachments` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `activity_id`   INT UNSIGNED NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `file_path`     VARCHAR(500) NOT NULL,
    `mime_type`     VARCHAR(100) NOT NULL,
    `file_size`     INT UNSIGNED NOT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_activity_attachments_activity` (`activity_id`),
    CONSTRAINT `fk_activity_attachments_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tài liệu đính kèm hoạt động';

-- ===========================================
-- BẢNG 9: shifts - Ca trực
-- ===========================================
CREATE TABLE IF NOT EXISTS `shifts` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `activity_id`  INT UNSIGNED NOT NULL,
    `name`         VARCHAR(200) NOT NULL COMMENT 'Tên ca: Ca sáng, Ca chiều...',
    `description`  TEXT DEFAULT NULL,
    `date`         DATE NOT NULL,
    `time_start`   TIME NOT NULL,
    `time_end`     TIME NOT NULL,
    `min_volunteers` INT UNSIGNED NOT NULL DEFAULT 1,
    `max_volunteers` INT UNSIGNED NOT NULL DEFAULT 10,
    `location`     VARCHAR(500) DEFAULT NULL COMMENT 'Địa điểm riêng của ca (nếu khác HĐ)',
    `status`       ENUM('open','closed','full','cancelled') NOT NULL DEFAULT 'open',
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_shifts_activity` (`activity_id`),
    KEY `idx_shifts_date` (`date`),
    KEY `idx_shifts_status` (`status`),
    CONSTRAINT `fk_shifts_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_shift_times` CHECK (`time_end` > `time_start`),
    CONSTRAINT `chk_shift_volunteers` CHECK (`max_volunteers` >= `min_volunteers`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ca trực trong hoạt động';

-- ===========================================
-- BẢNG 10: shift_required_skills - Kỹ năng bắt buộc của ca
-- ===========================================
CREATE TABLE IF NOT EXISTS `shift_required_skills` (
    `shift_id`   INT UNSIGNED NOT NULL,
    `skill_id`   INT UNSIGNED NOT NULL,
    `is_mandatory` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=bắt buộc, 0=ưu tiên',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`shift_id`, `skill_id`),
    KEY `idx_srs_skill` (`skill_id`),
    CONSTRAINT `fk_srs_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_srs_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kỹ năng bắt buộc của ca trực';

-- ===========================================
-- BẢNG 11: registrations - Đăng ký tham gia ca
-- ===========================================
CREATE TABLE IF NOT EXISTS `registrations` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NOT NULL,
    `shift_id`    INT UNSIGNED NOT NULL,
    `status`      ENUM('pending','approved','rejected','cancelled','withdrawn')
                  NOT NULL DEFAULT 'pending',
    `note`        VARCHAR(500) DEFAULT NULL COMMENT 'Ghi chú khi đăng ký',
    `registered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reg_user_shift` (`user_id`, `shift_id`) COMMENT 'Chống đăng ký trùng',
    KEY `idx_reg_shift` (`shift_id`),
    KEY `idx_reg_status` (`status`),
    CONSTRAINT `fk_reg_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`  (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reg_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Đăng ký tham gia ca trực';

-- ===========================================
-- BẢNG 12: assignment_runs - Phiên chạy thuật toán
-- ===========================================
CREATE TABLE IF NOT EXISTS `assignment_runs` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `activity_id`     INT UNSIGNED NOT NULL,
    `run_by`          INT UNSIGNED NOT NULL COMMENT 'Điều phối viên chạy',
    `algorithm`       ENUM('baseline_greedy','improved_greedy') NOT NULL,
    `status`          ENUM('running','preview','confirmed','cancelled') NOT NULL DEFAULT 'running',
    `input_snapshot`  JSON DEFAULT NULL COMMENT 'Ảnh chụp đầu vào (JSON)',
    `config`          JSON DEFAULT NULL COMMENT 'Cấu hình thuật toán',
    `result_summary`  JSON DEFAULT NULL COMMENT 'Tóm tắt kết quả',
    `duration_ms`     INT UNSIGNED DEFAULT NULL COMMENT 'Thời gian chạy ms',
    `confirmed_at`    DATETIME DEFAULT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ar_activity` (`activity_id`),
    KEY `idx_ar_run_by` (`run_by`),
    KEY `idx_ar_algorithm` (`algorithm`),
    CONSTRAINT `fk_ar_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`),
    CONSTRAINT `fk_ar_run_by` FOREIGN KEY (`run_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Phiên chạy thuật toán phân công';

-- ===========================================
-- BẢNG 13: assignments - Kết quả phân công
-- ===========================================
CREATE TABLE IF NOT EXISTS `assignments` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `run_id`      INT UNSIGNED DEFAULT NULL COMMENT 'Phiên chạy thuật toán (NULL nếu thủ công)',
    `shift_id`    INT UNSIGNED NOT NULL,
    `user_id`     INT UNSIGNED NOT NULL,
    `status`      ENUM('assigned','confirmed','absent','replaced','removed')
                  NOT NULL DEFAULT 'assigned',
    `assigned_by` INT UNSIGNED DEFAULT NULL COMMENT 'Ai phân công (NULL nếu thuật toán)',
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `note`        VARCHAR(500) DEFAULT NULL,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_assign_shift_user` (`shift_id`, `user_id`),
    KEY `idx_assign_run` (`run_id`),
    KEY `idx_assign_user` (`user_id`),
    KEY `idx_assign_status` (`status`),
    CONSTRAINT `fk_assign_run`   FOREIGN KEY (`run_id`)      REFERENCES `assignment_runs` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_assign_shift` FOREIGN KEY (`shift_id`)    REFERENCES `shifts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_assign_user`  FOREIGN KEY (`user_id`)     REFERENCES `users`  (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_assign_by`    FOREIGN KEY (`assigned_by`) REFERENCES `users`  (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Phân công tình nguyện viên vào ca';

-- ===========================================
-- BẢNG 14: assignment_run_items - Chi tiết kết quả từng ca
-- ===========================================
CREATE TABLE IF NOT EXISTS `assignment_run_items` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `run_id`          INT UNSIGNED NOT NULL,
    `shift_id`        INT UNSIGNED NOT NULL,
    `assigned_count`  INT UNSIGNED NOT NULL DEFAULT 0,
    `required_min`    INT UNSIGNED NOT NULL DEFAULT 0,
    `is_sufficient`   TINYINT(1) NOT NULL DEFAULT 0,
    `shortage`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số người còn thiếu',
    `reason`          TEXT DEFAULT NULL COMMENT 'Lý do thiếu người',
    `assigned_users`  JSON DEFAULT NULL COMMENT 'Danh sách user_id được phân công',
    PRIMARY KEY (`id`),
    KEY `idx_ari_run` (`run_id`),
    KEY `idx_ari_shift` (`shift_id`),
    CONSTRAINT `fk_ari_run`   FOREIGN KEY (`run_id`)   REFERENCES `assignment_runs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ari_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chi tiết kết quả phân công từng ca';

-- ===========================================
-- BẢNG 15: attendance - Xác nhận tham gia
-- ===========================================
CREATE TABLE IF NOT EXISTS `attendance` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `assignment_id` INT UNSIGNED NOT NULL,
    `user_id`      INT UNSIGNED NOT NULL,
    `shift_id`     INT UNSIGNED NOT NULL,
    `status`       ENUM('pending','confirmed','absent') NOT NULL DEFAULT 'pending',
    `confirmed_at` DATETIME DEFAULT NULL,
    `hours_worked` DECIMAL(5,2) DEFAULT NULL COMMENT 'Số giờ thực tế làm việc',
    `note`         VARCHAR(500) DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_att_assignment` (`assignment_id`),
    KEY `idx_att_user` (`user_id`),
    KEY `idx_att_shift` (`shift_id`),
    CONSTRAINT `fk_att_assign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_att_user`   FOREIGN KEY (`user_id`)       REFERENCES `users`  (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_att_shift`  FOREIGN KEY (`shift_id`)      REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Điểm danh/xác nhận tham gia';

-- ===========================================
-- BẢNG 16: replacement_requests - Yêu cầu báo vắng và thay người
-- ===========================================
CREATE TABLE IF NOT EXISTS `replacement_requests` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `assignment_id`   INT UNSIGNED NOT NULL COMMENT 'Phân công cần thay',
    `requester_id`    INT UNSIGNED NOT NULL COMMENT 'Người báo vắng',
    `replacement_id`  INT UNSIGNED DEFAULT NULL COMMENT 'Người thay thế',
    `reason`          TEXT NOT NULL COMMENT 'Lý do vắng mặt',
    `status`          ENUM('pending','approved','rejected','cancelled')
                      NOT NULL DEFAULT 'pending',
    `coordinator_note` TEXT DEFAULT NULL COMMENT 'Ghi chú của điều phối viên',
    `reviewed_by`     INT UNSIGNED DEFAULT NULL COMMENT 'Điều phối viên xét duyệt',
    `reviewed_at`     DATETIME DEFAULT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rr_assignment` (`assignment_id`),
    KEY `idx_rr_requester` (`requester_id`),
    KEY `idx_rr_status` (`status`),
    CONSTRAINT `fk_rr_assign`  FOREIGN KEY (`assignment_id`)  REFERENCES `assignments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rr_request` FOREIGN KEY (`requester_id`)   REFERENCES `users` (`id`),
    CONSTRAINT `fk_rr_replace` FOREIGN KEY (`replacement_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rr_review`  FOREIGN KEY (`reviewed_by`)    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Yêu cầu báo vắng và xin thay người';

-- ===========================================
-- BẢNG 17: notifications - Thông báo
-- ===========================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NOT NULL,
    `type`        VARCHAR(100) NOT NULL COMMENT 'assignment, replacement, reminder, system...',
    `title`       VARCHAR(300) NOT NULL,
    `message`     TEXT NOT NULL,
    `link`        VARCHAR(500) DEFAULT NULL COMMENT 'URL liên quan',
    `is_read`     TINYINT(1) NOT NULL DEFAULT 0,
    `read_at`     DATETIME DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notif_user_read` (`user_id`, `is_read`),
    KEY `idx_notif_type` (`type`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Thông báo hệ thống';

-- ===========================================
-- BẢNG 18: audit_logs - Nhật ký thao tác
-- ===========================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED DEFAULT NULL COMMENT 'NULL nếu thao tác hệ thống',
    `action`      VARCHAR(200) NOT NULL COMMENT 'login, create_activity, run_algorithm...',
    `entity_type` VARCHAR(100) DEFAULT NULL COMMENT 'activity, shift, assignment...',
    `entity_id`   INT UNSIGNED DEFAULT NULL,
    `old_data`    JSON DEFAULT NULL COMMENT 'Dữ liệu trước khi thay đổi',
    `new_data`    JSON DEFAULT NULL COMMENT 'Dữ liệu sau khi thay đổi',
    `ip_address`  VARCHAR(45) DEFAULT NULL,
    `user_agent`  VARCHAR(500) DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_al_user` (`user_id`),
    KEY `idx_al_action` (`action`),
    KEY `idx_al_entity` (`entity_type`, `entity_id`),
    KEY `idx_al_created` (`created_at`),
    CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nhật ký thao tác quan trọng';

-- ===========================================
-- BẢNG 19: assignment_history - Lịch sử phân công thủ công
-- ===========================================
CREATE TABLE IF NOT EXISTS `assignment_history` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `assignment_id` INT UNSIGNED NOT NULL,
    `changed_by`   INT UNSIGNED NOT NULL,
    `action`       ENUM('add','remove','transfer','status_change') NOT NULL,
    `old_data`     JSON DEFAULT NULL,
    `new_data`     JSON DEFAULT NULL,
    `note`         VARCHAR(500) DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ah_assignment` (`assignment_id`),
    KEY `idx_ah_changed_by` (`changed_by`),
    CONSTRAINT `fk_ah_assign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ah_user`   FOREIGN KEY (`changed_by`)    REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử chỉnh sửa phân công';

SET FOREIGN_KEY_CHECKS = 1;
