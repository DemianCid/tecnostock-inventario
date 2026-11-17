<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$sql = "
    SELECT m.id AS movimiento_id, m.fecha, m.cantidad, m.precio_unitario,
           p.nombre AS producto, u.nombre AS vendedor
    FROM movimientos m
    INNER JOIN usuarios u ON m.usuario_id = u.id
    INNER JOIN productos p ON m.producto_id = p.id
    WHERE m.tipo = 'salida' AND m.precio_unitario IS NOT NULL
    ORDER BY m.id DESC
";

$stmt = $conexion->prepare($sql);
$stmt->execute();
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TecnoStock - Historial de Ventas</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light" style="font-family: 'Inter', sans-serif;">

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold m-0">📊 Historial de Ventas</h3>
                <p class="text-muted small m-0">Registro de dinero ingresado y productos vendidos.</p>
            </div>
            <div>
                <a href="nueva_venta.php" class="btn btn-success btn-sm me-2">🛒 Nueva Venta</a>
                <a href="index.php" class="btn btn-outline-secondary btn-sm">Volver al Panel</a>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>N° Venta</th>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Total</th>
                            <th>Vendedor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ventas)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">Aún no hay ventas registradas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ventas as $v): ?>
                                <tr>
                                    <td><strong>#<?= $v['movimiento_id'] ?></strong></td>
                                    <td><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                                    <td class="fw-medium"><?= htmlspecialchars($v['producto']) ?></td>
                                    <td><span class="badge bg-secondary"><?= $v['cantidad'] ?></span></td>
                                    <td>$<?= number_format((float)$v['precio_unitario'], 2, ',', '.') ?></td>
                                    <td class="fw-bold text-success">$<?= number_format((float)$v['precio_unitario'] * (int)$v['cantidad'], 2, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($v['vendedor']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>