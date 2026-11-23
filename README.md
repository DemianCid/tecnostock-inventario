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

