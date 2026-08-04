-- Roots Dental Hospital Management System
-- Database: roots_dental_hms

CREATE DATABASE IF NOT EXISTS `roots_dental_hms`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `roots_dental_hms`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS approval_requests;
DROP TABLE IF EXISTS purchase_items;
DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS inventory_transactions;
DROP TABLE IF EXISTS inventory_items;
DROP TABLE IF EXISTS inventory_categories;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bills;
DROP TABLE IF EXISTS patient_documents;
DROP TABLE IF EXISTS follow_ups;
DROP TABLE IF EXISTS prescription_items;
DROP TABLE IF EXISTS prescriptions;
DROP TABLE IF EXISTS medicine_masters;
DROP TABLE IF EXISTS treatment_sessions;
DROP TABLE IF EXISTS patient_treatment_plans;
DROP TABLE IF EXISTS treatment_masters;
DROP TABLE IF EXISTS dental_examinations;
DROP TABLE IF EXISTS patient_visits;
DROP TABLE IF EXISTS appointment_status_histories;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS patient_medical_histories;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS reference_doctors;
DROP TABLE IF EXISTS doctor_leaves;
DROP TABLE IF EXISTS doctor_schedules;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NULL,
  avatar VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  remember_token VARCHAR(255) NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  INDEX idx_users_active (is_active),
  INDEX idx_users_deleted (deleted_at)
) ENGINE=InnoDB;

CREATE TABLE roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE permissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  module VARCHAR(100) NOT NULL,
  action VARCHAR(50) NOT NULL,
  slug VARCHAR(150) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  INDEX idx_perm_module (module)
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
  role_id INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_roles (
  user_id INT UNSIGNED NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `value` TEXT NULL,
  group_name VARCHAR(50) NOT NULL DEFAULT 'general',
  created_at DATETIME NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  token VARCHAR(255) NOT NULL,
  created_at DATETIME NULL,
  INDEX idx_pr_email (email)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  module VARCHAR(100) NOT NULL,
  record_id INT UNSIGNED NULL,
  action VARCHAR(50) NOT NULL,
  old_values LONGTEXT NULL,
  new_values LONGTEXT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NULL,
  INDEX idx_audit_module (module),
  INDEX idx_audit_user (user_id),
  INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE doctors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  doctor_code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  mobile VARCHAR(20) NULL,
  email VARCHAR(150) NULL,
  qualification VARCHAR(255) NULL,
  specialization VARCHAR(255) NULL,
  registration_number VARCHAR(100) NULL,
  consultation_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  slot_duration INT UNSIGNED NOT NULL DEFAULT 30,
  calendar_color VARCHAR(20) NOT NULL DEFAULT '#00AEEF',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_doctors_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE doctor_schedules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  doctor_id INT UNSIGNED NOT NULL,
  day_of_week TINYINT UNSIGNED NOT NULL COMMENT '0=Sun..6=Sat',
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  break_start TIME NULL,
  break_end TIME NULL,
  slot_duration INT UNSIGNED NOT NULL DEFAULT 30,
  is_off TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
  UNIQUE KEY uq_doctor_day (doctor_id, day_of_week)
) ENGINE=InnoDB;

CREATE TABLE doctor_leaves (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  doctor_id INT UNSIGNED NOT NULL,
  leave_date DATE NOT NULL,
  start_time TIME NULL,
  end_time TIME NULL,
  reason VARCHAR(255) NULL,
  leave_type ENUM('full_day','partial','blocked') NOT NULL DEFAULT 'full_day',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
  INDEX idx_leave_date (doctor_id, leave_date)
) ENGINE=InnoDB;

