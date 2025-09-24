<?php
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/Auth.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: agregar_producto.php");
    exit();
}

$nombre = trim($_POST['nombre'] ?? '');
if ($nombre === '') {
    $_SESSION['mensaje_error'] = "El nombre del producto es obligatorio.";
    header("Location: agregar_producto.php");
    exit();
}

try {
    $dsn = "pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME;
    $conn = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $stmt = $conn->prepare("INSERT INTO productos (nombre) VALUES (:nombre)");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->execute();

    $_SESSION['mensaje_exito'] = "Producto agregado correctamente.";
} catch (PDOException $e) {
    $_SESSION['mensaje_error'] = "Error al guardar: " . $e->getMessage();
}

header("Location: agregar_producto.php");
exit();
