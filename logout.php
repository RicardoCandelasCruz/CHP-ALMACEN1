<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Auth.php';

try {
    $pdo = new PDO(
        "pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME, 
        DB_USER, 
        DB_PASS
    );
    $auth = new Auth($pdo);
    $auth->cerrarSesion();
} catch (PDOException $e) {
    // Aunque falle la conexión, destruir la sesión
    session_start();
    session_destroy();
}

header("Location: login.php");
exit();
?>