// Global variables
let currentDate = new Date();
let selectedDate = null;
let scheduleModal = null;
let currentUser = null;
let selectedTechnicianId = null;

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap modal
    scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
    
    // Get current user data
    getCurrentUser();
    
    // Load technicians if admin or support
    if (document.getElementById('technicianSelect')) {
        loadTechnicians();
    }
    
    // Initialize calendar
    updateCalendar();
    
    // Initialize WebSocket connection for real-time updates
    initializeWebSocket();
});

// Get current user data from JWT
async function getCurrentUser() {
    try {
        const response = await fetch('../api/auth/verify.php', {
            headers: {
                'Authorization': `Bearer ${getCookie('auth_token')}`
            }
        });
        const data = await response.json();
        if (data.success) {
            currentUser = data.user;
            if (currentUser.role === 'technician') {
                selectedTechnicianId = currentUser.id;
                loadSchedules();
            }
        }
    } catch (error) {
        console.error('Error getting user data:', error);
    }
}

// Load technicians for select box
async function loadTechnicians() {
    try {
        const response = await fetch('../api/users/technicians.php', {
            headers: {
                'Authorization': `Bearer ${getCookie('auth_token')}`
            }
        });
        const data = await response.json();
        
        const select = document.getElementById('technicianSelect');
        select.innerHTML = '<option value="">Selecionar Técnico</option>';
        
        data.technicians.forEach(tech => {
            const option = document.createElement('option');
            option.value = tech.id;
            option.textContent = tech.name;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading technicians:', error);
    }
}

// Update calendar with current month
function updateCalendar() {
    const monthNames = ['JANEIRO', 'FEVEREIRO', 'MARÇO', 'ABRIL', 'MAIO', 'JUNHO',
                       'JULHO', 'AGOSTO', 'SETEMBRO', 'OUTUBRO', 'NOVEMBRO', 'DEZEMBRO'];
    
    document.getElementById('currentMonth').textContent = monthNames[currentDate.getMonth()];
    document.getElementById('currentYear').textContent = currentDate.getFullYear();
    
    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    
    const calendarGrid = document.getElementById('calendarGrid');
    calendarGrid.innerHTML = '';
    
    // Add previous month's days
    for (let i = 0; i < firstDay.getDay(); i++) {
        const dayElement = createDayElement(null, true);
        calendarGrid.appendChild(dayElement);
    }
    
    // Add current month's days
    for (let day = 1; day <= lastDay.getDate(); day++) {
        const dayElement = createDayElement(day, false);
        calendarGrid.appendChild(dayElement);
    }
    
    // Load schedules for current month
    loadSchedules();
}

// Create a day element for the calendar
function createDayElement(day, inactive) {
    const dayElement = document.createElement('div');
    dayElement.className = `calendar-day${inactive ? ' inactive' : ''}`;
    
    if (day) {
        dayElement.innerHTML = `
            <div class="day-number">${day}</div>
            <div class="schedules" data-day="${day}"></div>
        `;
        
        dayElement.addEventListener('click', () => {
            selectedDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
            openScheduleModal();
        });
    }
    
    return dayElement;
}

// Load schedules for current month
async function loadSchedules() {
    if (!selectedTechnicianId && currentUser.role === 'technician') {
        selectedTechnicianId = currentUser.id;
    }
    
    if (!selectedTechnicianId && currentUser.role !== 'coordinator') {
        return;
    }
    
    try {
        const response = await fetch(`../api/schedules/month.php?year=${currentDate.getFullYear()}&month=${currentDate.getMonth() + 1}&technician_id=${selectedTechnicianId}`, {
            headers: {
                'Authorization': `Bearer ${getCookie('auth_token')}`
            }
        });
        const data = await response.json();
        
        // Clear existing schedules
        document.querySelectorAll('.schedules').forEach(el => el.innerHTML = '');
        
        // Add schedules to calendar
        data.schedules.forEach(schedule => {
            const date = new Date(schedule.date);
            const dayElement = document.querySelector(`.schedules[data-day="${date.getDate()}"]`);
            
            if (dayElement) {
                const scheduleElement = document.createElement('div');
                scheduleElement.className = `schedule-item status-${schedule.status}`;
                scheduleElement.innerHTML = `
                    <span class="status-dot ${getStatusClass(schedule.status)}"></span>
                    ${schedule.service_type.replace('_', ' ')}
                `;
                dayElement.appendChild(scheduleElement);
            }
        });
    } catch (error) {
        console.error('Error loading schedules:', error);
    }
}

// Open schedule modal
function openScheduleModal(scheduleId = null) {
    if (!selectedTechnicianId && currentUser.role !== 'coordinator') {
        alert('Selecione um técnico primeiro');
        return;
    }
    
    const form = document.getElementById('scheduleForm');
    form.reset();
    
    if (scheduleId) {
        // Load existing schedule data
        loadScheduleData(scheduleId);
    }
    
    // Disable form if user is technician or support
    const isReadOnly = ['technician', 'support'].includes(currentUser.role);
    form.querySelectorAll('input, select, textarea').forEach(el => {
        el.disabled = isReadOnly;
    });
    
    scheduleModal.show();
}

// Save schedule
async function saveSchedule() {
    const formData = {
        date: selectedDate.toISOString().split('T')[0],
        technician_id: selectedTechnicianId,
        local: document.getElementById('local').value,
        client: document.getElementById('client').value,
        service_type: document.getElementById('serviceType').value,
        details: document.getElementById('details').value,
        status: document.querySelector('input[name="status"]:checked').value
    };
    
    try {
        const response = await fetch('../api/schedules/save.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getCookie('auth_token')}`
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        if (data.success) {
            scheduleModal.hide();
            loadSchedules();
        } else {
            alert(data.error || 'Erro ao salvar agendamento');
        }
    } catch (error) {
        console.error('Error saving schedule:', error);
        alert('Erro ao salvar agendamento');
    }
}

// Navigation functions
function previousMonth() {
    currentDate.setMonth(currentDate.getMonth() - 1);
    updateCalendar();
}

function nextMonth() {
    currentDate.setMonth(currentDate.getMonth() + 1);
    updateCalendar();
}

// Sidebar toggle
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('collapsed');
    document.querySelector('.main-content').classList.toggle('expanded');
}

// Technician select change handler
function loadTechnicianSchedule() {
    selectedTechnicianId = document.getElementById('technicianSelect').value;
    loadSchedules();
}

// WebSocket initialization for real-time updates
function initializeWebSocket() {
    const ws = new WebSocket('wss://hmiranda.com.br/sul/ws');
    
    ws.onmessage = function(event) {
        const data = JSON.parse(event.data);
        if (data.type === 'schedule_update') {
            loadSchedules();
        }
    };
    
    ws.onerror = function(error) {
        console.error('WebSocket error:', error);
    };
}

// Helper functions
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

function getStatusClass(status) {
    const statusMap = {
        'em_planejamento': 'planning',
        'aguardando_confirmacao': 'waiting',
        'confirmado': 'confirmed',
        'cancelado': 'canceled'
    };
    return statusMap[status] || 'planning';
}

// Logout function
function logout() {
    document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    window.location.href = '../index.html';
}
