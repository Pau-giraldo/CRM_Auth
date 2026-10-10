-- ============================================================
-- PEDIDOS DE LA TIENDA  (ejecutar UNA vez en phpMyAdmin, base crm_tienda)
-- Funciona con tu crm_tienda.sql original; no necesita mejoras.sql.
-- ============================================================
USE crm_tienda;

CREATE TABLE IF NOT EXISTS pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  estado ENUM('Pendiente','Confirmado','Cancelado') NOT NULL DEFAULT 'Pendiente',
  total DECIMAL(10,2) NOT NULL,
  nota VARCHAR(500) NULL,
  venta_id INT NULL,                       -- se llena al confirmar: enlaza el pedido con la venta creada
  fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
  FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE SET NULL,
  INDEX idx_pedidos_estado (estado, fecha_pedido)
);

CREATE TABLE IF NOT EXISTS detalles_pedido (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,  -- precio que vio el cliente al pedir
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT
);

-- Si aún no tienes la columna de fotos (paso anterior), ejecuta también:
-- ALTER TABLE productos ADD COLUMN imagen VARCHAR(150) NULL;
