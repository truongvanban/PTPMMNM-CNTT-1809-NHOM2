ALTER TABLE users
    ADD COLUMN total_points INT NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN current_tier VARCHAR(20) NOT NULL DEFAULT 'bronze' AFTER total_points;

ALTER TABLE activities
    ADD COLUMN points_multiplier DECIMAL(4,2) NOT NULL DEFAULT 1.00 AFTER max_volunteers;

ALTER TABLE attendance
    MODIFY COLUMN status ENUM('pending','confirmed','absent','completed') NOT NULL DEFAULT 'pending';

CREATE TABLE IF NOT EXISTS point_transactions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    points INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    reference_id INT UNSIGNED DEFAULT NULL,
    description VARCHAR(500) NOT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_point_event (user_id, type, reference_id),
    KEY idx_point_user_created (user_id, created_at),
    KEY idx_point_type_created (type, created_at),
    CONSTRAINT fk_point_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_point_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS badges (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    icon VARCHAR(100) NOT NULL DEFAULT 'bi-award-fill',
    required_points INT UNSIGNED NOT NULL DEFAULT 0,
    description VARCHAR(500) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_badges_name (name),
    KEY idx_badges_required_points (required_points)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gamification_settings (
    setting_key VARCHAR(60) NOT NULL,
    setting_value DECIMAL(8,2) NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academic_terms (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_terms_dates (starts_on, ends_on),
    CONSTRAINT fk_terms_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_term_dates CHECK (ends_on >= starts_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO gamification_settings (setting_key, setting_value) VALUES
    ('points_per_hour', 1),
    ('difficult_shift_multiplier', 1.5),
    ('skilled_shift_multiplier', 2),
    ('on_time_bonus', 5),
    ('late_cancel_penalty', 10),
    ('no_show_penalty', 20),
    ('daily_points_cap', 30),
    ('certificate_threshold', 500);

INSERT IGNORE INTO badges (name, icon, required_points, description) VALUES
    ('Tân binh', 'bi-seedling', 0, 'Bắt đầu hành trình tình nguyện'),
    ('Nhiệt huyết', 'bi-fire', 101, 'Tích lũy từ 101 điểm cống hiến'),
    ('Truyền cảm hứng', 'bi-star-fill', 501, 'Tích lũy từ 501 điểm cống hiến'),
    ('Đại sứ xanh', 'bi-award-fill', 1501, 'Tích lũy từ 1501 điểm cống hiến');
