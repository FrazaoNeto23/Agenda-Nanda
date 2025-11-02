-- =====================================================
-- SISTEMA DE AGENDA MANICURE - VERSÃO COMPLETA
-- =====================================================

CREATE DATABASE IF NOT EXISTS agenda_manicure;
USE agenda_manicure;

-- =====================================================
-- TABELA DE USUÁRIOS (Atualizada)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    whatsapp VARCHAR(20) DEFAULT NULL,
    role ENUM('cliente', 'dono', 'funcionario') DEFAULT 'cliente',
    active BOOLEAN DEFAULT TRUE,
    avatar VARCHAR(255) DEFAULT NULL,
    points INT DEFAULT 0 COMMENT 'Programa de fidelidade',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE SERVIÇOS (Atualizada)
-- =====================================================
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    duration INT DEFAULT 60 COMMENT 'Duração em minutos',
    active BOOLEAN DEFAULT TRUE,
    category VARCHAR(50) DEFAULT 'geral',
    image VARCHAR(255) DEFAULT NULL,
    points_reward INT DEFAULT 10 COMMENT 'Pontos ganhos ao concluir',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (active),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE EVENTOS/AGENDAMENTOS (Atualizada)
-- =====================================================
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    start DATETIME NOT NULL,
    end DATETIME NOT NULL,
    status ENUM('pendente', 'agendado', 'concluido', 'cancelado', 'bloqueado') DEFAULT 'pendente',
    user_id INT DEFAULT NULL,
    service_id INT DEFAULT NULL,
    professional_id INT DEFAULT NULL COMMENT 'Profissional que vai atender',
    notes TEXT COMMENT 'Observações internas',
    client_notes TEXT COMMENT 'Observações do cliente',
    cancel_reason TEXT,
    confirmed_at DATETIME DEFAULT NULL,
    reminder_sent BOOLEAN DEFAULT FALSE,
    payment_status ENUM('pendente', 'pago', 'cancelado') DEFAULT 'pendente',
    payment_method VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    FOREIGN KEY (professional_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_start (start),
    INDEX idx_status (status),
    INDEX idx_user (user_id),
    INDEX idx_service (service_id),
    INDEX idx_date (start, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE HISTÓRICO DE MUDANÇAS
-- =====================================================
CREATE TABLE IF NOT EXISTS historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    acao VARCHAR(50) NOT NULL,
    descricao TEXT,
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_event (event_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE AVALIAÇÕES
-- =====================================================
CREATE TABLE IF NOT EXISTS avaliacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    nota INT NOT NULL CHECK (nota BETWEEN 1 AND 5),
    comentario TEXT,
    resposta TEXT COMMENT 'Resposta do estabelecimento',
    respondido_por INT DEFAULT NULL,
    respondido_em DATETIME DEFAULT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (respondido_por) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_service (service_id),
    INDEX idx_nota (nota),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE CUPONS DE DESCONTO
-- =====================================================
CREATE TABLE IF NOT EXISTS cupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    descricao VARCHAR(255),
    tipo ENUM('percentual', 'fixo') DEFAULT 'percentual',
    valor DECIMAL(10, 2) NOT NULL,
    min_value DECIMAL(10, 2) DEFAULT 0 COMMENT 'Valor mínimo para usar',
    max_discount DECIMAL(10, 2) DEFAULT NULL COMMENT 'Desconto máximo (para %)',
    quantidade_total INT DEFAULT NULL COMMENT 'Quantidade de cupons disponíveis',
    quantidade_usada INT DEFAULT 0,
    valid_from DATE DEFAULT NULL,
    valid_until DATE DEFAULT NULL,
    active BOOLEAN DEFAULT TRUE,
    first_time_only BOOLEAN DEFAULT FALSE COMMENT 'Apenas para novos clientes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_active (active),
    INDEX idx_validade (valid_from, valid_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE USO DE CUPONS
-- =====================================================
CREATE TABLE IF NOT EXISTS cupons_uso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cupom_id INT NOT NULL,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    valor_desconto DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cupom_id) REFERENCES cupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_cupom (cupom_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE PACOTES/COMBOS
-- =====================================================
CREATE TABLE IF NOT EXISTS pacotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    sessions INT NOT NULL COMMENT 'Quantidade de sessões',
    valid_days INT DEFAULT 90 COMMENT 'Validade em dias',
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE SERVIÇOS DO PACOTE
-- =====================================================
CREATE TABLE IF NOT EXISTS pacotes_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pacote_id INT NOT NULL,
    service_id INT NOT NULL,
    FOREIGN KEY (pacote_id) REFERENCES pacotes(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    UNIQUE KEY unique_pacote_service (pacote_id, service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE COMPRA DE PACOTES
-- =====================================================
CREATE TABLE IF NOT EXISTS pacotes_comprados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pacote_id INT NOT NULL,
    sessions_total INT NOT NULL,
    sessions_used INT DEFAULT 0,
    valid_until DATE NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pacote_id) REFERENCES pacotes(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE GALERIA DE TRABALHOS
-- =====================================================
CREATE TABLE IF NOT EXISTS galeria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100),
    description TEXT,
    image_path VARCHAR(255) NOT NULL,
    service_id INT DEFAULT NULL,
    ordem INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    INDEX idx_active (active),
    INDEX idx_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE LISTA DE ESPERA
-- =====================================================
CREATE TABLE IF NOT EXISTS lista_espera (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    preferred_date DATE DEFAULT NULL,
    preferred_time TIME DEFAULT NULL,
    notes TEXT,
    notified BOOLEAN DEFAULT FALSE,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    INDEX idx_active (active),
    INDEX idx_date (preferred_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE CONFIGURAÇÕES DO SISTEMA
-- =====================================================
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    descricao VARCHAR(255),
    tipo ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE NOTIFICAÇÕES
-- =====================================================
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT,
    link VARCHAR(255) DEFAULT NULL,
    lida BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_lida (user_id, lida),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERIR DADOS INICIAIS
-- =====================================================

-- Inserir configurações padrão
INSERT INTO configuracoes (chave, valor, descricao, tipo) VALUES
('horario_abertura', '08:00', 'Horário de abertura', 'text'),
('horario_fechamento', '20:00', 'Horário de fechamento', 'text'),
('intervalo_agenda', '30', 'Intervalo entre agendamentos (minutos)', 'number'),
('dias_fechados', '[0]', 'Dias da semana fechados (0=Domingo, 6=Sábado)', 'json'),
('antecedencia_minima', '2', 'Horas de antecedência mínima para agendar', 'number'),
('antecedencia_cancelamento', '2', 'Horas de antecedência mínima para cancelar', 'number'),
('max_agendamentos_dia', '10', 'Máximo de agendamentos por dia', 'number'),
('whatsapp_enabled', 'true', 'Habilitar WhatsApp', 'boolean'),
('email_enabled', 'false', 'Habilitar e-mail', 'boolean'),
('fidelidade_enabled', 'true', 'Habilitar programa de fidelidade', 'boolean'),
('pontos_por_real', '1', 'Pontos ganhos por real gasto', 'number'),
('pontos_para_desconto', '100', 'Pontos necessários para R$ 10 de desconto', 'number'),
('nome_estabelecimento', 'Salão de Beleza XYZ', 'Nome do estabelecimento', 'text'),
('endereco', 'Rua Exemplo, 123 - Centro', 'Endereço', 'text'),
('telefone', '(17) 99999-9999', 'Telefone', 'text')
ON DUPLICATE KEY UPDATE chave = chave;

-- Inserir serviços iniciais
INSERT INTO services (name, description, price, duration, category, active, points_reward) VALUES
('Manicure Simples', 'Manicure tradicional com esmaltação comum', 35.00, 45, 'manicure', TRUE, 35),
('Pedicure Simples', 'Pedicure tradicional com esmaltação comum', 40.00, 60, 'pedicure', TRUE, 40),
('Manicure + Pedicure', 'Combo completo de mãos e pés', 70.00, 90, 'combo', TRUE, 70),
('Unhas em Gel', 'Aplicação de unhas em gel', 80.00, 120, 'manicure', TRUE, 80),
('Esmaltação em Gel', 'Esmaltação especial em gel', 50.00, 60, 'manicure', TRUE, 50),
('Blindagem de Unhas', 'Tratamento de fortalecimento', 60.00, 90, 'tratamento', TRUE, 60),
('Spa dos Pés', 'Tratamento relaxante completo para os pés', 90.00, 90, 'spa', TRUE, 90),
('Design de Unhas', 'Nail art personalizada', 100.00, 120, 'design', TRUE, 100),
('Alongamento de Unhas', 'Alongamento com fibra de vidro', 120.00, 150, 'manicure', TRUE, 120),
('Manutenção de Gel', 'Manutenção de unhas em gel', 60.00, 90, 'manicure', TRUE, 60)
ON DUPLICATE KEY UPDATE name = name;

-- Inserir cupons de exemplo
INSERT INTO cupons (codigo, descricao, tipo, valor, min_value, valid_from, valid_until, active, first_time_only) VALUES
('BEM-VINDO', 'Desconto de boas-vindas para novos clientes', 'percentual', 15.00, 50.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), TRUE, TRUE),
('FIDELIDADE10', '10% de desconto para clientes fiéis', 'percentual', 10.00, 70.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 180 DAY), TRUE, FALSE),
('PRIMEIRA-VEZ', 'R$ 20 OFF na primeira visita', 'fixo', 20.00, 80.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), TRUE, TRUE)
ON DUPLICATE KEY UPDATE codigo = codigo;

-- Inserir pacotes de exemplo
INSERT INTO pacotes (name, description, price, sessions, valid_days, active) VALUES
('Pacote 5 Manicures', '5 sessões de manicure simples com 10% de desconto', 157.50, 5, 90, TRUE),
('Pacote 10 Sessões Combo', '10 sessões de manicure + pedicure com 15% de desconto', 595.00, 10, 180, TRUE),
('Pacote Mensal Premium', '4 sessões de serviços premium', 300.00, 4, 30, TRUE)
ON DUPLICATE KEY UPDATE name = name;

-- =====================================================
-- VIEWS ÚTEIS
-- =====================================================

-- View de agendamentos com informações completas
CREATE OR REPLACE VIEW vw_agendamentos_completos AS
SELECT 
    e.id,
    e.title,
    e.start,
    e.end,
    e.status,
    e.payment_status,
    e.notes,
    e.client_notes,
    u.name AS cliente_nome,
    u.email AS cliente_email,
    u.phone AS cliente_phone,
    u.whatsapp AS cliente_whatsapp,
    s.name AS servico_nome,
    s.price AS servico_preco,
    s.duration AS servico_duracao,
    p.name AS profissional_nome,
    e.created_at,
    e.updated_at
FROM events e
LEFT JOIN users u ON e.user_id = u.id
LEFT JOIN services s ON e.service_id = s.id
LEFT JOIN users p ON e.professional_id = p.id;

-- View de estatísticas de serviços
CREATE OR REPLACE VIEW vw_estatisticas_servicos AS
SELECT 
    s.id,
    s.name,
    s.price,
    COUNT(e.id) AS total_agendamentos,
    SUM(CASE WHEN e.status = 'concluido' THEN 1 ELSE 0 END) AS total_concluidos,
    SUM(CASE WHEN e.status = 'cancelado' THEN 1 ELSE 0 END) AS total_cancelados,
    AVG(CASE WHEN av.nota IS NOT NULL THEN av.nota ELSE NULL END) AS avaliacao_media,
    COUNT(DISTINCT av.id) AS total_avaliacoes
FROM services s
LEFT JOIN events e ON s.id = e.service_id
LEFT JOIN avaliacoes av ON s.id = av.service_id
GROUP BY s.id, s.name, s.price;

-- View de faturamento
CREATE OR REPLACE VIEW vw_faturamento AS
SELECT 
    DATE(e.start) AS data,
    COUNT(*) AS total_agendamentos,
    SUM(CASE WHEN e.status = 'concluido' THEN s.price ELSE 0 END) AS faturamento_dia,
    AVG(s.price) AS ticket_medio
FROM events e
INNER JOIN services s ON e.service_id = s.id
WHERE e.status IN ('agendado', 'concluido')
GROUP BY DATE(e.start)
ORDER BY data DESC;

-- =====================================================
-- TRIGGERS ÚTEIS
-- =====================================================

-- Trigger para adicionar pontos após conclusão
DELIMITER //
CREATE TRIGGER tr_add_pontos_fidelidade
AFTER UPDATE ON events
FOR EACH ROW
BEGIN
    IF NEW.status = 'concluido' AND OLD.status != 'concluido' THEN
        IF NEW.service_id IS NOT NULL THEN
            UPDATE users u
            INNER JOIN services s ON s.id = NEW.service_id
            SET u.points = u.points + s.points_reward
            WHERE u.id = NEW.user_id;
        END IF;
    END IF;
END;//
DELIMITER ;

-- Trigger para registrar histórico automaticamente
DELIMITER //
CREATE TRIGGER tr_historico_status_change
AFTER UPDATE ON events
FOR EACH ROW
BEGIN
    IF NEW.status != OLD.status THEN
        INSERT INTO historico (event_id, acao, descricao, created_at)
        VALUES (
            NEW.id,
            NEW.status,
            CONCAT('Status alterado de "', OLD.status, '" para "', NEW.status, '"'),
            NOW()
        );
    END IF;
END;//
DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================
CREATE INDEX idx_events_date_status ON events(start, status);
CREATE INDEX idx_users_role_active ON users(role, active);
CREATE INDEX idx_services_active_category ON services(active, category);

-- =====================================================
-- FIM DO SCRIPT
-- =====================================================
