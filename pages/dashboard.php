<?php
require_once '../includes/config.php';

// Verify authentication
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    header('Location: ../index.html');
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
$userData = verifyJWT($token);

if (!$userData) {
    header('Location: ../index.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sul Agenda - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="bg-dark">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo-container">
            <img src="../assets/img/logo.png" alt="Sul Agenda" class="logo">
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="#"><i class="bi bi-calendar3"></i> Agenda</a>
            <?php if ($userData['role'] === 'admin'): ?>
            <a class="nav-link" href="admin/users.php"><i class="bi bi-people"></i> Administrar</a>
            <?php endif; ?>
        </nav>
        <div class="user-info">
            <div class="user-details">
                <span class="user-name"><?php echo htmlspecialchars($userData['name']); ?></span>
                <span class="user-role"><?php echo htmlspecialchars($userData['email']); ?></span>
            </div>
            <button class="btn btn-link logout-btn" onclick="logout()">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Toggle Sidebar Button -->
        <button class="btn btn-primary sidebar-toggle" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>

        <!-- Calendar Header -->
        <div class="calendar-header">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center">
                    <h2 class="text-white mb-0">
                        <span id="currentMonth">MAIO</span> / 
                        <span id="currentYear">2025</span>
                    </h2>
                    <div class="btn-group ms-3">
                        <button class="btn btn-outline-primary" onclick="previousMonth()">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button class="btn btn-outline-primary" onclick="nextMonth()">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <?php if (in_array($userData['role'], ['admin', 'support'])): ?>
                <div class="technician-select">
                    <select class="form-select" id="technicianSelect" onchange="loadTechnicianSchedule()">
                        <option value="">Selecionar Técnico</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="calendar-grid" id="calendarGrid"></div>
    </div>

    <!-- Schedule Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header">
                    <h5 class="modal-title">Agendamento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="scheduleForm">
                        <div class="mb-3">
                            <label for="local" class="form-label">Local</label>
                            <input type="text" class="form-control" id="local" required>
                        </div>
                        <div class="mb-3">
                            <label for="client" class="form-label">Cliente</label>
                            <input type="text" class="form-control" id="client" required>
                        </div>
                        <div class="mb-3">
                            <label for="serviceType" class="form-label">Serviço a Realizar</label>
                            <select class="form-select" id="serviceType" required>
                                <option value="visita_tecnica">Visita Técnica</option>
                                <option value="visita_comercial">Visita Comercial</option>
                                <option value="manutencao_preventiva">Manutenção Preventiva</option>
                                <option value="manutencao_corretiva">Manutenção Corretiva</option>
                                <option value="preventiva_contratual">Preventiva Contratual</option>
                                <option value="corretiva_contratual">Corretiva Contratual</option>
                                <option value="deslocamento">Deslocamento</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="details" class="form-label">Detalhes</label>
                            <textarea class="form-control" id="details" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <div class="status-options">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="statusPlanning" value="em_planejamento" checked>
                                    <label class="form-check-label" for="statusPlanning">
                                        <span class="status-dot planning"></span>
                                        Em Planejamento
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="statusWaiting" value="aguardando_confirmacao">
                                    <label class="form-check-label" for="statusWaiting">
                                        <span class="status-dot waiting"></span>
                                        Aguardando Confirmação
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="statusConfirmed" value="confirmado">
                                    <label class="form-check-label" for="statusConfirmed">
                                        <span class="status-dot confirmed"></span>
                                        Confirmado
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="statusCanceled" value="cancelado">
                                    <label class="form-check-label" for="statusCanceled">
                                        <span class="status-dot canceled"></span>
                                        Cancelado
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <?php if (in_array($userData['role'], ['admin', 'coordinator'])): ?>
                    <button type="button" class="btn btn-primary" onclick="saveSchedule()">Salvar</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