CREATE TABLE reference_doctors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ref_code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  clinic_hospital VARCHAR(255) NULL,
  mobile VARCHAR(20) NULL,
  email VARCHAR(150) NULL,
  address TEXT NULL,
  specialization VARCHAR(255) NULL,
  remarks TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE patients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  profile_photo VARCHAR(255) NULL,
  mobile VARCHAR(20) NOT NULL,
  alternate_mobile VARCHAR(20) NULL,
  dob DATE NULL,
  age INT UNSIGNED NULL,
  gender ENUM('male','female','other') NULL,
  email VARCHAR(150) NULL,
  address TEXT NULL,
  city VARCHAR(100) NULL,
  state VARCHAR(100) NULL,
  pincode VARCHAR(20) NULL,
  blood_group VARCHAR(10) NULL,
  emergency_contact VARCHAR(150) NULL,
  reference_doctor_id INT UNSIGNED NULL,
  registration_date DATE NOT NULL,
  medical_history TEXT NULL,
  allergies TEXT NULL,
  existing_conditions TEXT NULL,
  current_medicines TEXT NULL,
  notes TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (reference_doctor_id) REFERENCES reference_doctors(id) ON DELETE SET NULL,
  INDEX idx_patients_mobile (mobile),
  INDEX idx_patients_name (name),
  INDEX idx_patients_code (patient_code),
  INDEX idx_patients_deleted (deleted_at)
) ENGINE=InnoDB;

CREATE TABLE patient_medical_histories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  recorded_at DATETIME NOT NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NULL,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  INDEX idx_pmh_patient (patient_id)
) ENGINE=InnoDB;

CREATE TABLE appointments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  appointment_code VARCHAR(50) NOT NULL UNIQUE,
  patient_id INT UNSIGNED NULL,
  doctor_id INT UNSIGNED NOT NULL,
  appointment_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  visit_reason VARCHAR(255) NULL,
  treatment_master_id INT UNSIGNED NULL,
  notes TEXT NULL,
  entry_type VARCHAR(30) NOT NULL DEFAULT 'appointment',
  status VARCHAR(30) NOT NULL DEFAULT 'scheduled',
  created_by INT UNSIGNED NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id),
  INDEX idx_appt_date (appointment_date),
  INDEX idx_appt_doctor_date (doctor_id, appointment_date),
  INDEX idx_appt_status (status),
  INDEX idx_appt_patient (patient_id),
  INDEX idx_appt_entry_type (entry_type),
  UNIQUE KEY uq_doctor_slot (doctor_id, appointment_date, start_time, deleted_at)
) ENGINE=InnoDB;

CREATE TABLE appointment_status_histories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT UNSIGNED NOT NULL,
  from_status VARCHAR(30) NULL,
  to_status VARCHAR(30) NOT NULL,
  changed_by INT UNSIGNED NULL,
  remarks VARCHAR(255) NULL,
  created_at DATETIME NULL,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
  INDEX idx_ash_appt (appointment_id)
) ENGINE=InnoDB;

CREATE TABLE patient_visits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visit_code VARCHAR(50) NOT NULL UNIQUE,
  patient_id INT UNSIGNED NOT NULL,
  appointment_id INT UNSIGNED NULL,
  doctor_id INT UNSIGNED NOT NULL,
  visit_date DATE NOT NULL,
  visit_time TIME NULL,
  chief_complaint TEXT NULL,
  symptoms TEXT NULL,
  clinical_examination TEXT NULL,
  diagnosis TEXT NULL,
  doctor_notes TEXT NULL,
  recommended_treatment TEXT NULL,
  follow_up_required TINYINT(1) NOT NULL DEFAULT 0,
  follow_up_date DATE NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'in_progress',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (patient_id) REFERENCES patients(id),
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id),
  INDEX idx_visits_patient (patient_id),
  INDEX idx_visits_date (visit_date),
  INDEX idx_visits_doctor (doctor_id)
) ENGINE=InnoDB;

CREATE TABLE dental_examinations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  visit_id INT UNSIGNED NOT NULL,
  tooth_number VARCHAR(20) NOT NULL,
  tooth_condition VARCHAR(100) NULL,
  complaint TEXT NULL,
  clinical_findings TEXT NULL,
  diagnosis TEXT NULL,
  recommended_treatment TEXT NULL,
  notes TEXT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (visit_id) REFERENCES patient_visits(id) ON DELETE CASCADE,
  INDEX idx_exam_visit (visit_id)
) ENGINE=InnoDB;

