<?php
// Database configuration
$host = 'localhost';
$db   = 'savewise_db'; // Make sure this matches your actual database name
$user = 'root';
$pass = ''; // Default password for root user in XAMPP
$charset = 'utf8mb4';

// Data Source Name
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enable exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
];

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Handle connection error (enable this for debugging; disable in production)
    die("Database connection failed: " . $e->getMessage());
}
?>
