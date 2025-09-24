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
    
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }
    
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
        $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Error de validación del formulario");
    }
    
    // Limpiar el token CSRF después de usarlo
    unset($_SESSION['csrf_token']);
    
    // Validar y sanitizar datos
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception("ID de usuario no proporcionado");
    }
    
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null) {
        throw new Exception("ID de usuario inválido");
    }
    
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
    $es_admin = isset($_POST['es_admin']) ? 1 : 0;
    
    // Validar longitud de contraseña si se proporciona
    if (!empty($password) && strlen($password) < 8) {
        throw new Exception("La contraseña debe tener al menos 8 caracteres");
    }
    
    // Verificar que el usuario existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        throw new Exception("Usuario no encontrado");
    }
    
    // Verificar si el nombre de usuario ya existe (para otro usuario)
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = :username AND id != :id");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        throw new Exception("El nombre de usuario ya está en uso por otro usuario");
    }
    
    // Preparar la consulta según si se actualiza la contraseña o no
    if (!empty($password)) {
        // Hashear la nueva contraseña
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE usuarios SET username = :username, nombre = :nombre, password = :password, es_admin = :es_admin WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':password', $password_hashed, PDO::PARAM_STR);
    } else {
        // No actualizar la contraseña
        $query = "UPDATE usuarios SET username = :username, nombre = :nombre, es_admin = :es_admin WHERE id = :id";
        $stmt = $conn->prepare($query);
    }

    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
    $stmt->bindParam(':es_admin', $es_admin, PDO::PARAM_INT);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        header("Location: agregar_usuario.php?success=2");
        exit();
    } else {
        throw new Exception("Error al actualizar el usuario");
    }
    
} catch (PDOException $e) {
    $mensaje = "Error en la base de datos: ".$e->getMessage();
    error_log("PDOException en procesar_editar_usuario: ".$e->getMessage());
    header("Location: editar_usuario.php?id=".$id."&error=".urlencode($mensaje));
    exit();
} catch (Exception $e) {
    $mensaje = $e->getMessage();
    error_log("Exception en procesar_editar_usuario: ".$e->getMessage());
    header("Location: editar_usuario.php?id=".$id."&error=".urlencode($mensaje));
    exit();
}
?>