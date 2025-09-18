CREATE DATABASE IF NOT EXISTS `manicure_agenda` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `manicure_agenda`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `senha_hash` VARCHAR(255) NOT NULL,
  `tipo` ENUM('cliente','dono') NOT NULL DEFAULT 'cliente',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `services` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(150) NOT NULL,
  `valor` DECIMAL(10,2) DEFAULT NULL,
  `descricao` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `service_id` INT UNSIGNED NULL,
  `client_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `date` DATE NOT NULL,
  `time` TIME NOT NULL,
  `notes` TEXT,
  `status` ENUM('agendado','confirmado','cancelado','concluido') NOT NULL DEFAULT 'agendado',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Exemplo: inserir um serviço inicial
INSERT INTO services (nome, valor, descricao) VALUES
('Manicure Simples', 30.00, 'Corte e limpeza das unhas com esmaltação simples'),
('Pedicure', 40.00, 'Cuidado dos pés e esmaltação'),
('Alongamento de Unhas', 80.00, 'Alongamento em gel ou fibra');

-- OBS: Crie um usuário 'dono' manualmente depois de registrar via register.php ou atualize o campo `tipo` no banco:
-- UPDATE users SET tipo='dono' WHERE email='seu-email@exemplo.com';
