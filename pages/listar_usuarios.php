<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

include '../includes/conexion.php';

try {
    // Obtener todos los usuarios
    $query = "SELECT id, nombre, email FROM usuarios";
    $stmt = $conn->query($query);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Usuarios</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-5">
        <h1>Listar Usuarios</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo $usuario['id']; ?></td>
                        <td><?php echo $usuario['nombre']; ?></td>
                        <td><?php echo $usuario['email']; ?></td>
                        <td>
                            <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                            <a href="eliminar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este usuario?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>