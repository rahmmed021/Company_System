SET NAMES utf8mb4;

INSERT INTO roles (id, name, label_en, label_bn) VALUES
(1, 'admin', 'Admin', 'অ্যাডমিন'),
(2, 'foreman', 'Foreman', 'ফোরম্যান'),
(3, 'labor', 'Labor', 'শ্রমিক');

INSERT INTO permissions (name, label_en, label_bn) VALUES
('manage_all', 'Manage entire system', 'পুরো সিস্টেম পরিচালনা'),
('manage_project_workforce', 'Manage assigned project workforce', 'নির্ধারিত প্রকল্প কর্মী পরিচালনা'),
('view_own_profile', 'View own profile', 'নিজের প্রোফাইল দেখা'),
('view_own_salary', 'View own salary', 'নিজের বেতন দেখা'),
('apply_leave', 'Apply for leave', 'ছুটির আবেদন');

INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions
UNION SELECT 2, id FROM permissions WHERE name IN ('manage_project_workforce','view_own_profile','view_own_salary','apply_leave')
UNION SELECT 3, id FROM permissions WHERE name IN ('view_own_profile','view_own_salary','apply_leave');

INSERT INTO users (id, name, email, mobile, password_hash, role, status, language, theme) VALUES
(1, 'Demo Admin', 'admin@example.com', '01700000000', 'seed$9a4aabf0e5cf71cae2cea646613ce7e2a5919fa758e56819704be25a3a2c1f0b', 'admin', 'active', 'bn', 'light');

INSERT INTO workers (id, id_number, full_name, full_name_bn, age, father_name, nid, address, blood_group, mobile, joining_date, skill, daily_salary, role, status, created_by) VALUES
(1, 'NEP202501050001', 'Demo Foreman', 'ডেমো ফোরম্যান', 38, 'Abdul Karim', '1988000000001', 'Dhaka, Bangladesh', 'O+', '01800000000', '2025-01-05', 'Steel fabrication', 1000.00, 'foreman', 'active', 1),
(2, 'NEP202502100001', 'Demo Labor', 'ডেমো শ্রমিক', 27, 'Md Rahim', '1997000000002', 'Gazipur, Bangladesh', 'B+', '01900000000', '2025-02-10', 'Welding helper', 800.00, 'helper', 'active', 1);

INSERT INTO users (name, email, mobile, password_hash, role, status, language, theme, worker_id) VALUES
('Demo Foreman', NULL, '01800000000', 'seed$9a4aabf0e5cf71cae2cea646613ce7e2a5919fa758e56819704be25a3a2c1f0b', 'foreman', 'active', 'bn', 'light', 1),
('Demo Labor', NULL, '01900000000', 'seed$9a4aabf0e5cf71cae2cea646613ce7e2a5919fa758e56819704be25a3a2c1f0b', 'labor', 'active', 'bn', 'light', 2);

INSERT INTO projects (id, name_en, name_bn, client_name, client_mobile, location, work_type_en, work_type_bn, start_date, expected_end_date, total_amount, description_en, description_bn, status, created_by) VALUES
(1, 'Factory Shed Fabrication', 'ফ্যাক্টরি শেড ফ্যাব্রিকেশন', 'ABC Manufacturing Ltd.', '01611111111', 'Narayanganj', 'Steel shed', 'স্টিল শেড', '2026-01-01', '2026-04-30', 1000000.00, 'Steel fabrication and installation project.', 'স্টিল ফ্যাব্রিকেশন এবং ইনস্টলেশন প্রকল্প।', 'running', 1),
(2, 'Office Grill Installation', 'অফিস গ্রিল ইনস্টলেশন', 'MNO Trading', '01622222222', 'Dhaka', 'Grill work', 'গ্রিল কাজ', '2026-02-15', '2026-03-20', 250000.00, 'Window and stair grill installation.', 'জানালা ও সিঁড়ির গ্রিল ইনস্টলেশন।', 'completed', 1);

INSERT INTO worker_projects (worker_id, project_id, start_date, status, notes, created_by) VALUES
(1, 1, '2026-01-01', 'active', 'Responsible for daily workforce attendance.', 1),
(2, 1, '2026-01-02', 'active', 'Assigned as welding helper.', 1);

INSERT INTO expense_categories (name_en, name_bn, type) VALUES
('Raw Materials', 'কাঁচামাল', 'raw_materials'),
('Food', 'খাবার', 'food'),
('Car/Vehicle Rental', 'গাড়ি ভাড়া', 'vehicle'),
('Transportation', 'পরিবহন', 'transportation'),
('Fuel', 'জ্বালানি', 'fuel'),
('Tools', 'সরঞ্জাম', 'tools'),
('Maintenance', 'রক্ষণাবেক্ষণ', 'maintenance'),
('Accommodation', 'থাকা', 'accommodation'),
('Other', 'অন্যান্য', 'other');