CREATE TABLE treatment_masters (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(100) NULL,
  default_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  estimated_sessions INT UNSIGNED NOT NULL DEFAULT 1,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE patient_treatment_plans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  plan_code VARCHAR(50) NOT NULL UNIQUE,
  patient_id INT UNSIGNED NOT NULL,
  visit_id INT UNSIGNED NULL,
  doctor_id INT UNSIGNED NOT NULL,
  treatment_master_id INT UNSIGNED NOT NULL,
  tooth_number VARCHAR(20) NULL,
  diagnosis TEXT NULL,
  description TEXT NULL,
  start_date DATE NULL,
  estimated_completion DATE NULL,
  sessions INT UNSIGNED NOT NULL DEFAULT 1,
  cost DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  final_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL DEFAULT 'recommended',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (patient_id) REFERENCES patients(id),
  FOREIGN KEY (visit_id) REFERENCES patient_visits(id) ON DELETE SET NULL,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id),
  FOREIGN KEY (treatment_master_id) REFERENCES treatment_masters(id),
  INDEX idx_ptp_patient (patient_id),
  INDEX idx_ptp_status (status)
) ENGINE=InnoDB;

CREATE TABLE treatment_sessions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  treatment_plan_id INT UNSIGNED NOT NULL,
  session_number INT UNSIGNED NOT NULL,
  session_date DATE NOT NULL,
  doctor_id INT UNSIGNED NOT NULL,
  tooth_number VARCHAR(20) NULL,
  procedure_performed TEXT NULL,
  clinical_notes TEXT NULL,
  material_used TEXT NULL,
  next_session_date DATE NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'completed',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (treatment_plan_id) REFERENCES patient_treatment_plans(id) ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id),
  INDEX idx_ts_plan (treatment_plan_id)
) ENGINE=InnoDB;

CREATE TABLE medicine_masters (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  generic_name VARCHAR(150) NULL,
  medicine_type VARCHAR(50) NOT NULL DEFAULT 'Tablet',
  default_dosage VARCHAR(100) NULL,
  default_frequency VARCHAR(100) NULL,
  default_duration VARCHAR(100) NULL,
  default_instructions VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE prescriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  prescription_number VARCHAR(50) NOT NULL UNIQUE,
  patient_id INT UNSIGNED NOT NULL,
  visit_id INT UNSIGNED NULL,
  doctor_id INT UNSIGNED NOT NULL,
  diagnosis TEXT NULL,
  prescription_date DATE NOT NULL,
  advice TEXT NULL,
  follow_up_date DATE NULL,
  notes TEXT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (patient_id) REFERENCES patients(id),
  FOREIGN KEY (visit_id) REFERENCES patient_visits(id) ON DELETE SET NULL,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id),
  INDEX idx_rx_patient (patient_id)
) ENGINE=InnoDB;

CREATE TABLE prescription_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  prescription_id INT UNSIGNED NOT NULL,
  medicine_id INT UNSIGNED NULL,
  medicine_name VARCHAR(150) NOT NULL,
  dosage VARCHAR(100) NULL,
  frequency VARCHAR(100) NULL,
  duration VARCHAR(100) NULL,
  before_after_food VARCHAR(50) NULL,
  instructions VARCHAR(255) NULL,
  FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
  FOREIGN KEY (medicine_id) REFERENCES medicine_masters(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE follow_ups (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  doctor_id INT UNSIGNED NOT NULL,
  treatment_plan_id INT UNSIGNED NULL,
  last_visit_id INT UNSIGNED NULL,
  follow_up_date DATE NOT NULL,
  reason VARCHAR(255) NULL,
  notes TEXT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  appointment_id INT UNSIGNED NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (patient_id) REFERENCES patients(id),
  FOREIGN KEY (doctor_id) REFERENCES doctors(id),
  INDEX idx_fu_date (follow_up_date),
  INDEX idx_fu_status (status)
) ENGINE=InnoDB;

CREATE TABLE patient_documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  document_type VARCHAR(50) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  description VARCHAR(255) NULL,
  uploaded_by INT UNSIGNED NULL,
  created_at DATETIME NULL,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  INDEX idx_docs_patient (patient_id)
) ENGINE=InnoDB;

