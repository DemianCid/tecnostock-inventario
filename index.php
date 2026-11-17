<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$buscar = trim($_GET['buscar'] ?? '');

$sql = "
    SELECT p.*, c.nombre AS categoria 
    FROM productos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE 1=1
";

$params = [];

if (!empty($buscar)) {
    $sql .= " AND (p.nombre LIKE :buscar1 OR p.codigo LIKE :buscar2 OR c.nombre LIKE :buscar3)";
    $params[':buscar1'] = '%' . $buscar . '%';
    $params[':buscar2'] = '%' . $buscar . '%';
    $params[':buscar3'] = '%' . $buscar . '%';
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
    <title>TecnoStock - Panel de Inventario</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-main: #f8fafc;
            --brand-dark: #0f172a;
            --brand-indigo: #4338ca;
            --brand-indigo-hover: #3730a3;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-main);
            color: #334155;
        }

        /* Navbar personalizado */
        .navbar-custom {
            background-color: var(--brand-dark);
            border-bottom: 1px solid #1e293b;
        }

        .avatar-circle {
            width: 32px;
            height: 32px;
            background-color: #334155;
            color: #f8fafc;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
        }

        /* Tarjetas estilo dashboard moderno */
        .card-custom {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03);
        }

        /* Botones estilizados */
        .btn-indigo {
            background-color: var(--brand-indigo);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }
        .btn-indigo:hover {
            background-color: var(--brand-indigo-hover);
            color: #ffffff;
        }

        /* Tabla estilizada */
        .table-custom {
            margin-bottom: 0;
        }
        .table-custom thead {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .table-custom th {
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
        }
        .table-custom td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            font-size: 0.925rem;
        }

        /* Badges suaves */
        .badge-soft-danger {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .badge-soft-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .badge-soft-secondary {
            background-color: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>

    <!-- Header / Navbar Minimalista -->
    <nav class="navbar navbar-expand-lg navbar-custom py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-white d-flex align-items-center gap-2" href="index.php">
                <span style="background: var(--brand-indigo); width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.9rem;">TS</span>
                TecnoStock
            </a>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 text-white-50 fs-7">
                    <div class="avatar-circle">
                        <?= htmlspecialchars(strtoupper(substr($_SESSION['usuario_nombre'], 0, 1))) ?>
                    </div>
                    <span class="text-light fw-medium small"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
                </div>
                <a href="logout.php" class="btn btn-outline-light btn-sm rounded-2 text-white-50 border-secondary">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Encabezado de sección con acciones rápidas -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Catálogo de Productos</h3>
                <p class="text-muted small mb-0">Control de inventario, stock y movimientos de existencias.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="nueva_venta.php" class="btn btn-success btn-sm rounded-2 fw-medium d-flex align-items-center gap-1">
                    🛒 Nueva Venta
                </a>
                <a href="ventas.php" class="btn btn-outline-success btn-sm rounded-2 fw-medium d-flex align-items-center gap-1">
                    📊 Ventas
                </a>
                <a href="historial.php" class="btn btn-outline-secondary rounded-2 fw-medium btn-sm d-flex align-items-center gap-1">
                    📋 Historial
                </a>
                <a href="registrar_movimiento.php" class="btn btn-outline-dark rounded-2 fw-medium btn-sm d-flex align-items-center gap-2">
                    📦 Movimiento
                </a>
                <a href="agregar_producto.php" class="btn btn-indigo btn-sm rounded-2 d-flex align-items-center gap-1">
                    + Nuevo Producto
                </a>
            </div>
        </div>

        <!-- Filtro y Buscador en Tarjeta Limpia -->
        <div class="card card-custom mb-4 p-3">
            <form action="index.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <input type="text" name="buscar" class="form-control border-0 bg-light" placeholder="Buscar por código, nombre o categoría..." value="<?= htmlspecialchars($buscar) ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-2 btn-sm fw-medium">Filtrar</button>
                    <?php if (!empty($buscar)): ?>
                        <a href="index.php" class="btn btn-light border rounded-2 btn-sm">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabla principal limpia -->
        <div class="card card-custom overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Stock</th>
                            <th>Precio</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    No hay registros que coincidan con la búsqueda.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productos as $p): ?>
                                <tr>
                                    <td><code class="text-dark fw-bold"><?= htmlspecialchars($p['codigo']) ?></code></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($p['nombre']) ?></div>
                                    </td>
                                    <td><span class="text-muted"><?= htmlspecialchars($p['categoria'] ?? 'General') ?></span></td>
                                    <td>
                                        <span class="fw-bold me-1"><?= $p['stock_actual'] ?></span>
                                        <?php if ($p['stock_actual'] <= $p['stock_minimo']): ?>
                                            <span class="badge badge-soft-danger rounded-pill px-2 py-1">Stock Bajo (Mín: <?= $p['stock_minimo'] ?>)</span>
                                        <?php else: ?>
                                            <span class="badge badge-soft-success rounded-pill px-2 py-1">OK</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-semibold">$<?= number_format((float)$p['precio'], 2, ',', '.') ?></td>
                                    <td>
                                        <?php if ($p['estado'] === 'activo'): ?>
                                            <span class="badge badge-soft-success rounded-pill">Activo</span>
                                        <?php else: ?>
                                            <span class="badge badge-soft-secondary rounded-pill">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="editar_producto.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-light border me-1">Editar</a>
                                        <form action="desactivar_producto.php" method="POST" class="d-inline" onsubmit="return confirm('¿Cambiar estado del producto?')">
                                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                            <input type="hidden" name="estado" value="<?= $p['estado'] === 'activo' ? 'inactivo' : 'activo' ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                <?= $p['estado'] === 'activo' ? 'Desactivar' : 'Activar' ?>
                                            </button>
                                        </form>
                                    </td>
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