-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS sistema_quejas_salud;
USE sistema_quejas_salud;

-- Tabla para ciudades
CREATE TABLE IF NOT EXISTS ciudades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

-- Tabla para EPS
CREATE TABLE IF NOT EXISTS eps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

-- Tabla para tipos de quejas
CREATE TABLE IF NOT EXISTS tipos_queja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT
);

-- Tabla para almacenar las quejas
CREATE TABLE IF NOT EXISTS quejas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_paciente VARCHAR(100) NOT NULL,
    documento_identidad VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    ciudad_id INT NOT NULL,
    eps_id INT NOT NULL,
    tipo_queja_id INT NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Pendiente', 'En revisión', 'Resuelta', 'Rechazada') DEFAULT 'Pendiente',
    FOREIGN KEY (ciudad_id) REFERENCES ciudades(id),
    FOREIGN KEY (eps_id) REFERENCES eps(id),
    FOREIGN KEY (tipo_queja_id) REFERENCES tipos_queja(id)
);

-- Insertar algunas ciudades de ejemplo
INSERT INTO ciudades (nombre) VALUES 
('Bogotá'), ('Medellín'), ('Cali'), ('Barranquilla'), ('Cartagena'), 
('Bucaramanga'), ('Pereira'), ('Santa Marta'), ('Manizales'), ('Pasto');

-- Insertar algunas EPS de ejemplo
INSERT INTO eps (nombre) VALUES 
('Nueva EPS'), ('Sura EPS'), ('Sanitas'), ('Compensar'), ('Salud Total'), 
('Famisanar'), ('Coosalud'), ('Mutual SER'), ('Comfenalco Valle'), ('Emssanar');

-- Insertar tipos de quejas comunes en salud
INSERT INTO tipos_queja (nombre, descripcion) VALUES 
('Demora en la atención', 'Tiempo excesivo para recibir atención médica'),
('Negación de servicio', 'Rechazo a prestar un servicio de salud solicitado'),
('Mala atención del personal', 'Trato inadecuado por parte del personal médico o administrativo'),
('Medicamentos no entregados', 'No entrega de medicamentos formulados'),
('Error en diagnóstico', 'Diagnóstico médico equivocado'),
('Cobros indebidos', 'Cobros no justificados o errores en la facturación'),
('Problemas con citas', 'No disponibilidad o cancelación de citas médicas'),
('Infraestructura deficiente', 'Problemas con instalaciones, equipos o condiciones sanitarias'),
('Trámites excesivos', 'Procedimientos administrativos complicados o innecesarios'),
('Otro', 'Otros motivos no listados anteriormente');
