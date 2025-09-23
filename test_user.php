<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

try {
    // Prueba simple de conexión
    $pdo = new PDO(
        "pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME, 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]
    );
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
}