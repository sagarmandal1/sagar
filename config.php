<?php
/**
 * Database Configuration File
 * 
 * This file contains database connection settings.
 * Update these settings according to your environment.
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'user_management');
define('DB_USER', 'root');
define('DB_PASS', '');

// API Configuration
define('API_RATE', 10); // 1 taka = 10 API calls

// Application Configuration
define('APP_NAME', 'ইউজার ম্যানেজমেন্ট সিস্টেম');
define('APP_VERSION', '1.0');

// Pagination Configuration
define('USERS_PER_PAGE', 12);
define('TRANSACTIONS_PER_PAGE', 10);

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

// File Upload Configuration (for future use)
define('UPLOAD_MAX_SIZE', 2097152); // 2MB in bytes
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

/**
 * Get Database Connection
 * 
 * @return PDO Database connection instance
 * @throws Exception If connection fails
 */
function getDatabaseConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Format currency in Bengali
 * 
 * @param float $amount Amount to format
 * @return string Formatted currency string
 */
function formatCurrency($amount) {
    return '৳' . number_format($amount, 2);
}

/**
 * Format number in Bengali numerals
 * 
 * @param int $number Number to format
 * @return string Formatted number in Bengali
 */
function formatBengaliNumber($number) {
    $english = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
    $bengali = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    return str_replace($english, $bengali, $number);
}

/**
 * Calculate API calls from amount
 * 
 * @param float $amount Amount in taka
 * @return int Number of API calls
 */
function calculateApiCalls($amount) {
    return (int) ($amount * API_RATE);
}

/**
 * Sanitize input data
 * 
 * @param string $input Input string to sanitize
 * @return string Sanitized string
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log activity (for future use)
 * 
 * @param string $action Action performed
 * @param int $userId User ID
 * @param array $data Additional data
 */
function logActivity($action, $userId, $data = []) {
    // Implementation for activity logging
    // This can be extended to log to database or file
    error_log("Activity: $action, User: $userId, Data: " . json_encode($data));
}
?>