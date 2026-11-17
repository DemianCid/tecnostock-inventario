# TecnoStock

TecnoStock es un MVP web para controlar el inventario de una tienda de tecnología. Está construido con PHP, PDO y MySQL/MariaDB, y pensado para ejecutarse localmente con XAMPP.

## Problema

El registro manual de productos y movimientos dificulta conocer las existencias reales, identificar reposiciones necesarias y saber quién realizó cada cambio. TecnoStock centraliza el catálogo y conserva un historial relacionado de entradas y salidas.

## Alcance del MVP

- Inicio de sesión y registro de usuarios.
- Alta, edición, búsqueda y desactivación lógica de productos.
- Catálogo de categorías.
- Registro de entradas, salidas y ventas en el historial de movimientos.
- Validación de stock para impedir existencias negativas.
- Aviso visible para productos cuyo stock alcanza o queda bajo el mínimo.
- Consulta del historial de movimientos y de las ventas registradas.

El sistema no incluye roles/permisos diferenciados, compras a proveedores, facturación tributaria, pagos, ni múltiples bodegas.

## Actor principal

**Encargado/a de inventario**: inicia sesión y mantiene el catálogo; registra entradas, salidas y ventas; consulta alertas e historial. El MVP también permite crear una cuenta desde la pantalla de registro.

## Requerimientos funcionales

| Código | Requerimiento |
|---|---|
| RF1 | El sistema permite registrar usuarios e iniciar/cerrar sesión con contraseñas almacenadas como hash. |
| RF2 | El usuario puede crear productos con código único, nombre, categoría, precio, stock actual y stock mínimo. |
| RF3 | El usuario puede buscar productos por código, nombre o categoría y consultar su estado y existencias. |
| RF4 | El usuario puede editar los datos de un producto sin eliminar su historial. |
| RF5 | El usuario puede desactivar/reactivar productos; la desactivación es lógica y conserva los movimientos relacionados. |
| RF6 | El usuario puede registrar entradas y salidas, que actualizan el stock y quedan asociadas al producto, usuario y fecha. |
| RF7 | El usuario puede registrar una venta como movimiento de salida con precio unitario histórico; las ventas se consultan aparte del resto de movimientos. |
| RF8 | El sistema muestra una alerta cuando `stock_actual <= stock_minimo` y bloquea salidas que excedan el stock disponible. |

## Reglas de negocio

1. Cada producto tiene un código único y pertenece a una categoría existente.
2. Precio, stock actual y stock mínimo no pueden ser negativos; la cantidad de un movimiento debe ser mayor que cero.
3. Las salidas se validan en el navegador para orientar al usuario y nuevamente en PHP. La operación usa una transacción y bloqueo de fila; nunca permite dejar el stock negativo.
4. Una venta se registra en `movimientos` como `salida` y guarda el precio unitario vigente para que el total histórico no dependa de cambios posteriores al precio del producto.
5. Se considera stock bajo cuando `stock_actual <= stock_minimo`.
6. Los productos con historial no se borran físicamente. Se marcan `inactivo`; las claves foráneas también restringen la eliminación de productos y usuarios referenciados.
7. Las consultas PHP usan PDO y sentencias preparadas con parámetros.

## Requisitos

- XAMPP con Apache, PHP 8.0 o posterior y MySQL/MariaDB.
- Extensión PDO MySQL habilitada en PHP.
- Navegador web actualizado.

## Instalación en XAMPP

1. Copia la carpeta del proyecto en `C:\xampp\htdocs\tecnostock-inventario`.
2. Abre el panel de XAMPP e inicia **Apache** y **MySQL**.
3. Entra a `http://localhost/phpmyadmin`.
4. Selecciona **Importar**, elige `tecnostock.sql` y ejecuta la importación. El script crea la base `tecnostock`, las cuatro tablas relacionales y datos de prueba. Si ya existe una base con el esquema antiguo, respalda primero sus datos y migra o renómbrala antes de importar; el script no elimina bases existentes.
5. Revisa las credenciales de conexión en `conexion.php`. La configuración inicial corresponde a XAMPP local (`localhost`, usuario `root`, contraseña vacía). Si tu instalación tiene otra contraseña, actualiza `$user` y `$pass`.
6. Abre `http://localhost/tecnostock-inventario/login.php`.

**Cuenta de prueba:** `admin@tecnostock.cl` / `123456`. Cambia esta clave y las credenciales de base de datos antes de exponer la aplicación fuera de un entorno local.

El SQL deja un producto con stock `2` y mínimo `5` para comprobar la alerta de stock bajo, además de otro producto con existencias normales. El script puede ejecutarse nuevamente sin reemplazar las existencias actuales de esos productos.

## Estructura de datos

- `usuarios`: cuentas y hash de contraseña.
- `categorias`: clasificación de productos.
- `productos`: catálogo, precios, existencias, mínimo y estado.
- `movimientos`: entradas, salidas y ventas; relaciona producto y usuario, registra fecha y puede guardar el precio unitario histórico de una venta.

## Pruebas funcionales

Casos ejecutados con PHP 8.2 de XAMPP, una instancia MariaDB 10.4 temporal y aislada, y el navegador integrado. Las operaciones se comprobaron también directamente en la base de datos:

| Caso | Pasos | Resultado esperado | Verificación |
|---|---|---|---|
| Flujo normal | Inicia sesión; registra una entrada de 3 unidades para el SSD; vende una unidad; abre Historial y Ventas. | Stock del SSD pasa de 20 a 22; se guardan dos movimientos y la venta aparece con su precio histórico. | **Aprobado:** se verificaron los registros y existencias en la base, y ambas vistas mostraron los movimientos. |
| Stock insuficiente | Intenta vender y registrar como salida 3 unidades de `PER-001`, que tiene 2 disponibles. Se repite enviando POST directo para saltarse JavaScript. | JavaScript bloquea ambas acciones e informa las 2 disponibles; PHP también rechaza ambas solicitudes. Stock permanece en 2 y no se insertan movimientos. | **Aprobado:** se comprobó el bloqueo en navegador, los mensajes del servidor y el estado persistido. |
| Alerta de stock bajo | Abre el inventario después de importar el SQL y localiza `PER-001` (stock 2, mínimo 5). | Se muestra claramente **Stock Bajo (Mín: 5)** porque `2 <= 5`. | **Aprobado:** alerta visible en el catálogo con el producto inicial de prueba. |
| Desactivación lógica | Desactiva un producto que tiene movimientos y luego vuelve a activarlo. | El producto cambia de estado sin borrarse; sus movimientos permanecen consultables. | **Aprobado:** se verificó estado y permanencia de los dos movimientos asociados. |
| Reimportación de datos | Ejecuta `tecnostock.sql` nuevamente después del flujo normal. | Los datos iniciales permanecen disponibles sin sobrescribir las existencias ni el historial operativo. | **Aprobado:** el stock y el conteo de movimientos no cambiaron al repetir la importación. |

Para comprobar sintaxis PHP localmente, desde PowerShell ejecuta:

```powershell
Get-ChildItem -Filter *.php | ForEach-Object { & 'C:\xampp\php\php.exe' -l $_.FullName }
```
