-- SmartCare Database Schema
-- SQL script to create the database and required tables

-- CREATE DATABASE IF NOT EXISTS `smartcare_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `smartcare_db`;

-- 1. Users Table
-- Stores credentials and user profile information for all roles (patient, doctor, admin)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Doctors Table
-- Stores professional details specific to users with the 'doctor' role
CREATE TABLE IF NOT EXISTS `doctors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `specialty` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `fee` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `available_days` VARCHAR(255) NOT NULL COMMENT 'Comma-separated days e.g., Monday,Wednesday,Friday',
    `is_verified` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Verified/Active, 0 = Pending Approval',
    CONSTRAINT `fk_doctors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Appointments Table
-- Stores booking information between patients and doctors
CREATE TABLE IF NOT EXISTS `appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `time_slot` VARCHAR(50) NOT NULL,
    `status` ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `payment_status` ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
    `trx_id` VARCHAR(255) DEFAULT NULL,
    `meeting_link` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_appointments_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_appointments_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Prescriptions Table
-- Stores e-prescriptions issued by doctors for completed appointments
CREATE TABLE IF NOT EXISTS `prescriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `appointment_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `patient_id` INT NOT NULL,
    `diagnosis` TEXT NOT NULL,
    `medicines` TEXT NOT NULL,
    `instructions` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_prescriptions_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_prescriptions_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_prescriptions_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Notifications Table
-- Stores in-app user notifications for appointment updates, meeting links, and alerts
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Messages Table
-- Stores real-time chat messages between patients and doctors for an appointment
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `appointment_id` INT NOT NULL,
    `sender_id` INT NOT NULL,
    `receiver_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_messages_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



