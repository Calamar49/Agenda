-- ============================================================================
-- AGENDA WEB DINÁMICA - ESQUEMA DE BASE DE DATOS
-- ============================================================================
-- Laboratorio: Servidores de Red sobre Fedora Server 43
-- Servidor de Base de Datos: 192.168.56.105 (MariaDB)
-- Servidor Web (Apache/PHP): 192.168.56.104
-- Base de datos: uacdb
-- ============================================================================

-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS uacdb
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE uacdb;

-- ============================================================================
-- Tabla: contactos
-- Almacena la información de los contactos de la agenda.
-- ============================================================================
CREATE TABLE IF NOT EXISTS contactos (
    id              INT             AUTO_INCREMENT PRIMARY KEY,
    nombres         VARCHAR(150)    NOT NULL,
    genero          ENUM('Masculino', 'Femenino', 'Otro') NOT NULL,
    fecha_nacimiento DATE           NOT NULL,
    telefono        VARCHAR(20)     NOT NULL,
    email           VARCHAR(150)    NOT NULL,
    linkedin        VARCHAR(255)    DEFAULT NULL,
    tipo_sangre     ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índice para búsquedas por email (posible campo único en escenarios reales)
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Datos de prueba (opcional, descomentar para insertar)
-- ============================================================================
-- INSERT INTO contactos (nombres, genero, fecha_nacimiento, telefono, email, linkedin, tipo_sangre) VALUES
-- ('Carlos Mendoza', 'Masculino', '1995-03-15', '+52 555 123 4567', 'carlos.mendoza@email.com', 'https://linkedin.com/in/carlosmendoza', 'O+'),
-- ('María García', 'Femenino', '1998-07-22', '+52 555 987 6543', 'maria.garcia@email.com', 'https://linkedin.com/in/mariagarcia', 'A+'),
-- ('Alex Rivera', 'Otro', '2000-11-08', '+52 555 456 7890', 'alex.rivera@email.com', NULL, 'B-');
