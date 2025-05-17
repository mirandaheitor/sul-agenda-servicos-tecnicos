<?php
require_once '../../includes/config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondWithError('Method not allowed', 405);
}

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['email']) || !isset($data['password'])) {
    respondWithError('Email and password are required');
}

$email = sanitizeInput($data['email']);
$password = $data['password'];

try {
    $db = getDBConnection();
    
    // Get user by email
    $stmt = $db->prepare("SELECT id, email, password, role, name, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        respondWithError('Invalid credentials', 401);
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        respondWithError('Invalid credentials', 401);
    }
    
    // Check if user is active
    if ($user['status'] !== 'active') {
        respondWithError('Account is not active', 403);
    }
    
    // Remove password from user data
    unset($user['password']);
    
    // Generate JWT token
    $token = generateJWT($user);
    
    // Update last login
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Return success response with token and user data
    respondWithSuccess([
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
    
} catch(PDOException $e) {
    respondWithError('Database error: ' . $e->getMessage(), 500);
}
