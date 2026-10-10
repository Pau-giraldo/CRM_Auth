CRM TIENDA + TIENDA EN LINEA
Actualizado: 09-10-2026

CRM de escritorio web para una tienda de accesorios (cadenas, relojes y pulseras), con una
tienda publica conectada: los clientes arman un carrito, envian un pedido, y el equipo lo
confirma desde el CRM para convertirlo en venta.


======================================================================
1. REQUISITOS
======================================================================
- Windows con XAMPP (Apache y MySQL/MariaDB encendidos).
- PHP 8.0 o superior (el proyecto usa "match"; XAMPP reciente trae PHP 8.2).
- Navegador actual (las fotos de productos estan en formato WEBP).
- Editor opcional: Visual Studio Code.


======================================================================
2. INSTALACION
======================================================================
2.1 Carpeta del proyecto
    La carpeta DEBE llamarse exactamente CRM_Auth y quedar asi:

        C:\xampp\htdocs\CRM_Auth\dashboard.php
        C:\xampp\htdocs\CRM_Auth\tienda.php
        ...

    CUIDADO: si al descomprimir el ZIP queda una carpeta CRM_Auth dentro de otra
    CRM_Auth (C:\xampp\htdocs\CRM_Auth\CRM_Auth\...), sube todo el contenido un nivel
    y borra la carpeta vacia. Ver seccion 9 (Solucion de problemas).

2.2 Base de datos
    1. Abre XAMPP y pon en Start: Apache y MySQL.
    2. Entra a http://localhost/phpmyadmin
    3. Pulsa "Nueva", escribe el nombre  crm_tienda  (cotejamiento utf8mb4_general_ci)
       y pulsa "Crear".
    4. Haz clic sobre crm_tienda (debe quedar abierta).
    5. Pestana "Importar" > elige  bd_crm_actualizada.sql  > "Importar".
    6. Deben aparecer 10 tablas: campanas, categorias, clientes, detalles_pedido,
       detalles_venta, interacciones, pedidos, productos, usuarios, ventas.

    El archivo bd_crm_actualizada.sql ya incluye las tablas de pedidos y la columna
    "imagen" de productos. No hace falta ejecutar pedidos.sql si importaste ese archivo.
    (pedidos.sql solo sirve para agregar las tablas de pedidos a una base antigua.)

2.3 Conexion
    Archivo: config/database.php
    Por defecto: host localhost, base crm_tienda, usuario root, sin contrasena
    (configuracion normal de XAMPP). Si cambias alguno de esos datos, editalo ahi.

2.4 Abrir el sistema
    CRM (requiere iniciar sesion):  http://localhost/CRM_Auth/
    Tienda publica (sin sesion):    http://localhost/CRM_Auth/tienda.php

2.5 Primer usuario
    Entra a http://localhost/CRM_Auth/auth/registro.php y crea tu usuario.
    Los usuarios de ejemplo del SQL (Ana Torres, Carlos Perez, Laura Gomez) tienen
    contrasenas falsas y NO pueden iniciar sesion. Usa un usuario creado desde Registro.


======================================================================
3. ESTRUCTURA DE ARCHIVOS
======================================================================
CRM_Auth\
  index.php             Entrada: lleva al login o al panel.
  dashboard.php         Panel principal con resumen.
  clientes.php          Lista y cambio de estado de clientes.
  nuevo_cliente.php     Alta de clientes.
  productos.php         Inventario y agregar stock.
  nuevo_producto.php    Alta de productos.
  categorias.php        Crear, editar y eliminar categorias.
  campanas.php          Crear, editar y eliminar campanas comerciales.
  ventas.php            Listado de ventas.
  nueva_venta.php       Registrar una venta (descuenta stock).
  venta_pdf.php         Comprobante PDF de una venta (venta_pdf.php?id=N).
  interacciones.php     Historial de interacciones con filtros.
  nueva_interaccion.php Registrar llamada, correo, WhatsApp o reunion.
  pedidos.php           Bandeja de pedidos que llegan de la tienda.
  pedido.php            Detalle de un pedido: confirmar o cancelar.
  tienda.php            Catalogo publico con carrito.
  carrito.php           Carrito, formulario de pedido y pantalla de gracias.
  pedidos.sql           Solo las tablas de pedidos (para bases antiguas).
  auth\                 login.php, registro.php, logout.php
  config\               database.php (conexion), session.php (sesion y login)
  includes\             header.php, footer.php (paginas antiguas)
                        crm_top.php, crm_bottom.php (paginas nuevas del CRM)
                        tienda_lib.php (funciones de la tienda y configuracion)
  assets\css\           style.css (CRM), tienda.css (tienda publica)
  assets\img\productos\ Fotos de los productos (.webp)


