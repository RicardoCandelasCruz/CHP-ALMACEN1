<?php
session_start();
include '../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Obtener el usuario de la base de datos (ahora incluyendo el rol)
        $query = "SELECT id, nombre, password, rol FROM usuarios WHERE email = :email";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar la contraseña
            if (password_verify($password, $usuario['password'])) {
                // Contraseña válida - guardar datos en sesión
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_rol'] = $usuario['rol']; // Guardar el rol en sesión
                
                // Redirigir según el rol
                if ($usuario['rol'] == 'admin') {
                    header("Location: ../admin/index.php");
                } else {
                    header("Location: ../index.php");
                }
                exit();
            } else {
                // Contraseña incorrecta
                header("Location: ../login.php?error=1");
                exit();
            }
        } else {
            // Usuario no encontrado
            header("Location: ../login.php?error=1");
            exit();
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>