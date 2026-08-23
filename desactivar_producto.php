<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    // Consultar el estado actual
    $stmt = $conexion->prepare("SELECT estado FROM productos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $producto = $stmt->fetch();

    if ($producto) {
        $nuevo_estado = ($producto['estado'] === 'activo') ? 'inactivo' : 'activo';
        
        $update = $conexion->prepare("UPDATE productos SET estado = :estado WHERE id = :id");
        $update->execute([':estado' => $nuevo_estado, ':id' => $id]);
    }
}

header('Location: index.php');
exit;