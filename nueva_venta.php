<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$mensaje = '';
$tipo_alerta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id = filter_var($_POST['producto_id'] ?? null, FILTER_VALIDATE_INT);
    $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT);

    if ($producto_id !== false && $producto_id > 0 && $cantidad !== false && $cantidad > 0) {
        try {
            $conexion->beginTransaction();

            $stmt = $conexion->prepare(
                "SELECT id, nombre, precio, stock_actual FROM productos
                 WHERE id = :id AND estado = 'activo' FOR UPDATE"
            );
            $stmt->execute([':id' => $producto_id]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$producto) {
                $conexion->rollBack();
                $mensaje = 'Producto no encontrado o inactivo.';
                $tipo_alerta = 'danger';
            } elseif ((int)$producto['stock_actual'] < $cantidad) {
                $conexion->rollBack();
                $mensaje = "Stock insuficiente. Solo quedan {$producto['stock_actual']} unidades.";
                $tipo_alerta = 'warning';
            } else {
                $actualizar = $conexion->prepare(
                    "UPDATE productos SET stock_actual = stock_actual - :cantidad
                     WHERE id = :id AND stock_actual >= :cantidad_disponible"
                );
                $actualizar->execute([
                    ':cantidad' => $cantidad,
                    ':id' => $producto_id,
                    ':cantidad_disponible' => $cantidad
                ]);

                if ($actualizar->rowCount() !== 1) {
                    throw new RuntimeException('No fue posible reservar el stock disponible.');
                }

                $movimiento = $conexion->prepare(
                    "INSERT INTO movimientos
                        (producto_id, usuario_id, tipo, cantidad, observacion, precio_unitario)
                     VALUES (:producto_id, :usuario_id, 'salida', :cantidad, 'Venta', :precio)"
                );
                $movimiento->execute([
                    ':producto_id' => $producto_id,
                    ':usuario_id' => $_SESSION['usuario_id'],
                    ':cantidad' => $cantidad,
                    ':precio' => $producto['precio']
                ]);

                $conexion->commit();
                $mensaje = 'Venta registrada correctamente.';
                $tipo_alerta = 'success';
            }
        } catch (PDOException | RuntimeException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            $mensaje = 'No se pudo procesar la venta. Inténtalo nuevamente.';
            $tipo_alerta = 'danger';
        }
    } else {
        $mensaje = "Selecciona un producto y una cantidad válida.";
        $tipo_alerta = "warning";
    }
}

$stmtProductos = $conexion->prepare(
    "SELECT id, nombre, precio, stock_actual FROM productos
     WHERE estado = 'activo' AND stock_actual > 0 ORDER BY nombre ASC"
);
$stmtProductos->execute();
$productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TecnoStock - Registrar Venta</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light" style="font-family: 'Inter', sans-serif;">

    <div class="container my-5" style="max-width: 550px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold m-0">🛒 Registrar Venta</h3>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Volver al Panel</a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
            <form action="nueva_venta.php" method="POST" id="form-venta">
                <div class="mb-3">
                    <label class="form-label fw-medium">Seleccionar Producto</label>
                    <select name="producto_id" class="form-select" required>
                        <option value="">-- Selecciona un producto --</option>
                        <?php foreach ($productos as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" data-stock="<?= (int)$p['stock_actual'] ?>">
                                <?= htmlspecialchars($p['nombre']) ?> (Stock: <?= $p['stock_actual'] ?>) - $<?= number_format((float)$p['precio'], 2, ',', '.') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">Cantidad</label>
                    <input type="number" name="cantidad" id="cantidad" class="form-control" min="1" value="1" required>
                    <div class="form-text" id="aviso-stock" aria-live="polite"></div>
                </div>

                <button type="submit" id="submit-venta" class="btn btn-success w-100 fw-bold">Completar Venta</button>
            </form>
        </div>
    </div>

    <script>
        const productoSelect = document.querySelector('[name="producto_id"]');
        const cantidadInput = document.getElementById('cantidad');
        const avisoStock = document.getElementById('aviso-stock');
        const botonVenta = document.getElementById('submit-venta');

        function validarStock() {
            const option = productoSelect.options[productoSelect.selectedIndex];
            const disponible = Number(option.dataset.stock || 0);
            cantidadInput.max = disponible || '';
            const excede = !productoSelect.value || Number(cantidadInput.value) > disponible;
            avisoStock.textContent = excede && productoSelect.value
                ? `Stock insuficiente: disponibles ${disponible}.`
                : '';
            botonVenta.disabled = excede;
        }

        productoSelect.addEventListener('change', validarStock);
        cantidadInput.addEventListener('input', validarStock);
        document.getElementById('form-venta').addEventListener('submit', function (event) {
            validarStock();
            if (botonVenta.disabled || Number(cantidadInput.value) < 1) {
                event.preventDefault();
            }
        });
        validarStock();
    </script>
</body>
</html>