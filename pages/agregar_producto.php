<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Auth.php';

session_start();

try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    $conn = new PDO($dsn, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->query("SELECT id, nombre FROM productos ORDER BY nombre");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error de base de datos: " . $e->getMessage();
    error_log($error);
    $productos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header"><h1 class="h4">Agregar Nuevo Producto</h1></div>
        <div class="card-body">
            <?php foreach (['mensaje_exito' => 'success', 'mensaje_error' => 'danger'] as $key => $type): ?>
                <?php if (isset($_SESSION[$key])): ?>
                    <div class="alert alert-<?= $type ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION[$key]) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION[$key]); ?>
                <?php endif; ?>
            <?php endforeach; ?>

            <form action="procesar_producto.php" method="POST" class="mb-4">
                <div class="row align-items-end">
                    <div class="col-md-8">
                        <label for="nombre" class="form-label">Nombre del Producto *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="col-md-4 mt-md-0 mt-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save me-2"></i>Guardar Producto
                        </button>
                    </div>
                </div>
            </form>

            <div class="card mt-4 mb-3">
                <div class="card-header"><h2 class="h5 mb-0">Listado de Productos</h2></div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th class="text-end" style="width:200px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($productos): ?>
                                <?php foreach ($productos as $producto): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($producto['id']) ?></td>
                                        <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                        <td class="text-end">
                                            <a href="editar_producto.php?id=<?= $producto['id'] ?>" class="btn btn-sm btn-outline-warning">
                                                <i class="bi bi-pencil-square me-1"></i>Editar
                                            </a>
                                            <a href="eliminar_producto.php?id=<?= $producto['id'] ?>" class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('¿Eliminar este producto?');">
                                                <i class="bi bi-trash me-1"></i>Eliminar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-3">No hay productos</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
