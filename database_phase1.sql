-- DCMS Phase 1: Database Expansion
-- This script adds the necessary tables for Complaint Management

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Departments Table
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `dept_name` VARCHAR(100) NOT NULL,
    `dept_head_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`dept_head_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Complaint Categories
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `dept_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`dept_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Complaints Table
CREATE TABLE IF NOT EXISTS `complaints` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `attachment_path` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('Pending', 'In-Process', 'Resolved', 'Closed') DEFAULT 'Pending',
    `priority` ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Complaint Assignments
CREATE TABLE IF NOT EXISTS `complaint_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `complaint_id` INT NOT NULL,
    `department_id` INT NOT NULL,
    `assigned_to` INT DEFAULT NULL,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Complaint Action Logs
CREATE TABLE IF NOT EXISTS `complaint_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `complaint_id` INT NOT NULL,
    `action_by` INT NOT NULL,
    `action_taken` VARCHAR(255) NOT NULL,
    `remarks` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`complaint_id`) REFERENCES `complaints`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`action_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
