SET NAMES utf8mb4;

ALTER TABLE workers ADD COLUMN IF NOT EXISTS id_number VARCHAR(80) NULL UNIQUE AFTER id;
ALTER TABLE material_purchases ADD COLUMN IF NOT EXISTS carrying_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER unit_price;
ALTER TABLE material_purchases ADD COLUMN IF NOT EXISTS invoice_image VARCHAR(255) NULL AFTER invoice_number;
ALTER TABLE food_expenses ADD COLUMN IF NOT EXISTS carrying_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER unit_price;
ALTER TABLE food_expenses ADD COLUMN IF NOT EXISTS total_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER carrying_cost;

CREATE TABLE IF NOT EXISTS id_cards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  worker_id INT UNSIGNED NOT NULL UNIQUE,
  id_number VARCHAR(80) NOT NULL UNIQUE,
  designation ENUM('foreman','labor') NOT NULL,
  mobile VARCHAR(30) NOT NULL,
  photo_path VARCHAR(255) NULL,
  notes TEXT NULL,
  status ENUM('active','deleted') NOT NULL DEFAULT 'active',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_id_cards_worker FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE,
  CONSTRAINT fk_id_cards_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS homepage_sections (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_key ENUM('hero','about','contact') NOT NULL UNIQUE,
  title_en VARCHAR(190) NOT NULL,
  title_bn VARCHAR(190) NULL,
  body_en TEXT NULL,
  body_bn TEXT NULL,
  image_path VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_homepage_sections_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS homepage_updates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title_en VARCHAR(190) NOT NULL,
  title_bn VARCHAR(190) NULL,
  body_en TEXT NULL,
  body_bn TEXT NULL,
  published_at DATETIME NULL,
  image_path VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_homepage_updates_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS homepage_services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title_en VARCHAR(190) NOT NULL,
  title_bn VARCHAR(190) NULL,
  body_en TEXT NULL,
  body_bn TEXT NULL,
  icon VARCHAR(80) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_homepage_services_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS homepage_media (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  media_type ENUM('photo','video') NOT NULL,
  title_en VARCHAR(190) NOT NULL,
  title_bn VARCHAR(190) NULL,
  media_path VARCHAR(255) NOT NULL,
  thumbnail_path VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_homepage_media_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value, setting_group)
VALUES ('company_name', 'Nousin Enterprise', 'general')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO settings (setting_key, setting_value, setting_group)
VALUES ('company_logo', 'assets/images/nousin-logo.svg', 'general')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO settings (setting_key, setting_value, setting_group)
VALUES ('default_language', 'bn', 'language')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
