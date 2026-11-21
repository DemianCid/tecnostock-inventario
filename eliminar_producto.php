<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if ($id !== false && $id > 0) {
        $_POST['estado'] = 'inactivo';
        require 'desactivar_producto.php';
        exit;
    }
}

header('Location: index.php');
exit;