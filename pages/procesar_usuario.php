<?php
session_start();
echo "Ruta actual: " . __DIR__ . "\n";
echo "Buscando: " . __DIR__ . "/includes/config.php\n";

require __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Hashear la contraseña
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Insertar el usuario en la base de datos
        $query = "INSERT INTO usuarios (nombre, email, password) VALUES (:nombre, :email, :password)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password_hashed);

        if ($stmt->execute()) {
            header("Location: ../index.php?success=1");
            exit();
        } else {
            header("Location: agregar_usuario.php?error=1");
            exit();
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>