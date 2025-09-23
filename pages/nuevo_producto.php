<?php

require_once __DIR__ . '/../includes/config.php';
// Obtener todos los productos
try {
    $query = "SELECT id, nombre, inventario, cantidad FROM productos";
    $stmt = $conn->query($query);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-5">
        <h1>Agregar Producto</h1>

        <!-- Formulario para agregar producto -->
        <form action="procesar_producto.php" method="POST">
            <div class="form-group">
                <label for="nombre">Nombre del Producto</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required>
            </div>
      <!--     <div class="form-group">
                <label for="inventario">Inventario</label>
                <input type="number" class="form-control" id="inventario" name="inventario" required>
            </div>
            <div class="form-group">
                <label for="cantidad">Cantidad</label>
                <input type="number" class="form-control" id="cantidad" name="cantidad" required>
            </div>-->
            <button type="submit" class="btn btn-primary mt-3">Agregar Producto</button>
        </form>

        </body>
</html>