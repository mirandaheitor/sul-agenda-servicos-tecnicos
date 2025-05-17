-- Create users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'coordinator', 'technician', 'support') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Create schedules table
CREATE TABLE schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    technician_id INT NOT NULL,
    coordinator_id INT NOT NULL,
    date DATE NOT NULL,
    local VARCHAR(255) NOT NULL,
    client VARCHAR(255) NOT NULL,
    service_type ENUM('visita_tecnica', 'visita_comercial', 'manutencao_preventiva', 'manutencao_corretiva', 'preventiva_contratual', 'corretiva_contratual', 'deslocamento') NOT NULL,
    details TEXT,
    status ENUM('em_planejamento', 'aguardando_confirmacao', 'confirmado', 'cancelado') NOT NULL DEFAULT 'em_planejamento',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (technician_id) REFERENCES users(id),
    FOREIGN KEY (coordinator_id) REFERENCES users(id)
);

-- Create notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    schedule_id INT NOT NULL,
    type ENUM('whatsapp', 'email', 'push', 'sms') NOT NULL,
    status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (schedule_id) REFERENCES schedules(id)
);

-- Create activity_logs table
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default admin user
INSERT INTO users (name, email, password, role) VALUES (
    'Admin',
    'admin@sulagenda.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: 'password'
    'admin'
);

-- Create indexes for better performance
CREATE INDEX idx_schedules_date ON schedules(date);
CREATE INDEX idx_schedules_technician ON schedules(technician_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_status ON notifications(status);
CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
