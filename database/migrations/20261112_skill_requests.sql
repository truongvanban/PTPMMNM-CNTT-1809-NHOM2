CREATE TABLE IF NOT EXISTS `skill_requests` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NOT NULL,
    `skill_name`  VARCHAR(150) NOT NULL,
    `proof`       TEXT NOT NULL,
    `status`      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_note`  TEXT DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_skill_requests_user_status` (`user_id`, `status`),
    KEY `idx_skill_requests_status_created` (`status`, `created_at`),
    CONSTRAINT `fk_skill_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Yêu cầu bổ sung kỹ năng của tình nguyện viên';