======================================================================
4. MODULOS DEL CRM (requieren iniciar sesion)
======================================================================
- Registro / Login / Cierre de sesion: las contrasenas se guardan con password_hash()
  y se validan con password_verify(). El correo es unico. Roles: Administrador y Vendedor.
- Panel principal: resumen de clientes, productos, ventas e interacciones recientes.
- Clientes: lista con estado del embudo (Contacto Inicial, Propuesta, Negociacion,
  Ganado, Perdido).
- Productos: inventario, alta de productos y "Agregar stock".
- Categorias: crear, editar y eliminar. No deja eliminar una categoria que tenga productos.
- Campanas: nombre, fechas y presupuesto. Al eliminar una campana, las ventas
  asociadas conservan su registro.
- Ventas: se registran desde nueva_venta.php, que guarda la venta en "ventas" y su
  producto en "detalles_venta", y descuenta el stock automaticamente. Cada venta
  tiene comprobante PDF.
- Interacciones: historial de llamadas, correos, WhatsApp y reuniones por cliente.
- Pedidos: ver seccion 6.


======================================================================
5. TIENDA PUBLICA
======================================================================
URL: http://localhost/CRM_Auth/tienda.php  (no requiere sesion)

- Muestra los productos de la base de datos con categoria, precio, foto y estado de
  stock (Disponible / Ultimas N unidades si quedan 5 o menos / Agotado).
- Filtros por categoria y orden por precio.
- Carrito: agregar, cambiar cantidades, quitar. Se guarda en la sesion del navegador.
- Las cantidades se ajustan al stock real y los precios siempre se leen de la base
  de datos, nunca del navegador.
- No hay cobro en linea: el pedido es una solicitud que el equipo confirma despues.

Fotos de productos
  1. Guarda las fotos en  assets\img\productos\  (formato .webp, jpg o png).
  2. En la tabla productos, la columna "imagen" lleva solo el nombre del archivo,
     por ejemplo: cadena-artesanal.webp
  3. Si un producto no tiene foto (o el archivo no existe), se muestra un icono.
  Fotos actuales: cadena-artesanal, cadena-casa, reloj-gshock, reloj-digital,
  pulsera-cuero y pulsera-plata (todas .webp).

Configuracion de la tienda
  Archivo: includes/tienda_lib.php (parte de arriba)
    NOMBRE_TIENDA    Nombre que se muestra en la tienda.
    WHATSAPP_TIENDA  Numero con indicativo, solo digitos (ej. 573001234567). Si lo
                     dejas vacio, no aparece el boton de WhatsApp en la pantalla
                     de gracias.


======================================================================
6. FLUJO DE UN PEDIDO (TIENDA -> CRM)
======================================================================
1. El cliente agrega productos y llena el formulario (nombre, apellido, correo,
   telefono y nota opcional).
2. El sistema crea el cliente en el CRM, o reutiliza el que ya tenga ese correo
   (sin modificar sus datos). Guarda el pedido como PENDIENTE.
   En este momento NO se descuenta stock.
3. En el CRM, menu "Pedidos": los pendientes aparecen primero. En el detalle se puede
   escribir al cliente por WhatsApp y elegir una campana (opcional).
4. "Confirmar y crear venta": crea la venta a nombre del usuario que confirma,
   registra el detalle, descuenta el stock y pasa el cliente a "Ganado".
   Si el stock ya no alcanza, el sistema no deja confirmar e indica que producto falta.
5. "Cancelar pedido": lo marca Cancelado, sin tocar stock.

Proteccion basica contra spam en el formulario: campo trampa, maximo 5 pedidos por
hora y espera de 20 segundos entre pedidos (por visitante).


======================================================================
7. BASE DE DATOS (crm_tienda)
======================================================================
usuarios          Usuarios del CRM (nombre, correo unico, password, rol).
clientes          Clientes y su estado en el embudo.
interacciones     Contactos con el cliente (tipo, nota, usuario, fecha).
categorias        Categorias de producto (Cadenas, Relojes, Pulseras).
productos         Catalogo: categoria, nombre, sku, precio, stock, imagen.
campanas          Campanas comerciales (fechas y presupuesto).
ventas            Cabecera de la venta (cliente, vendedor, campana, total, fecha).
detalles_venta    Productos de cada venta (cantidad y precio unitario).
pedidos           Pedidos de la tienda (estado, total, nota, venta_id al confirmar).
detalles_pedido   Productos de cada pedido (cantidad y precio que vio el cliente).


