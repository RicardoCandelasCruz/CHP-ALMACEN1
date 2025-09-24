<?php
echo "Ruta actual: " . __DIR__ . "\n";
echo "Buscando: " . __DIR__ . "/includes/config.php\n";

require __DIR__ . '/includes/config.php';

echo "Ruta actual: " . __DIR__ . "\n";
echo "Buscando: " . __DIR__ . "/includes/Auth.php\n";

require __DIR__ . '/includes/Auth.php';
session_start();

$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['mensaje_error'] = "ID inválido.";
    header("Location: agregar_producto.php");
    exit();
}

try {
    $dsn = "pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME;
    $conn = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $stmt = $conn->prepare("SELECT * FROM productos WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $producto = $stmt->fetch();

    if (!$producto) {
        $_SESSION['mensaje_error'] = "Producto no encontrado.";
        header("Location: agregar_producto.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['mensaje_error'] = "Error: " . $e->getMessage();
    header("Location: agregar_producto.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header"><h4>Editar Producto</h4></div>
        <div class="card-body">
            <form action="procesar_editar_producto.php" method="POST">
                <input type="hidden" name="id" value="<?= htmlspecialchars($producto['id']) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="nombre" name="nombre"
                           value="<?= htmlspecialchars($producto['nombre']) ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                <a href="agregar_producto.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
