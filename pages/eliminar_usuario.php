<?php
// Iniciar sesión segura
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/Auth.php';

// Inicializar variables
$mensaje = '';

try {
    // 1. Establecer conexión a PostgreSQL con manejo de errores
    $dsn = "pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";options='--client_encoding=UTF8'";
    $conn = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false
    ]);

    // 2. Verificar autenticación y permisos
    $auth = new Auth($conn);
    
    // Redirigir si no está logueado
    if (!$auth->verificarSesion()) {
        header("Location: ../login.php?redirect=".urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
    
    // Redirigir si no es admin
    if (!$auth->esAdmin()) {
        header("Location: ../index.php?error=permisos");
        exit();
    }
    
    // Verificar que se proporcionó un ID
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception("ID de usuario no proporcionado");
    }
    
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null) {
        throw new Exception("ID de usuario inválido");
    }
    
    // Verificar que el usuario existe
    $stmt = $conn->prepare("SELECT id, username FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        throw new Exception("Usuario no encontrado");
    }
    
    // Verificar que no se está eliminando al usuario actual
    if ($id == $_SESSION['usuario_id']) {
        throw new Exception("No puedes eliminar tu propio usuario");
    }
    
    // Verificar que no se está eliminando al usuario administrador principal
    if ($id == 1) {
        throw new Exception("No se puede eliminar al administrador principal del sistema");
    }
    
    // Eliminar el usuario
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        // Registrar la eliminación en el log
        error_log("Usuario eliminado: ID=$id, Username={$usuario['username']} por el usuario {$_SESSION['usuario_id']}");
        
        // Redirigir a la página de usuarios con mensaje de éxito
        header("Location: agregar_usuario.php?success=3");
        exit();
    } else {
        throw new Exception("Error al eliminar el usuario");
    }
    
} catch (PDOException $e) {
    $mensaje = "Error en la base de datos: ".$e->getMessage();
    error_log("PDOException en eliminar_usuario: ".$e->getMessage());
    header("Location: agregar_usuario.php?error=".urlencode($mensaje));
    exit();
} catch (Exception $e) {
    $mensaje = $e->getMessage();
    error_log("Exception en eliminar_usuario: ".$e->getMessage());
    header("Location: agregar_usuario.php?error=".urlencode($mensaje));
    exit();
}
?>