======================================================================
8. TIPS DE USO CON XAMPP
======================================================================
- Para ver cualquier pagina: Apache y MySQL en verde en XAMPP, y abrir la direccion
  desde el navegador (http://localhost/CRM_Auth/...).
- El boton "Iniciar depuracion" (F5) de Visual Studio Code NO sirve las paginas PHP:
  las sirve Apache. Guarda con Ctrl+S y recarga el navegador con F5 o Ctrl+F5.
- Solo se sirve lo que esta dentro de C:\xampp\htdocs.
- Al cambiar CSS o fotos, recarga con Ctrl+F5 para limpiar la cache.
- Haz respaldos de la base desde phpMyAdmin > crm_tienda > Exportar.


======================================================================
9. SOLUCION DE PROBLEMAS
======================================================================
"Not Found" al abrir el CRM o la tienda
  - Revisa que tienda.php y dashboard.php esten DIRECTAMENTE en
    C:\xampp\htdocs\CRM_Auth\ (sin una segunda carpeta CRM_Auth adentro).
  - config/session.php redirige al login con la ruta fija /CRM_Auth/auth/login.php.
    Por eso la carpeta debe llamarse CRM_Auth y estar directamente en htdocs.
    Si la renombras o la mueves, cambia esa ruta en config/session.php.
  - Si el error sale al entrar sin sesion iniciada, abre directamente
    http://localhost/CRM_Auth/auth/login.php

"#1046 Base de datos no seleccionada" al importar
  - La base crm_tienda no existe o no esta abierta. Crea la base (seccion 2.2) y haz
    clic sobre ella antes de pulsar Importar.

"Error de conexion con la base de datos"
  - MySQL esta apagado o la base no se llama crm_tienda. Revisa config/database.php.

Aviso de phpMyAdmin "Almacenamiento de configuracion... no recibio el OK"
  - Es solo un aviso de funciones extra de phpMyAdmin. No afecta al CRM. Se puede
    ignorar.

No se ven las fotos de la tienda
  - El nombre en la columna "imagen" debe ser identico al archivo (mayusculas y
    extension incluidas) y estar en assets\img\productos\.
  - Prueba una foto directo: http://localhost/CRM_Auth/assets/img/productos/cadena-casa.webp
  - Windows puede ocultar extensiones: activa Ver > Mostrar > Extensiones de nombre
    de archivo para verificar que no quedo como .webp.webp.

Avisos "Warning: Undefined array key" en la tienda
  - Suele deberse a un archivo copiado incompleto o con otra codificacion. Vuelve a
    copiar tienda.php y guardalo como UTF-8.

Los pedidos no aparecen / error al enviar un pedido
  - Verifica que existan las tablas pedidos y detalles_pedido.
  - Verifica que existan includes/tienda_lib.php y assets/css/tienda.css.


======================================================================
10. NOTAS IMPORTANTES Y LIMITACIONES CONOCIDAS
======================================================================
Seguridad (revisar antes de publicar en internet)
  - En auth/registro.php cualquier persona puede elegir el rol Administrador al
    registrarse. Antes de publicar, deja el rol fijo en Vendedor y asigna
    administradores desde la base de datos.
  - Todavia no hay control de permisos por rol: Administrador y Vendedor ven y
    pueden hacer lo mismo en el CRM.
  - La proteccion CSRF existe en el carrito y en el formulario de pedido, pero la
    mayoria de formularios internos del CRM aun no la tienen.
  - config/database.php usa el usuario root sin contrasena: solo apto para desarrollo
    local. En un servidor real crea un usuario propio con contrasena.
  - La proteccion anti-spam de la tienda es basica; si llegan pedidos falsos, habria
    que anadir un captcha.

Inventario y ventas
  - El stock no se reserva al enviar un pedido, solo al confirmarlo. Si dos personas
    piden la ultima unidad, el primero en ser confirmado se la lleva.
  - nueva_venta.php registra un producto por venta; el carrito de la tienda si permite
    varios productos por pedido.
  - Borrar un cliente, usuario o producto elimina en cascada sus ventas (llaves foraneas
    ON DELETE CASCADE de la base actual). Evita borrar registros con ventas o cambia esas
    llaves a RESTRICT.

Datos de ejemplo
  - La venta 4 usa precios de 25.000 y 35.000 para la G-Shock y el reloj digital
    (parecen faltar ceros respecto a los precios del catalogo).
  - Varias ventas apuntan a campanas cuyas fechas no coinciden con la fecha de la venta.
  - Los usuarios de ejemplo tienen contrasenas falsas (ver seccion 2.5).


======================================================================
11. IDEAS PARA SEGUIR
======================================================================
- Permisos por rol (solo Administrador crea productos, campanas y usuarios).
- Registro de usuarios restringido a administradores.
- CSRF en todos los formularios del CRM.
- Venta con varios productos desde el CRM (carrito interno).
- Seguimientos con fecha (recordatorios por cliente) y panel de pendientes.
- Reportes: ventas por mes, productos mas vendidos, retorno por campana.
- Subir la foto del producto desde el formulario de nuevo producto.
- Busqueda, filtros y paginacion en clientes.
- Anular ventas devolviendo el stock y registrar el metodo de pago.
- Exportar a CSV y recuperar contrasena.
