SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS backups;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS login_history;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS homepage_media;
DROP TABLE IF EXISTS homepage_services;
DROP TABLE IF EXISTS homepage_updates;
DROP TABLE IF EXISTS homepage_sections;
DROP TABLE IF EXISTS id_cards;
DROP TABLE IF EXISTS equipment_movements;
DROP TABLE IF EXISTS equipment_assignments;
DROP TABLE IF EXISTS equipment;
DROP TABLE IF EXISTS admin_personal_expenses;
DROP TABLE IF EXISTS received_payments;
DROP TABLE IF EXISTS vehicle_expenses;
DROP TABLE IF EXISTS food_expenses;
DROP TABLE IF EXISTS material_purchases;
DROP TABLE IF EXISTS materials;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS expense_categories;
DROP TABLE IF EXISTS leave_applications;
DROP TABLE IF EXISTS deductions;
DROP TABLE IF EXISTS bonuses;
DROP TABLE IF EXISTS withdrawals;
DROP TABLE IF EXISTS advances;
DROP TABLE IF EXISTS salary_transactions;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS worker_projects;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS workers;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  label_en VARCHAR(100) NOT NULL,
  label_bn VARCHAR(100) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  label_en VARCHAR(160) NOT NULL,
  label_bn VARCHAR(160) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
  role_id INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  worker_id INT UNSIGNED NULL,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(160) NULL UNIQUE,
  mobile VARCHAR(30) NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','foreman','labor') NOT NULL DEFAULT 'labor',
  status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  language ENUM('en','bn') NOT NULL DEFAULT 'bn',
  theme ENUM('light','dark') NOT NULL DEFAULT 'light',
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE workers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_number VARCHAR(80) NULL UNIQUE,
  photo_path VARCHAR(255) NULL,
  full_name VARCHAR(160) NOT NULL,
  full_name_bn VARCHAR(160) NULL,
  age INT UNSIGNED NULL,
  father_name VARCHAR(160) NULL,
  nid VARCHAR(40) NULL UNIQUE,
  date_of_birth DATE NULL,
  address TEXT NULL,
  blood_group ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NULL,
  mobile VARCHAR(30) NOT NULL UNIQUE,
  joining_date DATE NULL,
  skill VARCHAR(160) NULL,
  daily_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  role ENUM('foreman','labor','supervisor','mistry','helper','welder','engineer') NOT NULL,
  status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_workers_role_status (role, status),
  INDEX idx_workers_search (id_number, full_name, mobile, joining_date),
  CONSTRAINT fk_workers_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users
  ADD CONSTRAINT fk_users_worker FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE SET NULL;

