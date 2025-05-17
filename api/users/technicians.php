<?php
require_once '../../includes/config.php';

// Verify authentication
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    respondWithError('Unauthorized', 401);
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
$userData = verifyJWT($token);

if (!$userData || !in_array($userData['role'], ['admin', 'coordinator', 'support'])) {
    respondWithError('Unauthorized', 401);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respondWithError('Method not allowed', 405);
}

try {
    $db = getDBConnection();
    
    // Get active technicians
    $query = "
        SELECT 
            id,
            name,
            email,
            status,
            created_at,
            last_login
        FROM users 
        WHERE role = 'technician' 
        AND status = 'active'
        ORDER BY name ASC
    ";
    
    $stmt = $db->query($query);
    $technicians = $stmt->fetchAll();
    
    // Get schedule counts for each technician
    $technicianStats = [];
    foreach ($technicians as $tech) {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_schedules,
                SUM(CASE WHEN status = 'confirmado' THEN 1 ELSE 0 END) as confirmed_schedules,
                SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as canceled_schedules,
                SUM(CASE WHEN date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_schedules
            FROM schedules 
            WHERE technician_id = ?
        ");
        $stmt->execute([$tech['id']]);
        $stats = $stmt->fetch();
        
        $technicianStats[$tech['id']] = $stats;
    }
    
    // Format response
    $formattedTechnicians = array_map(function($tech) use ($technicianStats) {
        return [
            'id' => $tech['id'],
            'name' => $tech['name'],
            'email' => $tech['email'],
            'status' => $tech['status'],
            'created_at' => $tech['created_at'],
            'last_login' => $tech['last_login'],
            'stats' => [
                'total_schedules' => (int)$technicianStats[$tech['id']]['total_schedules'],
                'confirmed_schedules' => (int)$technicianStats[$tech['id']]['confirmed_schedules'],
                'canceled_schedules' => (int)$technicianStats[$tech['id']]['canceled_schedules'],
                'upcoming_schedules' => (int)$technicianStats[$tech['id']]['upcoming_schedules']
            ]
        ];
    }, $technicians);
    
    respondWithSuccess([
        'technicians' => $formattedTechnicians,
        'total_count' => count($technicians)
    ]);
    
} catch(PDOException $e) {
    respondWithError('Database error: ' . $e->getMessage(), 500);
}
