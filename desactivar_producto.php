<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$estado = $_POST['estado'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id !== false && $id > 0
    && in_array($estado, ['activo', 'inactivo'], true)) {
    $update = $conexion->prepare("UPDATE productos SET estado = :estado WHERE id = :id");
    $update->execute([':estado' => $estado, ':id' => $id]);
}

header('Location: index.php');
exit;