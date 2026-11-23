<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Consultar historial de movimientos asociando producto y usuario
$sql = "
    SELECT m.*, p.nombre AS producto_nombre, p.codigo AS producto_codigo, u.nombre AS usuario_nombre
    FROM movimientos m
    LEFT JOIN productos p ON m.producto_id = p.id
    LEFT JOIN usuarios u ON m.usuario_id = u.id
    ORDER BY m.fecha DESC
";

$stmt = $conexion->prepare($sql);
$stmt->execute();
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Historial de Movimientos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .card-custom { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark py-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">TecnoStock</a>
            <a href="index.php" class="btn btn-outline-light btn-sm">Volver al Inventario</a>
        </div>
    </nav>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Historial de Entradas y Salidas</h3>
                <p class="text-muted small mb-0">Registro detallado de movimientos de inventario y fechas de retiro.</p>
            </div>
            <a href="registrar_movimiento.php" class="btn btn-primary btn-sm">+ Registrar Movimiento</a>
        </div>

        <div class="card card-custom overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Responsable</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimientos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No hay movimientos registrados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movimientos as $m): ?>
                                <tr>
                                    <td><small class="fw-semibold text-muted"><?= date('d/m/Y H:i', strtotime($m['fecha'])) ?></small></td>
                                    <td>
                                        <?php if ($m['tipo'] === 'entrada'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Entrada</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Salida / Retiro</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($m['producto_nombre'] ?? 'Desconocido') ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($m['producto_codigo'] ?? '') ?></small>
                                    </td>
                                    <td class="fw-bold"><?= $m['cantidad'] ?></td>
                                    <td><?= htmlspecialchars($m['usuario_nombre'] ?? 'Sistema') ?></td>
                                    <td><small class="text-secondary"><?= htmlspecialchars($m['observacion'] ?? 'Sin observaciones') ?></small></td>
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