INSERT INTO materials (name_en, name_bn, unit) VALUES
('Steel', 'স্টিল', 'kg'),
('MS Sheet', 'এমএস শিট', 'sheet'),
('Welding Rod', 'ওয়েল্ডিং রড', 'box'),
('Paint', 'রং', 'liter'),
('Cutting Disc', 'কাটিং ডিস্ক', 'pcs');

INSERT INTO attendance (attendance_date, project_id, worker_id, status, daily_salary, overtime_hours, overtime_amount, total_salary, notes, entered_by, created_by) VALUES
('2026-03-01', 1, 2, 'present', 800.00, 2.00, 200.00, 1000.00, 'Demo attendance with overtime.', 1, 1);

INSERT INTO salary_transactions (worker_id, project_id, attendance_id, transaction_date, type, amount, overtime_amount, description, created_by) VALUES
(2, 1, 1, '2026-03-01', 'salary', 1000.00, 200.00, 'Attendance salary', 1);

INSERT INTO advances (worker_id, date, amount, project_id, reason, notes, status, created_by) VALUES
(2, '2026-03-05', 1000.00, 1, 'Family emergency', 'Demo advance', 'approved', 1);

INSERT INTO withdrawals (worker_id, date, amount, project_id, payment_method, reference, notes, status, created_by) VALUES
(2, '2026-03-10', 500.00, 1, 'cash', 'WD-001', 'Demo withdrawal', 'paid', 1);

INSERT INTO bonuses (worker_id, date, project_id, amount, description, status, created_by) VALUES
(2, '2026-03-12', 1, 300.00, 'Good performance bonus', 'approved', 1);

INSERT INTO deductions (worker_id, date, project_id, amount, description, status, created_by) VALUES
(2, '2026-03-13', 1, 100.00, 'Tool damage deduction', 'active', 1);

INSERT INTO leave_applications (worker_id, leave_type, start_date, end_date, reason, application_date, status, admin_note, created_by) VALUES
(2, 'casual', '2026-03-20', '2026-03-21', 'Personal work', '2026-03-15', 'pending', NULL, 3);

INSERT INTO expenses (expense_date, project_id, category_id, description_en, description_bn, amount, vendor, invoice_number, payment_method, notes, status, created_by) VALUES
('2026-03-03', 1, 6, 'Tool purchase', 'সরঞ্জাম ক্রয়', 3500.00, 'Local Hardware', 'INV-1001', 'cash', 'Hammer and drill bit', 'approved', 1);

INSERT INTO material_purchases (project_id, material, quantity, unit, unit_price, carrying_cost, total_amount, supplier, invoice_number, purchase_date, created_by) VALUES
(1, 'Steel', 500.00, 'kg', 95.00, 1500.00, 49000.00, 'Dhaka Steel', 'MAT-001', '2026-03-02', 1);

INSERT INTO food_expenses (project_id, food_item, quantity, unit_price, carrying_cost, total_cost, total_amount, description, expense_date, created_by) VALUES
(1, 'Lunch', 0.00, 0.00, 200.00, 1200.00, 1400.00, 'Worker lunch', '2026-03-04', 1);

INSERT INTO vehicle_expenses (project_id, vehicle_type, driver_name, rental_amount, fuel_amount, other_cost, total_amount, notes, expense_date, created_by) VALUES
(1, 'Pickup', 'Kamal', 5000.00, 1200.00, 300.00, 6500.00, 'Material transport', '2026-03-05', 1);

INSERT INTO received_payments (project_id, client_name, contract_amount, receivable_amount, received_amount, payment_date, payment_method, cheque_number, bank_name, transaction_reference, notes, status, created_by) VALUES
(1, 'ABC Manufacturing Ltd.', 1000000.00, 700000.00, 300000.00, '2026-03-07', 'bank', NULL, 'Demo Bank', 'TXN-001', 'First installment', 'received', 1),
(1, 'ABC Manufacturing Ltd.', 1000000.00, 500000.00, 200000.00, '2026-03-20', 'cheque', 'CHQ-002', 'Demo Bank', 'TXN-002', 'Second installment', 'received', 1);

INSERT INTO admin_personal_expenses (expense_date, category, description, amount, payment_method, reference, notes, status, created_by) VALUES
('2026-03-06', 'Travel', 'Personal city travel', 1500.00, 'cash', 'PEX-001', 'Separate admin personal expense', 'active', 1);

INSERT INTO equipment (id, name_en, name_bn, category, quantity, available_quantity, assigned_quantity, damaged_quantity, cancelled_quantity, purchase_date, purchase_price, condition_status, location, notes, created_by) VALUES
(1, 'Welding Machine', 'ওয়েল্ডিং মেশিন', 'Machine', 10, 7, 3, 0, 0, '2025-12-01', 25000.00, 'available', 'Main Store', 'Demo inventory item', 1),
(2, 'Grinding Machine', 'গ্রাইন্ডিং মেশিন', 'Machine', 5, 5, 0, 0, 0, '2025-12-05', 9000.00, 'available', 'Main Store', 'Demo inventory item', 1);

