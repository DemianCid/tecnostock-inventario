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
    $producto_id = (int)$_POST['producto_id'];
    $cantidad = (int)$_POST['cantidad'];

    if ($producto_id > 0 && $cantidad > 0) {
        $stmt = $conexion->prepare("SELECT * FROM productos WHERE id = :id AND estado = 'activo'");
        $stmt->execute([':id' => $producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($producto) {
            if ($producto['stock_actual'] >= $cantidad) {
                try {
                    $conexion->beginTransaction();

                    $subtotal = $producto['precio'] * $cantidad;

                    // 1. Registrar Venta
                    $stmtVenta = $conexion->prepare("INSERT INTO ventas (usuario_id, total) VALUES (:u_id, :total)");
                    $stmtVenta->execute([
                        ':u_id' => $_SESSION['usuario_id'],
                        ':total' => $subtotal
                    ]);
                    $venta_id = $conexion->lastInsertId();

                    // 2. Registrar Detalle
                    $stmtDetalle = $conexion->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (:v_id, :p_id, :cant, :precio, :sub)");
                    $stmtDetalle->execute([
                        ':v_id' => $venta_id,
                        ':p_id' => $producto_id,
                        ':cant' => $cantidad,
                        ':precio' => $producto['precio'],
                        ':sub' => $subtotal
                    ]);

                    // 3. Descontar Stock
                    $stmtStock = $conexion->prepare("UPDATE productos SET stock_actual = stock_actual - :cant WHERE id = :id");
                    $stmtStock->execute([':cant' => $cantidad, ':id' => $producto_id]);

                    // 4. Registrar en Historial de Movimientos
                    $stmtMov = $conexion->prepare("INSERT INTO movimientos (producto_id, usuario_id, tipo, cantidad) VALUES (:p_id, :u_id, 'salida', :cant)");
                    $stmtMov->execute([
                        ':p_id' => $producto_id,
                        ':u_id' => $_SESSION['usuario_id'],
                        ':cant' => $cantidad
                    ]);

                    $conexion->commit();
                    $mensaje = "¡Venta #" . $venta_id . " registrada con éxito!";
                    $tipo_alerta = "success";
                } catch (Exception $e) {
                    $conexion->rollBack();
                    $mensaje = "Error al procesar la venta: " . $e->getMessage();
                    $tipo_alerta = "danger";
                }
            } else {
                $mensaje = "Stock insuficiente. Solo quedan {$producto['stock_actual']} unidades.";
                $tipo_alerta = "warning";
            }
        } else {
            $mensaje = "Producto no encontrado o inactivo.";
            $tipo_alerta = "danger";
        }
    } else {
        $mensaje = "Selecciona un producto y una cantidad válida.";
        $tipo_alerta = "warning";
    }
}

$productos = $conexion->query("SELECT * FROM productos WHERE estado = 'activo' AND stock_actual > 0 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
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
                <?= $mensaje ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
            <form action="nueva_venta.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-medium">Seleccionar Producto</label>
                    <select name="producto_id" class="form-select" required>
                        <option value="">-- Selecciona un producto --</option>
                        <?php foreach ($productos as $p): ?>
                            <option value="<?= $p['id'] ?>">
                                <?= htmlspecialchars($p['nombre']) ?> (Stock: <?= $p['stock_actual'] ?>) - $<?= number_format($p['precio'], 0, '', '.') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">Cantidad</label>
                    <input type="number" name="cantidad" class="form-control" min="1" value="1" required>
                </div>

                <button type="submit" class="btn btn-success w-100 fw-bold">Completar Venta</button>
            </form>
        </div>
    </div>

</body>
</html>