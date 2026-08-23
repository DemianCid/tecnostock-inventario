<?php
session_start();
require_once 'conexion.php';

// Control de sesión: si no ha iniciado sesión, va al login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Lógica de búsqueda
$buscar = trim($_GET['buscar'] ?? '');

$sql = "
    SELECT p.*, c.nombre AS categoria 
    FROM productos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE 1=1
";

$params = [];

if (!empty($buscar)) {
    $sql .= " AND (p.nombre LIKE :buscar1 OR p.codigo LIKE :buscar2)";
    $params[':buscar1'] = '%' . $buscar . '%';
    $params[':buscar2'] = '%' . $buscar . '%';
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Barra superior de usuario -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">TecnoStock</a>
            <div class="d-flex align-items-center">
                <span class="text-light me-3">👤 <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="text-primary font-weight-bold mb-0">Gestión de Inventario</h2>
            <div>
                <a href="registrar_movimiento.php" class="btn btn-warning me-2">📦 Movimiento (Entrada/Salida)</a>
                <a href="agregar_producto.php" class="btn btn-success">+ Nuevo Producto</a>
            </div>
        </div>

        <!-- Buscador -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <form action="index.php" method="GET" class="row g-2">
                    <div class="col-md-10">
                        <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre o código de producto..." value="<?= htmlspecialchars($buscar) ?>">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        <?php if (!empty($buscar)): ?>
                            <a href="index.php" class="btn btn-secondary">Limpiar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de productos -->
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Listado de Productos</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Stock Actual</th>
                                <th>Precio</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productos)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No se encontraron productos registrados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($productos as $p): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($p['codigo']) ?></strong></td>
                                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                                        <td><?= htmlspecialchars($p['categoria'] ?? 'Sin categoría') ?></td>
                                        <td>
                                            <?= $p['stock_actual'] ?>
                                            <!-- Alerta de Stock Bajo -->
                                            <?php if ($p['stock_actual'] <= $p['stock_minimo']): ?>
                                                <span class="badge bg-danger ms-1">Stock Bajo (Mín: <?= $p['stock_minimo'] ?>)</span>
                                            <?php else: ?>
                                                <span class="badge bg-success ms-1">OK</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>$<?= number_format($p['precio'], 0, '', '.') ?></td>
                                        <td>
                                            <?php if ($p['estado'] === 'activo'): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="editar_producto.php?id=<?= $p['id'] ?>" class="btn btn-warning btn-sm me-1">Editar</a>
                                            <a href="desactivar_producto.php?id=<?= $p['id'] ?>" class="btn btn-outline-secondary btn-sm" onclick="return confirm('¿Deseas cambiar el estado de este producto?')">
                                                <?= $p['estado'] === 'activo' ? 'Desactivar' : 'Activar' ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>