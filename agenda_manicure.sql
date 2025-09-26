CREATE DATABASE IF NOT EXISTS agenda_manicure;

USE agenda_manicure;

CREATE TABLE
    IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        email VARCHAR(100) UNIQUE,
        password VARCHAR(255),
        role ENUM ('cliente', 'dono') DEFAULT 'cliente'
    );

CREATE TABLE
    IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        price DECIMAL(10, 2)
    );

CREATE TABLE
    IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service VARCHAR(100),
        date DATE,
        time TIME,
        end_time TIME,
        status ENUM ('agendado', 'atendido') DEFAULT 'agendado'
    );