<?php
require_once 'includes/config.php';

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS
    );
    
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $db->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created successfully\n";
    
    // Select the database
    $db->exec("USE " . DB_NAME);
    
    // Read and execute SQL schema
    $sql = file_get_contents('database/schema.sql');
    $db->exec($sql);
    echo "Database schema created successfully\n";
    
    echo "Database setup completed successfully!\n";
    
} catch(PDOException $e) {
    die("Database setup failed: " . $e->getMessage() . "\n");
}
