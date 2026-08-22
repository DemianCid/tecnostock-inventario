<?php
require_once 'conexion.php';

// Obtener productos junto a su categoría
$stmt = $conexion->prepare("
    SELECT p.*, c.nombre AS categoria 
    FROM productos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id
    ORDER BY p.id DESC
");
$stmt->execute();
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
    <div class="container my-5">
        <h1 class="mb-4 text-primary font-weight-bold">TecnoStock - Inventario</h1>
        
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Listado de Productos</h5>
                <a href="agregar_producto.php" class="btn btn-success btn-sm">+ Nuevo Producto</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Stock</th>
                            <th>Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No hay productos registrados en el sistema.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td><?= htmlspecialchars($producto['codigo']) ?></td>
                                    <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                    <td><?= htmlspecialchars($producto['categoria'] ?? 'Sin categoría') ?></td>
                                    <td><?= $producto['stock_actual'] ?></td>
                                    <td>$<?= number_format($producto['precio'], 0, '', '.') ?></td>
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