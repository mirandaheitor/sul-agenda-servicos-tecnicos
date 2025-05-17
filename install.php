<?php
echo "Sul Agenda Installation Script\n";
echo "============================\n\n";

// Check PHP version
$requiredPhpVersion = '7.4.0';
if (version_compare(PHP_VERSION, $requiredPhpVersion, '<')) {
    die("Error: PHP version {$requiredPhpVersion} or higher is required. Current version: " . PHP_VERSION . "\n");
}

// Check required PHP extensions
$requiredExtensions = ['pdo_mysql', 'json', 'session'];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        die("Error: Required PHP extension '{$ext}' is not loaded.\n");
    }
}

// Test database connection
echo "Testing database connection...\n";
require_once 'includes/config.php';

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $db->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created successfully.\n";
    
    // Select the database
    $db->exec("USE " . DB_NAME);
    
    // Import schema
    echo "Importing database schema...\n";
    $sql = file_get_contents('database/schema.sql');
    $db->exec($sql);
    echo "Schema imported successfully.\n";
    
    // Create default admin user if not exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = 'admin@sulagenda.com'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, role, status)
            VALUES ('Administrator', 'admin@sulagenda.com', ?, 'admin', 'active')
        ");
        $stmt->execute([password_hash('password', PASSWORD_DEFAULT)]);
        echo "Default admin user created.\n";
    }
    
    echo "\nInstallation completed successfully!\n";
    echo "You can now log in with:\n";
    echo "Email: admin@sulagenda.com\n";
    echo "Password: password\n\n";
    echo "IMPORTANT: Please change the admin password after first login!\n";
    
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
}

// Check write permissions for important directories
$writableDirs = [
    'assets/img/uploads',
    'logs'
];

echo "\nChecking directory permissions...\n";
foreach ($writableDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: {$dir}\n";
    }
    if (!is_writable($dir)) {
        echo "Warning: Directory '{$dir}' is not writable.\n";
    }
}

// Generate .htaccess for Apache
if (!file_exists('.htaccess')) {
    $htaccess = "
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Redirect to HTTPS
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # API Routing
    RewriteRule ^api/.* - [L]
    
    # Prevent direct access to .php files
    RewriteCond %{THE_REQUEST} ^[A-Z]{3,}\s([^.]+)\.php [NC]
    RewriteRule ^ %1 [R=301,L]
    
    # Add .php extension internally
    RewriteCond %{REQUEST_FILENAME}.php -f
    RewriteRule ^([^\.]+)$ $1.php [NC,L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options nosniff
    Header set X-Frame-Options SAMEORIGIN
    Header set X-XSS-Protection \"1; mode=block\"
    Header set Referrer-Policy \"strict-origin-when-cross-origin\"
</IfModule>

# Prevent directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch \"^(config\.php|schema\.sql|composer\.json|package\.json|\.(env|git|htaccess))$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>
";
    file_put_contents('.htaccess', $htaccess);
    echo "Created .htaccess file with security configurations.\n";
}

echo "\nSetup complete! Please ensure your web server is configured correctly.\n";
echo "For more information, please read the README.md file.\n";