CREATE TABLE patient_clinical_charts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  chief_complaint TEXT NULL,
  drug_list TEXT NULL,
  habit TEXT NULL,
  test_advised TEXT NULL,
  tooth_notes JSON NULL,
  allotted_doctor_id INT UNSIGNED NULL,
  test_done TEXT NULL,
  next_appt_date DATE NULL,
  next_appt_time TIME NULL,
  next_appt_test TEXT NULL,
  next_appt_doctor_id INT UNSIGNED NULL,
  next_appointment_id INT UNSIGNED NULL,
  implant_appointment_id INT UNSIGNED NULL,
  lab_work LONGTEXT NULL,
  implant_work LONGTEXT NULL,
  created_by INT UNSIGNED NULL,
  updated_by INT UNSIGNED NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uq_pcc_patient (patient_id),
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (allotted_doctor_id) REFERENCES doctors(id) ON DELETE SET NULL,
  FOREIGN KEY (next_appt_doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE bills (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bill_number VARCHAR(50) NOT NULL UNIQUE,
  patient_id INT UNSIGNED NOT NULL,
  treatment_plan_id INT UNSIGNED NULL,
  doctor_id INT UNSIGNED NULL,
  gross_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  booking_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  net_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  pending_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  billing_date DATE NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  notes TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (patient_id) REFERENCES patients(id),
  INDEX idx_bills_patient (patient_id),
  INDEX idx_bills_date (billing_date),
  INDEX idx_bills_status (status)
) ENGINE=InnoDB;

CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  receipt_number VARCHAR(50) NOT NULL UNIQUE,
  bill_id INT UNSIGNED NOT NULL,
  patient_id INT UNSIGNED NOT NULL,
  payment_date DATE NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_mode VARCHAR(50) NOT NULL DEFAULT 'Cash',
  transaction_reference VARCHAR(150) NULL,
  received_by INT UNSIGNED NULL,
  remarks TEXT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'completed',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (bill_id) REFERENCES bills(id),
  FOREIGN KEY (patient_id) REFERENCES patients(id),
  INDEX idx_pay_date (payment_date),
  INDEX idx_pay_patient (patient_id)
) ENGINE=InnoDB;

CREATE TABLE inventory_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE inventory_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  category_id INT UNSIGNED NULL,
  brand VARCHAR(100) NULL,
  unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
  current_stock DECIMAL(12,2) NOT NULL DEFAULT 0,
  minimum_stock DECIMAL(12,2) NOT NULL DEFAULT 0,
  purchase_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
  batch_no VARCHAR(100) NULL,
  expiry_date DATE NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (category_id) REFERENCES inventory_categories(id) ON DELETE SET NULL,
  INDEX idx_inv_stock (current_stock)
) ENGINE=InnoDB;

CREATE TABLE inventory_transactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_id INT UNSIGNED NOT NULL,
  transaction_type VARCHAR(50) NOT NULL,
  quantity DECIMAL(12,2) NOT NULL,
  reference_type VARCHAR(50) NULL,
  reference_id INT UNSIGNED NULL,
  remarks TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NULL,
  FOREIGN KEY (item_id) REFERENCES inventory_items(id),
  INDEX idx_inv_txn_item (item_id)
) ENGINE=InnoDB;

CREATE TABLE suppliers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  contact_person VARCHAR(150) NULL,
  mobile VARCHAR(20) NULL,
  email VARCHAR(150) NULL,
  address TEXT NULL,
  gst_number VARCHAR(50) NULL,
  remarks TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE purchases (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  purchase_number VARCHAR(50) NOT NULL UNIQUE,
  supplier_id INT UNSIGNED NOT NULL,
  purchase_date DATE NOT NULL,
  invoice_number VARCHAR(100) NULL,
  invoice_date DATE NULL,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL DEFAULT 'confirmed',
  notes TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
  INDEX idx_purchase_date (purchase_date)
) ENGINE=InnoDB;

CREATE TABLE purchase_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  purchase_id INT UNSIGNED NOT NULL,
  item_id INT UNSIGNED NOT NULL,
  quantity DECIMAL(12,2) NOT NULL,
  rate DECIMAL(12,2) NOT NULL,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES inventory_items(id)
) ENGINE=InnoDB;

CREATE TABLE approval_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  module VARCHAR(100) NOT NULL,
  record_id INT UNSIGNED NOT NULL,
  action_type VARCHAR(50) NOT NULL,
  requested_by INT UNSIGNED NOT NULL,
  request_date DATETIME NOT NULL,
  reason TEXT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  approved_by INT UNSIGNED NULL,
  approval_date DATETIME NULL,
  remarks TEXT NULL,
  payload LONGTEXT NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  INDEX idx_approval_status (status)
) ENGINE=InnoDB;

-- FK for treatment on appointments (added after treatment_masters exists)
ALTER TABLE appointments
  ADD CONSTRAINT fk_appt_treatment
  FOREIGN KEY (treatment_master_id) REFERENCES treatment_masters(id) ON DELETE SET NULL;
