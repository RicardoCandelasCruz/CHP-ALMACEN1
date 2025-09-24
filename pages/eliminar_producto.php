<?php
echo "Ruta actual: " . __DIR__ . "\n";
echo "Buscando: " . __DIR__ . "/includes/config.php\n";

require __DIR__ . '/includes/config.php';

echo "Ruta actual: " . __DIR__ . "\n";
echo "Buscando: " . __DIR__ . "/includes/Auth.php\n";

require __DIR__ . '/includes/Auth.php';
session_start();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['mensaje_error'] = "ID inválido.";
    header("Location: agregar_producto.php");
    exit();
}

try {
    $dsn = "pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME;
    $conn = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $stmt = $conn->prepare("DELETE FROM productos WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $_SESSION['mensaje_exito'] = "Producto eliminado correctamente.";
} catch (PDOException $e) {
    $_SESSION['mensaje_error'] = "Error al eliminar: " . $e->getMessage();
}

header("Location: agregar_producto.php");
exit();
