<?php
require_once '../../includes/config.php';

// Verify authentication
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    respondWithError('Unauthorized', 401);
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
$userData = verifyJWT($token);

if (!$userData) {
    respondWithError('Invalid or expired token', 401);
}

try {
    $db = getDBConnection();
    
    // Get user data
    $stmt = $db->prepare("
        SELECT 
            id,
            name,
            email,
            role,
            status,
            created_at,
            last_login
        FROM users 
        WHERE id = ? AND status = 'active'
    ");
    
    $stmt->execute([$userData['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        respondWithError('User not found or inactive', 401);
    }
    
    // Determine redirect URL based on role
    $redirectUrl = 'pages/';
    switch ($user['role']) {
        case 'admin':
            $redirectUrl .= 'admin/dashboard.php';
            break;
        case 'coordinator':
            $redirectUrl .= 'coordinator/dashboard.php';
            break;
        case 'technician':
            $redirectUrl .= 'technician/dashboard.php';
            break;
        case 'support':
            $redirectUrl .= 'support/dashboard.php';
            break;
        default:
            $redirectUrl .= 'dashboard.php';
    }
    
    // Get user permissions
    $permissions = [];
    switch ($user['role']) {
        case 'admin':
            $permissions = [
                'manage_users' => true,
                'manage_schedules' => true,
                'view_all_schedules' => true,
                'edit_schedules' => true,
                'manage_notifications' => true
            ];
            break;
        case 'coordinator':
            $permissions = [
                'manage_schedules' => true,
                'view_all_schedules' => true,
                'edit_schedules' => true,
                'manage_notifications' => true
            ];
            break;
        case 'technician':
            $permissions = [
                'view_own_schedule' => true
            ];
            break;
        case 'support':
            $permissions = [
                'view_all_schedules' => true
            ];
            break;
    }
    
    // Get unread notifications count
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM notifications
        WHERE user_id = ?
        AND status = 'pending'
    ");
    $stmt->execute([$user['id']]);
    $notifications = $stmt->fetch();
    
    respondWithSuccess([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'created_at' => $user['created_at'],
            'last_login' => $user['last_login']
        ],
        'permissions' => $permissions,
        'notifications' => [
            'unread_count' => (int)$notifications['count']
        ],
        'redirectUrl' => $redirectUrl
    ]);
    
} catch(PDOException $e) {
    respondWithError('Database error: ' . $e->getMessage(), 500);
}
