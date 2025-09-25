<?php
require __DIR__ . '/includes/config.php';

try {
    // Obtener todos los usuarios con contraseñas en texto plano
    $query = "SELECT id, nombre, password FROM usuarios";
    $stmt = $conn->query($query);

    while ($usuario = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Verificar si la contraseña ya está hasheada
        if (!password_needs_rehash($usuario['password'], PASSWORD_DEFAULT)) {
            continue; // Saltar si ya está hasheada
        }

        // Hashear la contraseña
        $password_hashed = password_hash($usuario['password'], PASSWORD_DEFAULT);

        // Actualizar la contraseña en la base de datos
        $update_query = "UPDATE usuarios SET password = :password WHERE id = :id";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(':password', $password_hashed);
        $update_stmt->bindParam(':id', $usuario['id']);
        $update_stmt->execute();
    }

    echo "Todas las contraseñas han sido actualizadas correctamente.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>