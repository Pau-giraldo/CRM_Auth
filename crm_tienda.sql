-- BASE DE DATOS CRM TIENDA
-- Basada en el documento entregado
CREATE DATABASE IF NOT EXISTS crm_tienda;
USE crm_tienda;

CREATE TABLE IF NOT EXISTS usuarios (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL,
 correo VARCHAR(150) UNIQUE NOT NULL,
 password VARCHAR(255) NOT NULL,
 rol ENUM('Administrador','Vendedor') NOT NULL,
 fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS clientes (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL,
 apellido VARCHAR(100) NOT NULL,
 correo VARCHAR(150) UNIQUE NOT NULL,
 telefono VARCHAR(20),
 empresa VARCHAR(150),
 estado ENUM('Contacto Inicial','Propuesta','Negociación','Ganado','Perdido') DEFAULT 'Contacto Inicial',
 fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS interacciones (
 id INT AUTO_INCREMENT PRIMARY KEY,
 cliente_id INT NOT NULL,
 usuario_id INT NOT NULL,
 tipo ENUM('Llamada','Correo','WhatsApp','Reunión') NOT NULL,
 nota TEXT NOT NULL,
 fecha_interaccion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS categorias (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL,
 descripcion TEXT
);

CREATE TABLE IF NOT EXISTS productos (
 id INT AUTO_INCREMENT PRIMARY KEY,
 categoria_id INT NOT NULL,
 nombre VARCHAR(150) NOT NULL,
 sku VARCHAR(50) UNIQUE,
 precio DECIMAL(10,2) NOT NULL,
 stock INT DEFAULT 0,
 FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS campanas (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(150) NOT NULL,
 fecha_inicio DATE,
 fecha_fin DATE,
 presupuesto DECIMAL(10,2)
);

CREATE TABLE IF NOT EXISTS ventas (
 id INT AUTO_INCREMENT PRIMARY KEY,
 cliente_id INT NOT NULL,
 usuario_id INT NOT NULL,
 campana_id INT,
 total DECIMAL(10,2) NOT NULL,
 fecha_venta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
 FOREIGN KEY (campana_id) REFERENCES campanas(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS detalles_venta (
 id INT AUTO_INCREMENT PRIMARY KEY,
 venta_id INT NOT NULL,
 producto_id INT NOT NULL,
 cantidad INT NOT NULL,
 precio_unitario DECIMAL(10,2) NOT NULL,
 FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
 FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- DATOS DE EJEMPLO
INSERT INTO usuarios (nombre,correo,password,rol) VALUES
('Ana Torres','ana.torres@crmtienda.com','$2y$10$hashDeEjemplo1','Administrador'),
('Carlos Pérez','carlperez@crmtienda.com','$2y$10$hashDeEjemplo2','Vendedor'),
('Laura Gómez','laura.gomez@crmtienda.com','$2y$10$hashDeEjemplo3','Vendedor');

INSERT INTO clientes (nombre,apellido,correo,telefono,empresa,estado) VALUES
('Sebastián','Monsalve','sebastian@gmail.com','3001112233','Innovatech','Negociación'),
('Mariana','Ríos','mariana.rios@gmail.com','3002223344','Grupo Andino','Propuesta'),
('Jorge','Salazar','jorge.salazar@yahoo.com','3003334455','Salazar & Cía','Contacto Inicial'),
('Valentina','Rueda','valentina.rueda@gmail.com','3004445566',NULL,'Ganado'),
('Andrés','Castaño','andres.castano@hotmail.com','3005556677','Castaño SAS','Perdido'),
('Natalia','Ospina','natalia.ospina@gmail.com','3006667788','Ospina Digital','Negociación');

INSERT INTO categorias (nombre,descripcion) VALUES
('Cadenas',NULL),('Relojes',NULL),('Pulseras',NULL);

INSERT INTO campanas (nombre,fecha_inicio,fecha_fin,presupuesto) VALUES
('Cadena plata','2026-11-20','2026-11-30',5000000.00),
('Reloj Casio','2026-01-05','2026-02-15',3000000.00),
('Pulsera Oro','2026-12-01','2026-12-24',4000000.00);

INSERT INTO productos (categoria_id,nombre,sku,precio,stock) VALUES
(1,'Cadena Artesanal','CAD-ART',150000.00,3),
(1,'Cadena de la Casa','CAD-CAS',180000.00,20),
(2,'Reloj G-Shock','REL-GSH',250000.00,4),
(2,'Reloj Digital','REL-DIG',350000.00,15),
(3,'Pulsera Cuero','PUL-CUE',60000.00,50),
(3,'Pulsera Plata','PUL-PLA',500000.00,2);

INSERT INTO interacciones (cliente_id,usuario_id,tipo,nota,fecha_interaccion) VALUES
(1,2,'Llamada','Cliente interesado en Cadena plata, pidió cotización.','2026-08-01 10:00:00'),
(1,2,'Correo','Se envió propuesta comercial con descuento del 10%.','2026-08-05 15:30:00'),
(2,3,'WhatsApp','Cliente pidió más información Reloj G-Shock.','2026-08-10 09:15:00'),
(3,2,'Reunión','Primera reunión de presentación de la empresa.','2026-08-12 14:00:00'),
(4,3,'Llamada','Confirmó compra, se coordina fecha de pago.','2026-07-20 11:00:00'),
(5,2,'Correo','Cliente decidió no continuar, prefiere otra opción.','2026-06-15 16:45:00');

INSERT INTO ventas (cliente_id,usuario_id,campana_id,total,fecha_venta) VALUES
(4,3,2,500000.00,'2026-07-21 12:00:00'),
(1,2,NULL,150000.00,'2026-08-06 09:00:00'),
(2,3,1,60000.00,'2026-08-15 10:30:00'),
(6,2,1,535000.00,'2026-08-18 17:00:00');

INSERT INTO detalles_venta (venta_id,producto_id,cantidad,precio_unitario) VALUES
(1,6,1,500000.00),
(2,1,1,150000.00),
(3,5,1,60000.00),
(4,1,1,150000.00),
(4,3,4,25000.00),
(4,4,3,35000.00),
(4,5,2,60000.00);

UPDATE ventas v
SET v.total=(SELECT SUM(dv.cantidad*dv.precio_unitario)
            FROM detalles_venta dv WHERE dv.venta_id=v.id)
WHERE v.id=4;
