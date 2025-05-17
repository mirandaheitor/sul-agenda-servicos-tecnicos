<?php
require_once '../../includes/config.php';

// Verify authentication
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    respondWithError('Unauthorized', 401);
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
$userData = verifyJWT($token);

if (!$userData || !in_array($userData['role'], ['admin', 'coordinator'])) {
    respondWithError('Unauthorized', 401);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondWithError('Method not allowed', 405);
}

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
$requiredFields = ['date', 'technician_id', 'local', 'client', 'service_type', 'status'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        respondWithError("Field {$field} is required");
    }
}

// Validate service type
$validServiceTypes = [
    'visita_tecnica',
    'visita_comercial',
    'manutencao_preventiva',
    'manutencao_corretiva',
    'preventiva_contratual',
    'corretiva_contratual',
    'deslocamento'
];

if (!in_array($data['service_type'], $validServiceTypes)) {
    respondWithError('Invalid service type');
}

// Validate status
$validStatuses = ['em_planejamento', 'aguardando_confirmacao', 'confirmado', 'cancelado'];
if (!in_array($data['status'], $validStatuses)) {
    respondWithError('Invalid status');
}

try {
    $db = getDBConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // Check if technician exists and is active
    $stmt = $db->prepare("SELECT id, status FROM users WHERE id = ? AND role = 'technician'");
    $stmt->execute([$data['technician_id']]);
    $technician = $stmt->fetch();
    
    if (!$technician) {
        respondWithError('Invalid technician');
    }
    
    if ($technician['status'] !== 'active') {
        respondWithError('Technician is not active');
    }
    
    // Insert or update schedule
    if (isset($data['id'])) {
        // Update existing schedule
        $stmt = $db->prepare("
            UPDATE schedules 
            SET technician_id = ?, coordinator_id = ?, date = ?, local = ?, 
                client = ?, service_type = ?, details = ?, status = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['technician_id'],
            $userData['id'], // coordinator_id is the current user
            $data['date'],
            $data['local'],
            $data['client'],
            $data['service_type'],
            $data['details'] ?? null,
            $data['status'],
            $data['id']
        ]);
        
        $scheduleId = $data['id'];
    } else {
        // Insert new schedule
        $stmt = $db->prepare("
            INSERT INTO schedules 
            (technician_id, coordinator_id, date, local, client, service_type, details, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['technician_id'],
            $userData['id'], // coordinator_id is the current user
            $data['date'],
            $data['local'],
            $data['client'],
            $data['service_type'],
            $data['details'] ?? null,
            $data['status']
        ]);
        
        $scheduleId = $db->lastInsertId();
    }
    
    // Create notification
    $message = sprintf(
        "Novo agendamento para %s: %s em %s",
        date('d/m/Y', strtotime($data['date'])),
        $data['service_type'],
        $data['local']
    );
    
    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, schedule_id, type, message)
        VALUES (?, ?, 'whatsapp', ?)
    ");
    
    $stmt->execute([$data['technician_id'], $scheduleId, $message]);
    
    // Log activity
    $action = isset($data['id']) ? 'update_schedule' : 'create_schedule';
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, description, ip_address)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userData['id'],
        $action,
        "Schedule ID: {$scheduleId}",
        $_SERVER['REMOTE_ADDR']
    ]);
    
    // Commit transaction
    $db->commit();
    
    // Send WebSocket notification
    $wsMessage = json_encode([
        'type' => 'schedule_update',
        'schedule_id' => $scheduleId,
        'technician_id' => $data['technician_id']
    ]);
    
    // You would implement your WebSocket server connection here
    // For now, we'll just log it
    error_log("WebSocket message: {$wsMessage}");
    
    respondWithSuccess([
        'id' => $scheduleId,
        'message' => 'Schedule saved successfully'
    ]);
    
} catch(PDOException $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    respondWithError('Database error: ' . $e->getMessage(), 500);
}
