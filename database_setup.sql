-- Database schema for User Management System
-- Create the database and tables needed for the enhanced credit management system

CREATE DATABASE IF NOT EXISTS user_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE user_management;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    balance DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Credit transactions table for tracking all credit additions
CREATE TABLE IF NOT EXISTS credit_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Insert some sample data for demonstration
INSERT INTO users (name, email, phone, balance) VALUES
('সাগর মন্ডল', 'sagar@example.com', '+8801712345678', 1500.00),
('রহিম উদ্দিন', 'rahim@example.com', '+8801812345678', 2500.00),
('করিম আহমেদ', 'karim@example.com', '+8801912345678', 800.00),
('ফাতেমা খাতুন', 'fatema@example.com', '+8801612345678', 3200.00),
('আব্দুল হালিম', 'halim@example.com', '+8801512345678', 950.00);

-- Insert some sample credit transactions
INSERT INTO credit_transactions (user_id, amount, note) VALUES
(1, 500.00, 'API সেবার জন্য প্রাথমিক ক্রেডিট'),
(1, 1000.00, 'মাসিক প্ল্যান রিচার্জ'),
(2, 2000.00, 'বার্ষিক প্ল্যান ক্রয়'),
(2, 500.00, 'বোনাস ক্রেডিট'),
(3, 800.00, 'নতুন ইউজার বোনাস'),
(4, 2500.00, 'প্রিমিয়াম প্ল্যান আপগ্রেড'),
(4, 700.00, 'রেফারেল বোনাস'),
(5, 950.00, 'স্টার্টার প্ল্যান ক্রয়');