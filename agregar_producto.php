<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'conexion.php';

// Obtener categorías para el desplegable
$stmt_cat = $conexion->prepare("SELECT * FROM categorias WHERE estado = 'activo' ORDER BY nombre ASC");
$stmt_cat->execute();
$categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria_id = filter_var($_POST['categoria_id'] ?? null, FILTER_VALIDATE_INT);
    $stock_actual = filter_var($_POST['stock_actual'] ?? null, FILTER_VALIDATE_INT);
    $precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_FLOAT);

    if ($codigo !== '' && $nombre !== '' && $categoria_id !== false && $categoria_id > 0
        && $stock_actual !== false && $stock_actual >= 0 && $precio !== false && $precio >= 0) {
        try {
            $stmt = $conexion->prepare("
                INSERT INTO productos (codigo, nombre, categoria_id, stock_actual, precio) 
                VALUES (:codigo, :nombre, :categoria_id, :stock_actual, :precio)
            ");
            $stmt->execute([
                ':codigo' => $codigo,
                ':nombre' => $nombre,
                ':categoria_id' => $categoria_id,
                ':stock_actual' => $stock_actual,
                ':precio' => $precio
            ]);

            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'No se pudo guardar. Verifica que el código sea único y los datos sean válidos.';
        }
    } else {
        $error = 'Por favor completa todos los campos requeridos correctamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Producto - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Registrar Nuevo Producto</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="agregar_producto.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Código del Producto</label>
                        <input type="text" name="codigo" class="form-control" placeholder="Ej: PROD-001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre del Producto</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Mouse Gamer Logi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select name="categoria_id" class="form-select" required>
                            <option value="">-- Seleccionar Categoría --</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stock Inicial</label>
                            <input type="number" name="stock_actual" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Precio</label>
                            <input type="number" step="0.01" name="precio" class="form-control" placeholder="29989.00" min="0" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Guardar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>