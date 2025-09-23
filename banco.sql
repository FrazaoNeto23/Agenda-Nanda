CREATE DATABASE IF NOT EXISTS agenda_manicure CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agenda_manicure;

CREATE TABLE IF NOT EXISTS appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NULL,
  service VARCHAR(100) NOT NULL,
  date DATE NOT NULL,
  time TIME NOT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('dono', 'cliente') NOT NULL DEFAULT 'cliente'
);

CREATE TABLE appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  client_name VARCHAR(100) NOT NULL,
  date DATE NOT NULL,
  time TIME NOT NULL,
  service VARCHAR(100) NOT NULL,
  status ENUM('agendado','atendido') DEFAULT 'agendado',
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
);

-------------------------------------------------

CREATE DATABASE IF NOT EXISTS agenda_manicure CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agenda_manicure;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('dono','cliente') NOT NULL DEFAULT 'cliente'
);

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    client_name VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    service VARCHAR(100) NOT NULL,
    status ENUM('agendado','atendido') DEFAULT 'agendado',
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (username,password,role) VALUES 
('dono','$2y$10$GQZQ3ZpJvWro7tF.LgT4U.GH8eJx3yQkRtQ4Jb0uYpYZ0QbYPo1aC','dono'),
('cliente','$2y$10$7vO0XUlGk9s/pNskNnJvQeZz2p.0D0eQ7KUKl3XlknuTvHrb2cE7e','cliente');

INSERT INTO appointments (client_id,client_name,date,time,service,status) VALUES
(2,'Maria Silva','2025-09-24','09:00:00','Manicure Simples','agendado'),
(2,'Maria Silva','2025-09-25','14:00:00','Pedicure','agendado');
