CREATE DATABASE IF NOT EXISTS agenda_manicure;

USE agenda_manicure;

-- Tabela de usuários
CREATE TABLE
    IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM ('cliente', 'dono') DEFAULT 'cliente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- Tabela de serviços
CREATE TABLE
    IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        duration INT DEFAULT 60 COMMENT 'Duração em minutos',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- Tabela de eventos/agendamentos
CREATE TABLE
    IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        start DATETIME NOT NULL,
        end DATETIME NOT NULL,
        status ENUM ('agendado', 'concluido', 'cancelado') DEFAULT 'agendado',
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
        INDEX idx_start (start),
        INDEX idx_status (status),
        INDEX idx_user (user_id)
    );

-- Inserir alguns serviços de exemplo (se não existirem)
INSERT INTO
    services (name, price, duration)
VALUES
    ('Manicure Simples', 35.00, 45),
    ('Pedicure Simples', 40.00, 60),
    ('Manicure + Pedicure', 70.00, 90),
    ('Unhas em Gel', 80.00, 120),
    ('Esmaltação em Gel', 50.00, 60),
    ('Blindagem de Unhas', 60.00, 90),
    ('Spa dos Pés', 90.00, 90),
    ('Design de Unhas', 100.00, 120) ON DUPLICATE KEY
UPDATE name = name;