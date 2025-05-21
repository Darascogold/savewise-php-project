<?php
// Database configuration
$host = 'localhost';
$db   = 'savewise_db'; 
$user = 'root';
$pass = ''; 
$charset = 'utf8mb4';

// Data Source Name
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
    PDO::ATTR_EMULATE_PREPARES   => false,              
];

try {
    
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    
    die("Database connection failed: " . $e->getMessage());
}
?>
