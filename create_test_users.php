<?php
require_once __DIR__ . '/includes/config.php';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crear tabla si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        es_admin TINYINT(1) NOT NULL DEFAULT 0,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Insertar usuarios de prueba
    $usuarios = [
        [
            'username' => 'admin',
            'password' => password_hash('Admin123!', PASSWORD_DEFAULT),
            'nombre' => 'Administrador',
            'email' => 'admin@sistema.com',
            'es_admin' => 1
        ],
        [
            'username' => 'usuario',
            'password' => password_hash('Usuario123!', PASSWORD_DEFAULT),
            'nombre' => 'Usuario Normal',
            'email' => 'usuario@sistema.com',
            'es_admin' => 0
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, nombre, email, es_admin) 
                          VALUES (:username, :password, :nombre, :email, :es_admin)");

    foreach ($usuarios as $usuario) {
        $stmt->execute($usuario);
    }

    echo "Usuarios de prueba creados exitosamente:<br>";
    echo "<strong>Admin:</strong> usuario: admin | contraseña: Admin123!<br>";
    echo "<strong>Usuario normal:</strong> usuario: usuario | contraseña: Usuario123!";

} catch (PDOException $e) {
    die("<strong>Error:</strong> " . $e->getMessage());
}