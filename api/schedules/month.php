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
    respondWithError('Unauthorized', 401);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respondWithError('Method not allowed', 405);
}

// Validate and sanitize input parameters
$year = isset($_GET['year']) ? filter_var($_GET['year'], FILTER_VALIDATE_INT) : date('Y');
$month = isset($_GET['month']) ? filter_var($_GET['month'], FILTER_VALIDATE_INT) : date('m');
$technicianId = isset($_GET['technician_id']) ? filter_var($_GET['technician_id'], FILTER_VALIDATE_INT) : null;

if (!$year || !$month || $month < 1 || $month > 12) {
    respondWithError('Invalid date parameters');
}

// Build the date range for the query
$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = date('Y-m-t', strtotime($startDate));

try {
    $db = getDBConnection();
    
    // Base query
    $query = "
        SELECT 
            s.*,
            u_tech.name as technician_name,
            u_coord.name as coordinator_name
        FROM schedules s
        JOIN users u_tech ON s.technician_id = u_tech.id
        JOIN users u_coord ON s.coordinator_id = u_coord.id
        WHERE s.date BETWEEN ? AND ?
    ";
    $params = [$startDate, $endDate];
    
    // Add technician filter based on user role and parameters
    if ($userData['role'] === 'technician') {
        // Technicians can only see their own schedules
        $query .= " AND s.technician_id = ?";
        $params[] = $userData['id'];
    } elseif ($technicianId) {
        // Filter by specific technician if provided
        $query .= " AND s.technician_id = ?";
        $params[] = $technicianId;
    }
    
    // Order by date
    $query .= " ORDER BY s.date ASC, s.created_at ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $schedules = $stmt->fetchAll();
    
    // Format the response data
    $formattedSchedules = array_map(function($schedule) {
        return [
            'id' => $schedule['id'],
            'date' => $schedule['date'],
            'local' => $schedule['local'],
            'client' => $schedule['client'],
            'service_type' => $schedule['service_type'],
            'details' => $schedule['details'],
            'status' => $schedule['status'],
            'technician' => [
                'id' => $schedule['technician_id'],
                'name' => $schedule['technician_name']
            ],
            'coordinator' => [
                'id' => $schedule['coordinator_id'],
                'name' => $schedule['coordinator_name']
            ],
            'created_at' => $schedule['created_at'],
            'updated_at' => $schedule['updated_at']
        ];
    }, $schedules);
    
    // Get summary statistics
    $stats = [
        'total' => count($schedules),
        'by_status' => [
            'em_planejamento' => 0,
            'aguardando_confirmacao' => 0,
            'confirmado' => 0,
            'cancelado' => 0
        ],
        'by_service' => []
    ];
    
    foreach ($schedules as $schedule) {
        $stats['by_status'][$schedule['status']]++;
        
        if (!isset($stats['by_service'][$schedule['service_type']])) {
            $stats['by_service'][$schedule['service_type']] = 0;
        }
        $stats['by_service'][$schedule['service_type']]++;
    }
    
    respondWithSuccess([
        'schedules' => $formattedSchedules,
        'stats' => $stats,
        'period' => [
            'year' => $year,
            'month' => $month,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]
    ]);
    
} catch(PDOException $e) {
    respondWithError('Database error: ' . $e->getMessage(), 500);
}
