-- Adicionar campo whatsapp na tabela users (se ainda não existir)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS whatsapp VARCHAR(20) AFTER email,
ADD COLUMN IF NOT EXISTS receive_reminders TINYINT(1) DEFAULT 1 COMMENT 'Cliente aceita receber lembretes';

-- Tabela para controlar lembretes enviados
CREATE TABLE IF NOT EXISTS reminders_sent (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    reminder_type ENUM('24h', '2h', '1h') NOT NULL,
    sent_via ENUM('whatsapp', 'email', 'both') NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('enviado', 'erro', 'pendente') DEFAULT 'pendente',
    error_message TEXT,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_reminder (event_id, reminder_type),
    INDEX idx_event (event_id),
    INDEX idx_sent_at (sent_at)
);

-- Tabela de configurações de lembretes
CREATE TABLE IF NOT EXISTS reminder_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir configurações padrão
INSERT INTO reminder_settings (setting_key, setting_value, description) VALUES
('enable_24h_reminder', '1', 'Enviar lembrete 24 horas antes'),
('enable_2h_reminder', '1', 'Enviar lembrete 2 horas antes'),
('enable_1h_reminder', '0', 'Enviar lembrete 1 hora antes'),
('reminder_method', 'both', 'Método: whatsapp, email ou both'),
('business_hours_start', '08:00', 'Início do expediente'),
('business_hours_end', '20:00', 'Fim do expediente')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
