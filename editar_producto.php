<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
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
$stmt_cat = $conexion->prepare("SELECT * FROM categorias WHERE estado = 'activo' ORDER BY nombre ASC");
$stmt_cat->execute();
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo       = trim($_POST['codigo'] ?? '');
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $categoria_id = filter_var($_POST['categoria_id'] ?? null, FILTER_VALIDATE_INT);
    $stock_actual = filter_var($_POST['stock_actual'] ?? null, FILTER_VALIDATE_INT);
    $stock_minimo = filter_var($_POST['stock_minimo'] ?? null, FILTER_VALIDATE_INT);
    $precio       = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_FLOAT);

    if ($codigo !== '' && $nombre !== '' && $categoria_id !== false && $categoria_id > 0
        && $stock_actual !== false && $stock_actual >= 0
        && $stock_minimo !== false && $stock_minimo >= 0
        && $precio !== false && $precio >= 0) {
        try {
            $stmt = $conexion->prepare("
                UPDATE productos 
                SET codigo = :codigo, nombre = :nombre, descripcion = :descripcion, 
                    categoria_id = :categoria_id, stock_actual = :stock_actual, 
                    stock_minimo = :stock_minimo, precio = :precio 
                WHERE id = :id
            ");
            $stmt->execute([
                ':codigo'       => $codigo,
                ':nombre'       => $nombre,
                ':descripcion'  => $descripcion,
                ':categoria_id' => $categoria_id,
                ':stock_actual' => $stock_actual,
                ':stock_minimo' => $stock_minimo,
                ':precio'       => $precio,
                ':id'           => $id
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
    <div class="container my-5" style="max-width: 650px;">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Editar Producto</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="editar_producto.php?id=<?= $id ?>" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Código del Producto</label>
                            <input type="text" name="codigo" class="form-control" value="<?= htmlspecialchars($producto['codigo']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre del Producto</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($producto['nombre']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"><?= htmlspecialchars($producto['descripcion'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select name="categoria_id" class="form-select" required>
                            <option value="">-- Seleccionar Categoría --</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $producto['categoria_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stock Actual</label>
                            <input type="number" name="stock_actual" class="form-control" value="<?= $producto['stock_actual'] ?>" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stock Mínimo</label>
                            <input type="number" name="stock_minimo" class="form-control" value="<?= $producto['stock_minimo'] ?>" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Precio ($)</label>
                            <input type="number" step="0.01" name="precio" class="form-control" value="<?= number_format((float)$producto['precio'], 2, '.', '') ?>" min="0" required>
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