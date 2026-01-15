-- Charset & Engine
CREATE DATABASE IF NOT EXISTS kms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kms;

-- Nutzer & Rollen
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('Admin','Projektleiter','Techniker','Mitarbeiter','Lernender') NOT NULL DEFAULT 'Mitarbeiter',
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Beispiel-Admin (Passwort bitte sofort ändern!)
-- Passwort = Admin123!
INSERT INTO users (email, password_hash, role) VALUES
('admin-kms@bernauer.ch', '$2y$10$yqI1qQczxJmM8Gvw7sVg9eN0eWiVd9lJr0y8XGzJmCk1lJXqQvRie', 'Admin');
