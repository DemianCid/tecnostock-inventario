<?php
session_start();
require_once 'conexion.php';

// Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener lista de productos activos
$stmt_prod = $conexion->prepare("SELECT id, codigo, nombre, stock_actual FROM productos WHERE estado = 'activo' ORDER BY nombre ASC");
$stmt_prod->execute();
$productos = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id = filter_var($_POST['producto_id'] ?? null, FILTER_VALIDATE_INT);
    $tipo        = $_POST['tipo'] ?? '';
    $cantidad    = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT);
    $observacion = trim($_POST['observacion'] ?? '');
    $usuario_id  = $_SESSION['usuario_id'];

    if ($producto_id !== false && $producto_id > 0
        && in_array($tipo, ['entrada', 'salida'], true)
        && $cantidad !== false && $cantidad > 0) {
        try {
            $conexion->beginTransaction();
            $stmt_check = $conexion->prepare(
                "SELECT stock_actual FROM productos
                 WHERE id = :id AND estado = 'activo' FOR UPDATE"
            );
            $stmt_check->execute([':id' => $producto_id]);
            $producto = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if (!$producto) {
                $conexion->rollBack();
                $error = 'El producto seleccionado no existe o está inactivo.';
            } elseif ($tipo === 'salida' && (int)$producto['stock_actual'] < $cantidad) {
                $conexion->rollBack();
                $error = 'Stock insuficiente: solo hay ' . $producto['stock_actual'] . ' unidades disponibles.';
            } else {
                $operador = $tipo === 'entrada' ? '+' : '-';
                $stmt_update = $conexion->prepare(
                    "UPDATE productos SET stock_actual = stock_actual $operador :cantidad
                     WHERE id = :id AND (:es_entrada = 1 OR stock_actual >= :stock_minimo)"
                );
                $stmt_update->execute([
                    ':cantidad' => $cantidad,
                    ':id' => $producto_id,
                    ':es_entrada' => $tipo === 'entrada' ? 1 : 0,
                    ':stock_minimo' => $cantidad
                ]);

                if ($stmt_update->rowCount() !== 1) {
                    throw new RuntimeException('No fue posible actualizar el stock.');
                }

                $stmt_mov = $conexion->prepare(
                    "INSERT INTO movimientos (tipo, cantidad, observacion, producto_id, usuario_id)
                     VALUES (:tipo, :cantidad, :observacion, :producto_id, :usuario_id)"
                );
                $stmt_mov->execute([
                    ':tipo' => $tipo,
                    ':cantidad' => $cantidad,
                    ':observacion' => $observacion,
                    ':producto_id' => $producto_id,
                    ':usuario_id' => $usuario_id
                ]);

                $conexion->commit();
                header('Location: index.php');
                exit;
            }
        } catch (PDOException | RuntimeException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            $error = 'No se pudo registrar el movimiento. Inténtalo nuevamente.';
        }
    } else {
        $error = 'Por favor completa todos los campos requeridos con valores válidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimiento de Inventario - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Registrar Entrada / Salida de Stock</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="registrar_movimiento.php" method="POST" id="form-movimiento">
                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <select name="producto_id" id="producto_id" class="form-select" required>
                            <option value="">-- Seleccionar Producto --</option>
                            <?php foreach ($productos as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" data-stock="<?= (int)$p['stock_actual'] ?>">
                                    <?= htmlspecialchars($p['nombre']) ?> (Código: <?= htmlspecialchars($p['codigo']) ?> | Stock actual: <?= $p['stock_actual'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo de Movimiento</label>
                        <select name="tipo" id="tipo" class="form-select" required>
                            <option value="entrada">Entrada (Aumenta stock)</option>
                            <option value="salida">Salida (Disminuye stock)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cantidad</label>
                        <input type="number" name="cantidad" id="cantidad" class="form-control" min="1" placeholder="Ej: 10" required>
                        <div class="form-text" id="aviso-stock" aria-live="polite"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observación / Detalle</label>
                        <input type="text" name="observacion" class="form-control" placeholder="Ej: Compra a proveedor / Venta boleta #1024">
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" id="guardar-movimiento" class="btn btn-primary">Guardar Movimiento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        const productoSelect = document.getElementById('producto_id');
        const tipoSelect = document.getElementById('tipo');
        const cantidadInput = document.getElementById('cantidad');
        const avisoStock = document.getElementById('aviso-stock');
        const botonGuardar = document.getElementById('guardar-movimiento');

        function validarStockSalida() {
            const option = productoSelect.options[productoSelect.selectedIndex];
            const disponible = Number(option.dataset.stock || 0);
            const esSalida = tipoSelect.value === 'salida';
            cantidadInput.max = esSalida && disponible ? disponible : '';
            const excede = esSalida && Number(cantidadInput.value) > disponible;
            avisoStock.textContent = excede ? `Stock insuficiente: disponibles ${disponible}.` : '';
            botonGuardar.disabled = excede;
        }

        productoSelect.addEventListener('change', validarStockSalida);
        tipoSelect.addEventListener('change', validarStockSalida);
        cantidadInput.addEventListener('input', validarStockSalida);
        document.getElementById('form-movimiento').addEventListener('submit', function (event) {
            validarStockSalida();
            if (botonGuardar.disabled || Number(cantidadInput.value) < 1) {
                event.preventDefault();
            }
        });
        validarStockSalida();
    </script>
</body>
</html>