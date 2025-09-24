<?php
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/Auth.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: agregar_producto.php");
    exit();
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['mensaje_error'] = "Token CSRF inválido.";
    header("Location: agregar_producto.php");
    exit();
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$nombre = trim($_POST['nombre'] ?? '');

if (!$id || $nombre === '') {
    $_SESSION['mensaje_error'] = "Datos inválidos.";
    header("Location: agregar_producto.php");
    exit();
}

try {
    $dsn = "pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME;
    $conn = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $stmt = $conn->prepare("UPDATE productos SET nombre = :nombre WHERE id = :id");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $_SESSION['mensaje_exito'] = "Producto actualizado correctamente.";
} catch (PDOException $e) {
    $_SESSION['mensaje_error'] = "Error al actualizar: " . $e->getMessage();
}

header("Location: agregar_producto.php");
exit();
