<?php
// Iniciar sesión segura
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Auth.php';

// Inicializar variables
$mensaje = '';
$usuario = [];

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

    // Obtener el usuario por ID
    $stmt = $conn->prepare("SELECT id, username, nombre, es_admin FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        throw new Exception("Usuario no encontrado");
    }
    
    // Generar token CSRF para el formulario
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
} catch (PDOException $e) {
    $mensaje = "Error en la base de datos: ".$e->getMessage();
    error_log("PDOException en editar_usuario: ".$e->getMessage());
} catch (Exception $e) {
    $mensaje = $e->getMessage();
    error_log("Exception en editar_usuario: ".$e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Sistema de Pedidos</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/fontawesome.min.css">
    <style>
        .password-strength {
            height: 5px;
            margin-top: 5px;
            background-color: #eee;
            position: relative;
        }
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s;
        }
        .weak { background-color: #dc3545; }
        .medium { background-color: #ffc107; }
        .strong { background-color: #28a745; }
        .form-card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 25px;
            background-color: #fff;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '../includes/header.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card form-card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Editar Usuario</h2>
                        
                        <?php if (!empty($usuario)): ?>
                        <form action="procesar_editar_usuario.php" method="POST" id="editUserForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario['id']); ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($usuario['username']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Nueva Contraseña (dejar en blanco para no cambiar)</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                </div>
                                <small class="form-text text-muted">La contraseña debe tener al menos 8 caracteres, incluyendo letras, números y símbolos.</small>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="es_admin" name="es_admin" <?php echo ($usuario['es_admin'] ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="es_admin">Administrador</label>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="agregar_usuario.php" class="btn btn-secondary me-md-2">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            No se encontró el usuario solicitado o no tiene permisos para editarlo.
                            <div class="mt-3">
                                <a href="agregar_usuario.php" class="btn btn-primary">Volver a la lista de usuarios</a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
            if (password.match(/\d/)) strength += 1;
            if (password.match(/[^a-zA-Z\d]/)) strength += 1;
            
            strengthBar.className = 'password-strength-bar';
            
            if (password.length === 0) {
                strengthBar.style.width = '0';
            } else {
                switch(strength) {
                    case 1:
                        strengthBar.classList.add('weak');
                        strengthBar.style.width = '25%';
                        break;
                    case 2:
                        strengthBar.classList.add('medium');
                        strengthBar.style.width = '50%';
                        break;
                    case 3:
                        strengthBar.classList.add('medium');
                        strengthBar.style.width = '75%';
                        break;
                    case 4:
                        strengthBar.classList.add('strong');
                        strengthBar.style.width = '100%';
                        break;
                }
            }
        });
        
        // Validación del formulario
        const form = document.getElementById('editUserForm');
        form.addEventListener('submit', function(event) {
            const password = passwordInput.value;
            
            if (password.length > 0 && password.length < 8) {
                event.preventDefault();
                alert('La contraseña debe tener al menos 8 caracteres.');
            }
        });
    });
    </script>
</body>
</html>