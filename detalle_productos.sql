-- ============================================================
-- DETALLE DE PRODUCTOS (ejecutar UNA vez en phpMyAdmin, base crm_tienda)
-- Agrega descripción y ficha técnica a los productos.
-- ============================================================
USE crm_tienda;

ALTER TABLE productos
  ADD COLUMN descripcion TEXT NULL AFTER nombre,
  ADD COLUMN material VARCHAR(100) NULL,
  ADD COLUMN color VARCHAR(60) NULL,
  ADD COLUMN medidas VARCHAR(100) NULL;

-- TEXTOS DE EJEMPLO: cámbialos por la información real de cada producto.
-- Los campos que dejes vacíos (NULL) simplemente no se muestran en la tienda.
UPDATE productos SET
  descripcion = 'Collar artesanal tejido a mano con cuentas de madera. Una pieza ligera y con mucho estilo.',
  material = 'Hilo tejido y cuentas de madera', color = 'Crema y café'
WHERE sku = 'CAD-ART';

UPDATE productos SET
  descripcion = 'Cadenas delgadas en tono dorado con detalle de perlas. Se pueden usar juntas o por separado.',
  material = 'Metal dorado', color = 'Dorado'
WHERE sku = 'CAD-CAS';

UPDATE productos SET
  descripcion = 'Reloj digital resistente, pensado para el uso diario y el deporte.',
  material = 'Resina', color = 'Negro y azul'
WHERE sku = 'REL-GSH';

UPDATE productos SET
  descripcion = 'Reloj con pantalla táctil y correa de silicona. Cómodo para llevar todo el día.',
  material = 'Silicona y aleación', color = 'Negro'
WHERE sku = 'REL-DIG';

UPDATE productos SET
  descripcion = 'Pulsera de cuero trenzado con cierre de acero. Combina con cualquier estilo.',
  material = 'Cuero y acero inoxidable', color = 'Negro y dorado'
WHERE sku = 'PUL-CUE';

UPDATE productos SET
  descripcion = 'Pulsera de eslabones en plata con acabado brillante.',
  material = 'Plata', color = 'Plata'
WHERE sku = 'PUL-PLA';
