<?php
require_once 'conexion.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Obtener datos del producto
$stmt = $conexion->prepare("SELECT * FROM productos WHERE id = :id");
$stmt->execute([':id' => $id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    header('Location: index.php');
    exit;
}

// Obtener categorías
$stmt_cat = $conexion->query("SELECT * FROM categorias ORDER BY nombre ASC");
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $categoria_id = $_POST['categoria_id'] ?: null;
    $stock_actual = (int)$_POST['stock_actual'];
    $precio = round((float)$_POST['precio']); // Redondea a número entero

    if (!empty($codigo) && !empty($nombre) && $precio > 0) {
        try {
            $stmt = $conexion->prepare("
                UPDATE productos 
                SET codigo = :codigo, nombre = :nombre, categoria_id = :categoria_id, stock_actual = :stock_actual, precio = :precio 
                WHERE id = :id
            ");
            $stmt->execute([
                ':codigo' => $codigo,
                ':nombre' => $nombre,
                ':categoria_id' => $categoria_id,
                ':stock_actual' => $stock_actual,
                ':precio' => $precio,
                ':id' => $id
            ]);

            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Error al actualizar: El código ya pertenece a otro producto.';
        }
    } else {
        $error = 'Por favor completa todos los campos requeridos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Producto - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Editar Producto</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form action="editar_producto.php?id=<?= $id ?>" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Código del Producto</label>
                        <input type="text" name="codigo" class="form-control" value="<?= htmlspecialchars($producto['codigo']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre del Producto</label>
                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($producto['nombre']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select name="categoria_id" class="form-select">
                            <option value="">-- Seleccionar Categoría --</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $producto['categoria_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stock Actual</label>
                            <input type="number" name="stock_actual" class="form-control" value="<?= $producto['stock_actual'] ?>" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Precio</label>
                            <input type="number" step="1" name="precio" class="form-control" value="<?= (int)$producto['precio'] ?>" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-warning">Actualizar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>