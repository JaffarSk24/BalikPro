
-- Balik PRO - Database Schema (MySQL InnoDB, utf8mb4)
-- Generated: 2025-09-09

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE DATABASE IF NOT EXISTS balikpro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE balikpro;

-- 1. admins
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uuid CHAR(36) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(150) NULL,
  role ENUM('superadmin','operator') NOT NULL DEFAULT 'operator',
  totp_secret VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. partners
CREATE TABLE IF NOT EXISTS partners (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uuid CHAR(36) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  contact_person VARCHAR(150) NULL,
  email VARCHAR(255) NULL,
  phone VARCHAR(50) NULL,
  logo_path VARCHAR(500) NULL,
  pin_hash VARCHAR(255) NOT NULL,
  payout_account VARCHAR(255) NULL,
  monthly_limit INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_partners_name (name),
  INDEX idx_partners_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. services
CREATE TABLE IF NOT EXISTS services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uuid CHAR(36) NOT NULL UNIQUE,
  partner_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  nominal_value DECIMAL(10,2) NULL, -- используется для подсчёта маркетинговой «úspora»
  is_main TINYINT(1) NOT NULL DEFAULT 0,
  is_bonus TINYINT(1) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  issue_limit INT NULL,
  contact_info VARCHAR(500) NULL,
  meta JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_services_partner (partner_id),
  INDEX idx_services_is_main (is_main),
  INDEX idx_services_is_bonus (is_bonus),
  CONSTRAINT fk_services_partner FOREIGN KEY (partner_id) REFERENCES partners(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. bundles
CREATE TABLE IF NOT EXISTS bundles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uuid CHAR(36) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  main_service_id INT NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_bundles_main_service FOREIGN KEY (main_service_id) REFERENCES services(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. bundle_items (bonus services per bundle)
CREATE TABLE IF NOT EXISTS bundle_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bundle_id INT NOT NULL,
  service_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_bundle_items_bundle FOREIGN KEY (bundle_id) REFERENCES bundles(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_bundle_items_service FOREIGN KEY (service_id) REFERENCES services(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_bundle_items_bundle (bundle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. customers (minimal data per GDPR)
CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uuid CHAR(36) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customers_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. orders
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uuid CHAR(36) NOT NULL UNIQUE,
  order_number VARCHAR(50) NOT NULL UNIQUE,
  customer_id INT NULL,
  customer_name VARCHAR(255) NOT NULL,
  customer_email VARCHAR(255) NOT NULL,
  customer_phone VARCHAR(50) NULL,
  bundle_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'EUR',
  payment_provider VARCHAR(50) NULL,
  payment_status ENUM('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
  payment_provider_payload JSON NULL,
  pdf_path VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_orders_status (payment_status),
  INDEX idx_orders_created (created_at),
  CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_orders_bundle FOREIGN KEY (bundle_id) REFERENCES bundles(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. coupons
CREATE TABLE IF NOT EXISTS coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  uuid CHAR(36) NOT NULL UNIQUE,
  order_id INT NOT NULL,
  service_id INT NOT NULL,
  partner_id INT NOT NULL,
  type ENUM('main','bonus') NOT NULL,
  qr_hash CHAR(64) NOT NULL UNIQUE,
  code VARCHAR(100) NULL,
  valid_from DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  valid_until DATETIME NOT NULL,
  status ENUM('active','redeemed','expired','revoked') NOT NULL DEFAULT 'active',
  redeemed_at DATETIME NULL,
  redeemed_by VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_coupons_partner (partner_id),
  INDEX idx_coupons_status (status),
  INDEX idx_coupons_valid_until (valid_until),
  CONSTRAINT fk_coupons_order FOREIGN KEY (order_id) REFERENCES orders(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_coupons_service FOREIGN KEY (service_id) REFERENCES services(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_coupons_partner FOREIGN KEY (partner_id) REFERENCES partners(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. redemption_logs
CREATE TABLE IF NOT EXISTS redemption_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  coupon_id INT NOT NULL,
  partner_id INT NOT NULL,
  attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  pin_entered_hash VARCHAR(255) NULL, -- при необходимости
  success TINYINT(1) NOT NULL DEFAULT 0,
  note TEXT NULL,
  INDEX idx_redemption_coupon (coupon_id),
  INDEX idx_redemption_partner (partner_id),
  CONSTRAINT fk_redemption_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_redemption_partner FOREIGN KEY (partner_id) REFERENCES partners(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. payments (provider logs)
CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  provider VARCHAR(50) NOT NULL,
  provider_payment_id VARCHAR(255) NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'EUR',
  status ENUM('created','captured','refunded','failed') NOT NULL,
  raw_payload JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_payments_provider_id (provider_payment_id),
  CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. monthly_snapshots
CREATE TABLE IF NOT EXISTS monthly_snapshots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  month_year VARCHAR(20) NOT NULL UNIQUE, -- format 'Jun 2025'
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  data JSON NOT NULL,
  archived_by_admin INT NULL,
  note TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. payouts
CREATE TABLE IF NOT EXISTS payouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  partner_id INT NOT NULL,
  snapshot_id INT NOT NULL,
  amount_due DECIMAL(10,2) NOT NULL,
  status ENUM('pending','paid','rejected') NOT NULL DEFAULT 'pending',
  paid_at DATETIME NULL,
  payout_reference VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_payouts_partner (partner_id),
  INDEX idx_payouts_status (status),
  CONSTRAINT fk_payouts_partner FOREIGN KEY (partner_id) REFERENCES partners(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_payouts_snapshot FOREIGN KEY (snapshot_id) REFERENCES monthly_snapshots(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. pdf_files
CREATE TABLE IF NOT EXISTS pdf_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  generated_by INT NULL,
  CONSTRAINT fk_pdf_files_order FOREIGN KEY (order_id) REFERENCES orders(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. email_logs
CREATE TABLE IF NOT EXISTS email_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  to_email VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body_preview TEXT NULL,
  provider VARCHAR(50) NOT NULL,
  provider_payload JSON NULL,
  sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('sent','failed') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. audit_logs
CREATE TABLE IF NOT EXISTS audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actor_type ENUM('admin','system','partner') NOT NULL,
  actor_id INT NULL,
  action VARCHAR(255) NOT NULL,
  object_type VARCHAR(100) NULL,
  object_id INT NULL,
  payload JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;
