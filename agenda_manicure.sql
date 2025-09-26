CREATE DATABASE IF NOT EXISTS agenda_manicure CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agenda_manicure;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    role ENUM('cliente','dono') DEFAULT 'cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (nome, email, senha, role) 
VALUES ('Administrador', 'admin@agenda.com', 
        '$2y$10$8Fv9TeN/cGV6sYTxKX5aeOcHExMEqgHcYuwWCHb25VGu2hYErKhm2', 'dono');

CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(100) NOT NULL,
    service VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    end_time TIME DEFAULT NULL,
    status ENUM('agendado','atendido') DEFAULT 'agendado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO appointments (client_name, service, date, time, end_time, status)
VALUES
('Maria Souza', 'Manicure', '2025-09-28', '14:00:00', '15:00:00', 'agendado'),
('Joana Lima', 'Pedicure', '2025-09-29', '09:30:00', '10:30:00', 'atendido');
