-- Victorious Innovatech Solutions - Complete Database Schema
-- Run this SQL in your MySQL / phpMyAdmin database

CREATE DATABASE IF NOT EXISTS `victorious_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `victorious_db`;

-- 1. Newsletter Subscribers Table
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `status` ENUM('active', 'unsubscribed') DEFAULT 'active',
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Contact Inquiries Table
CREATE TABLE IF NOT EXISTS `contact_inquiries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `status` ENUM('new', 'in_review', 'replied') DEFAULT 'new',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_contact_email` (`email`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. AI Chatbot Logs Table (To track client inquiries & questions)
CREATE TABLE IF NOT EXISTS `chat_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_message` TEXT NOT NULL,
    `bot_response` TEXT NOT NULL,
    `engine` VARCHAR(50) DEFAULT 'local_ai', -- 'gemini' or 'local_ai'
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