INSERT INTO equipment_assignments (equipment_id, project_id, quantity, issue_date, expected_return_date, condition_before, status, notes, created_by) VALUES
(1, 1, 3, '2026-03-01', '2026-04-01', 'Good', 'assigned', 'Assigned to Factory Shed project', 1);

INSERT INTO equipment_movements (equipment_id, project_id, movement_type, quantity, movement_date, notes, created_by) VALUES
(1, 1, 'assigned', 3, '2026-03-01', 'Initial assignment', 1);

INSERT INTO id_cards (worker_id, id_number, designation, mobile, photo_path, notes, status, created_by) VALUES
(1, 'NEP202501050001', 'foreman', '01800000000', NULL, 'Demo foreman ID card', 'active', 1),
(2, 'NEP202502100001', 'helper', '01900000000', NULL, 'Demo labor ID card', 'active', 1);

INSERT INTO homepage_sections (section_key, title_en, title_bn, body_en, body_bn, image_path, sort_order, created_by) VALUES
('hero', 'Naoshin Enterprise', 'নওসিন এন্টারপ্রাইজ', 'Professional contracting, fabrication, workforce and project delivery services.', 'পেশাদার কন্ট্রাক্টিং, ফ্যাব্রিকেশন, কর্মী ব্যবস্থাপনা ও প্রকল্প বাস্তবায়ন সেবা।', 'assets/images/nousin-logo.svg', 1, 1),
('about', 'About Naoshin Enterprise', 'নওসিন এন্টারপ্রাইজ সম্পর্কে', 'Naoshin Enterprise manages skilled foremen, labour teams, project expenses, equipment and client delivery with accountability.', 'নওসিন এন্টারপ্রাইজ দক্ষ ফোরম্যান, শ্রমিক দল, প্রকল্প ব্যয়, ইকুইপমেন্ট ও ক্লায়েন্ট ডেলিভারি দায়িত্বশীলভাবে পরিচালনা করে।', 'assets/images/nousin-logo.svg', 2, 1),
('contact', 'Contact Naoshin Enterprise', 'যোগাযোগ', 'Dhaka, Bangladesh | 01700000000 | admin@example.com', 'ঢাকা, বাংলাদেশ | 01700000000 | admin@example.com', 'assets/images/nousin-logo.svg', 3, 1);

INSERT INTO homepage_updates (title_en, title_bn, body_en, body_bn, published_at, image_path, created_by) VALUES
('ERP launched for project control', 'প্রকল্প নিয়ন্ত্রণের জন্য ইআরপি চালু', 'Naoshin Enterprise now tracks workforce, payments, expenses and equipment in one secure system.', 'নওসিন এন্টারপ্রাইজ এখন কর্মী, পেমেন্ট, খরচ ও ইকুইপমেন্ট এক নিরাপদ সিস্টেমে ট্র্যাক করে।', NOW(), 'assets/images/nousin-logo.svg', 1);

INSERT INTO homepage_services (title_en, title_bn, body_en, body_bn, icon, sort_order, created_by) VALUES
('Steel Fabrication', 'স্টিল ফ্যাব্রিকেশন', 'Shed, grill, structure and welding works.', 'শেড, গ্রিল, স্ট্রাকচার ও ওয়েল্ডিং কাজ।', 'fa-screwdriver-wrench', 1, 1),
('Project Workforce', 'প্রকল্প কর্মী', 'Foreman and labour team deployment.', 'ফোরম্যান ও শ্রমিক দল নিয়োগ।', 'fa-people-group', 2, 1),
('Equipment Support', 'ইকুইপমেন্ট সাপোর্ট', 'Tools and equipment assignment for projects.', 'প্রকল্পের জন্য সরঞ্জাম ও ইকুইপমেন্ট বরাদ্দ।', 'fa-toolbox', 3, 1);

INSERT INTO homepage_media (media_type, title_en, title_bn, media_path, sort_order, created_by) VALUES
('photo', 'Company Logo', 'কোম্পানি লোগো', 'assets/images/nousin-logo.svg', 1, 1);

INSERT INTO notifications (user_id, title_key, body_key, type, is_read) VALUES
(1, 'notifications.attendance_title', 'notifications.attendance_body', 'success', 0),
(1, 'notifications.payment_title', 'notifications.payment_body', 'success', 0);

INSERT INTO settings (setting_key, setting_value, setting_group) VALUES
('company_name', 'Naoshin Enterprise', 'general'),
('company_logo', 'assets/images/nousin-logo.svg', 'general'),
('company_address', 'Dhaka, Bangladesh', 'general'),
('company_mobile', '01700000000', 'general'),
('company_email', 'admin@example.com', 'general'),
('currency', 'BDT', 'finance'),
('date_format', 'Y-m-d', 'general'),
('timezone', 'Asia/Dhaka', 'general'),
('default_language', 'bn', 'language'),
('theme', 'light', 'ui'),
('overtime_divisor', '8', 'salary');