CREATE TABLE projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name_en VARCHAR(190) NOT NULL,
  name_bn VARCHAR(190) NULL,
  client_name VARCHAR(190) NOT NULL,
  client_mobile VARCHAR(40) NULL,
  location VARCHAR(255) NULL,
  work_type_en VARCHAR(190) NULL,
  work_type_bn VARCHAR(190) NULL,
  start_date DATE NULL,
  expected_end_date DATE NULL,
  total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  description_en TEXT NULL,
  description_bn TEXT NULL,
  project_attachment_path VARCHAR(255) NULL,
  project_attachment_name VARCHAR(255) NULL,
  status ENUM('planning','running','completed','cancelled') NOT NULL DEFAULT 'planning',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_projects_status_dates (status, start_date, expected_end_date),
  INDEX idx_projects_search (name_en, client_mobile),
  CONSTRAINT fk_projects_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE worker_projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  worker_id INT UNSIGNED NOT NULL,
  project_id INT UNSIGNED NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NULL,
  status ENUM('active','completed','paused') NOT NULL DEFAULT 'active',
  notes TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_worker_projects_worker (worker_id, status),
  INDEX idx_worker_projects_project (project_id, status),
  CONSTRAINT fk_worker_projects_worker FOREIGN KEY (worker_id) REFERENCES workers(id),
  CONSTRAINT fk_worker_projects_project FOREIGN KEY (project_id) REFERENCES projects(id),
  CONSTRAINT fk_worker_projects_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendance (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attendance_date DATE NOT NULL,
  project_id INT UNSIGNED NOT NULL,
  worker_id INT UNSIGNED NOT NULL,
  status ENUM('present','absent','half_day','leave','holiday') NOT NULL,
  daily_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  overtime_hours DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  overtime_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  notes TEXT NULL,
  entered_by INT UNSIGNED NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_attendance_worker_date_project (attendance_date, worker_id, project_id),
  INDEX idx_attendance_project_date (project_id, attendance_date),
  CONSTRAINT fk_attendance_project FOREIGN KEY (project_id) REFERENCES projects(id),
  CONSTRAINT fk_attendance_worker FOREIGN KEY (worker_id) REFERENCES workers(id),
  CONSTRAINT fk_attendance_entered_by FOREIGN KEY (entered_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_attendance_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE salary_transactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  worker_id INT UNSIGNED NOT NULL,
  project_id INT UNSIGNED NULL,
  attendance_id INT UNSIGNED NULL,
  transaction_date DATE NOT NULL,
  type ENUM('salary','bonus','deduction','adjustment') NOT NULL DEFAULT 'salary',
  amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  overtime_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  description TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_salary_worker_date (worker_id, transaction_date),
  CONSTRAINT fk_salary_worker FOREIGN KEY (worker_id) REFERENCES workers(id),
  CONSTRAINT fk_salary_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  CONSTRAINT fk_salary_attendance FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE SET NULL,
  CONSTRAINT fk_salary_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE advances (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  worker_id INT UNSIGNED NOT NULL,
  date DATE NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  project_id INT UNSIGNED NULL,
  reason TEXT NULL,
  notes TEXT NULL,
  status ENUM('approved','void') NOT NULL DEFAULT 'approved',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_advances_worker FOREIGN KEY (worker_id) REFERENCES workers(id),
  CONSTRAINT fk_advances_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  CONSTRAINT fk_advances_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE withdrawals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  worker_id INT UNSIGNED NOT NULL,
  date DATE NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  project_id INT UNSIGNED NULL,
  payment_method ENUM('cash','bank','mobile_banking','other') NOT NULL DEFAULT 'cash',
  reference VARCHAR(160) NULL,
  notes TEXT NULL,
  status ENUM('paid','void') NOT NULL DEFAULT 'paid',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_withdrawals_worker FOREIGN KEY (worker_id) REFERENCES workers(id),
  CONSTRAINT fk_withdrawals_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  CONSTRAINT fk_withdrawals_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bonuses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  worker_id INT UNSIGNED NOT NULL,
  date DATE NOT NULL,
  project_id INT UNSIGNED NULL,
  amount DECIMAL(12,2) NOT NULL,
  description TEXT NULL,
  status ENUM('approved','void') NOT NULL DEFAULT 'approved',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_bonuses_worker FOREIGN KEY (worker_id) REFERENCES workers(id),
  CONSTRAINT fk_bonuses_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  CONSTRAINT fk_bonuses_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE deductions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  worker_id INT UNSIGNED NOT NULL,
  date DATE NOT NULL,
  project_id INT UNSIGNED NULL,
  amount DECIMAL(12,2) NOT NULL,
  description TEXT NULL,
  status ENUM('active','void') NOT NULL DEFAULT 'active',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_deductions_worker FOREIGN KEY (worker_id) REFERENCES workers(id),
  CONSTRAINT fk_deductions_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  CONSTRAINT fk_deductions_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leave_applications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  worker_id INT UNSIGNED NOT NULL,
  leave_type ENUM('sick','casual','emergency','annual') NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  reason TEXT NOT NULL,
  application_date DATE NOT NULL DEFAULT (CURRENT_DATE),
  status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  admin_note TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_leave_worker_status (worker_id, status),
  CONSTRAINT fk_leave_worker FOREIGN KEY (worker_id) REFERENCES workers(id),
  CONSTRAINT fk_leave_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE expense_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name_en VARCHAR(120) NOT NULL,
  name_bn VARCHAR(120) NULL,
  type ENUM('raw_materials','food','vehicle','transportation','fuel','tools','maintenance','accommodation','other') NOT NULL DEFAULT 'other',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE expenses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  expense_date DATE NOT NULL,
  project_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  description_en TEXT NULL,
  description_bn TEXT NULL,
  amount DECIMAL(12,2) NOT NULL,
  vendor VARCHAR(160) NULL,
  invoice_number VARCHAR(120) NULL,
  invoice_image VARCHAR(255) NULL,
  payment_method ENUM('cash','bank','mobile_banking','other') NOT NULL DEFAULT 'cash',
  notes TEXT NULL,
  status ENUM('submitted','approved','void') NOT NULL DEFAULT 'approved',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_expenses_project_date (project_id, expense_date),
  CONSTRAINT fk_expenses_project FOREIGN KEY (project_id) REFERENCES projects(id),
  CONSTRAINT fk_expenses_category FOREIGN KEY (category_id) REFERENCES expense_categories(id),
  CONSTRAINT fk_expenses_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE materials (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name_en VARCHAR(160) NOT NULL,
  name_bn VARCHAR(160) NULL,
  unit VARCHAR(40) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE material_purchases (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  material VARCHAR(160) NOT NULL,
  quantity DECIMAL(12,2) NOT NULL,
  unit VARCHAR(40) NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  carrying_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_amount DECIMAL(12,2) NOT NULL,
  supplier VARCHAR(160) NULL,
  invoice_number VARCHAR(120) NULL,
  invoice_image VARCHAR(255) NULL,
  purchase_date DATE NOT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_material_project FOREIGN KEY (project_id) REFERENCES projects(id),
  CONSTRAINT fk_material_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE food_expenses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  food_item VARCHAR(160) NOT NULL,
  quantity DECIMAL(12,2) NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  carrying_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_amount DECIMAL(12,2) NOT NULL,
  description TEXT NULL,
  expense_date DATE NOT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_food_project FOREIGN KEY (project_id) REFERENCES projects(id),
  CONSTRAINT fk_food_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vehicle_expenses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  vehicle_type VARCHAR(120) NULL,
  driver_name VARCHAR(120) NULL,
  rental_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  fuel_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  other_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  notes TEXT NULL,
  expense_date DATE NOT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_vehicle_project FOREIGN KEY (project_id) REFERENCES projects(id),
  CONSTRAINT fk_vehicle_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE received_payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  client_name VARCHAR(190) NULL,
  contract_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  receivable_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  received_amount DECIMAL(14,2) NOT NULL,
  payment_date DATE NOT NULL,
  payment_method ENUM('cash','bank','mobile_banking','cheque','other') NOT NULL DEFAULT 'cash',
  cheque_number VARCHAR(120) NULL,
  cheque_image VARCHAR(255) NULL,
  bank_name VARCHAR(160) NULL,
  transaction_reference VARCHAR(160) NULL,
  notes TEXT NULL,
  status ENUM('received','cancelled') NOT NULL DEFAULT 'received',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_received_project_date (project_id, payment_date),
  CONSTRAINT fk_received_project FOREIGN KEY (project_id) REFERENCES projects(id),
  CONSTRAINT fk_received_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_personal_expenses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  expense_date DATE NOT NULL,
  category VARCHAR(120) NOT NULL,
  description TEXT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_method ENUM('cash','bank','mobile_banking','other') NOT NULL DEFAULT 'cash',
  reference VARCHAR(160) NULL,
  notes TEXT NULL,
  status ENUM('active','void') NOT NULL DEFAULT 'active',
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_admin_expenses_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE equipment (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name_en VARCHAR(160) NOT NULL,
  name_bn VARCHAR(160) NULL,
  category VARCHAR(120) NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 0,
  available_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  assigned_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  damaged_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  cancelled_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  purchase_date DATE NULL,
  purchase_price DECIMAL(12,2) NULL,
  condition_status ENUM('available','assigned','damaged','under_repair','cancelled','lost') NOT NULL DEFAULT 'available',
  location VARCHAR(160) NULL,
  notes TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_equipment_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE equipment_assignments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  equipment_id INT UNSIGNED NOT NULL,
  project_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  issue_date DATE NOT NULL,
  expected_return_date DATE NULL,
  actual_return_date DATE NULL,
  condition_before VARCHAR(160) NULL,
  condition_after VARCHAR(160) NULL,
  status ENUM('assigned','returned','damaged','cancelled') NOT NULL DEFAULT 'assigned',
  notes TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_equipment_assignments_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(id),
  CONSTRAINT fk_equipment_assignments_project FOREIGN KEY (project_id) REFERENCES projects(id),
  CONSTRAINT fk_equipment_assignments_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE equipment_movements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  equipment_id INT UNSIGNED NOT NULL,
  project_id INT UNSIGNED NULL,
  movement_type ENUM('assigned','returned','damaged','cancelled','lost','disposed') NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  movement_date DATE NOT NULL,
  notes TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_equipment_movements_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(id),
  CONSTRAINT fk_equipment_movements_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
  CONSTRAINT fk_equipment_movements_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE id_cards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  worker_id INT UNSIGNED NOT NULL UNIQUE,
  id_number VARCHAR(80) NOT NULL UNIQUE,
  designation ENUM('foreman','labor','supervisor','mistry','helper','welder','engineer') NOT NULL,
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

CREATE TABLE homepage_sections (
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

CREATE TABLE homepage_updates (
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

CREATE TABLE homepage_services (
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

CREATE TABLE homepage_media (
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

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  title_key VARCHAR(160) NOT NULL,
  body_key VARCHAR(160) NOT NULL,
  destination_url VARCHAR(255) NULL,
  type ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notifications_user_read (user_id, is_read),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  module VARCHAR(120) NOT NULL,
  record_id INT UNSIGNED NULL,
  old_data JSON NULL,
  new_data JSON NULL,
  ip_address VARCHAR(60) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_module_action (module, action),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  login_identifier VARCHAR(160) NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  ip_address VARCHAR(60) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_user_created (user_id, created_at),
  CONSTRAINT fk_login_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  setting_group VARCHAR(80) NOT NULL DEFAULT 'general',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE backups (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  file_name VARCHAR(190) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_backups_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
