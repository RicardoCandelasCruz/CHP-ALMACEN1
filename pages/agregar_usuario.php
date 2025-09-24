<?php
// Iniciar sesión segura
echo "Ruta actual: " . __DIR__ . "\n";
echo "Buscando: " . __DIR__ . "/includes/config.php\n";

require __DIR__ . '/includes/config.php';

echo "Ruta actual: " . __DIR__ . "\n";
echo "Buscando: " . __DIR__ . "/includes/Auth.php\n";

require __DIR__ . '/includes/Auth.php';

// Inicializar variables
$mensaje = '';
$username = $nombre = '';
$es_admin = 0;

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

    // 3. Procesar formulario de agregar usuario
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validar token CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Token de seguridad inválido");
        }

        // Filtrar y validar datos
        $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
        $password = $_POST['password'] ?? '';
        $nombre = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING));
        $es_admin = isset($_POST['es_admin']) ? 1 : 0;

        // Validaciones adicionales
        // Validaciones mejoradas
if (empty(trim($username))) {
    throw new Exception("El nombre de usuario es obligatorio");
}

if (strlen($username) < 4 || strlen($username) > 20) {
    throw new Exception("El nombre de usuario debe tener entre 4 y 20 caracteres");
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    throw new Exception("El nombre de usuario solo puede contener letras (a-z, A-Z), números (0-9) y guiones bajos (_)");
}

if (empty($password)) {
    throw new Exception("La contraseña es obligatoria");
}

if (strlen($password) < 8) {
    throw new Exception("La contraseña debe tener al menos 8 caracteres");
}

if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    throw new Exception("La contraseña debe contener al menos una mayúscula y un número");
}

// Verificar si el usuario ya existe (versión más robusta)
$stmt = $conn->prepare("SELECT COUNT(id) as total FROM usuarios WHERE username = :username");
$stmt->bindParam(':username', $username, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch();

if ($result && $result['total'] > 0) {
    throw new Exception("El nombre de usuario '$username' ya está registrado");
}

        // Verificar si el usuario ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = :username");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            throw new Exception("El nombre de usuario ya está en uso");
        }

        // Hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Insertar usuario
        $stmt = $conn->prepare("
            INSERT INTO usuarios (username, password, nombre, es_admin, fecha_creacion) 
            VALUES (:username, :password, :nombre, :es_admin, NOW())
        ");
        
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password_hash, PDO::PARAM_STR);
        $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindParam(':es_admin', $es_admin, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $mensaje = "Usuario agregado correctamente";
            // Limpiar campos después de éxito
            $username = $nombre = '';
            $es_admin = 0;
            
            // Registrar en log
            error_log("Nuevo usuario creado: $username por ".$_SESSION['username']);
        } else {
            throw new Exception("Error al ejecutar la consulta");
        }
    }
    
    // Generar token CSRF para el formulario
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

} catch (PDOException $e) {
    $mensaje = "Error en la base de datos: ".$e->getMessage();
    error_log("PDOException en agregar_usuario: ".$e->getMessage());
} catch (Exception $e) {
    $mensaje = $e->getMessage();
    error_log("Exception en agregar_usuario: ".$e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Formulario para agregar nuevos usuarios al sistema">
    <title>Agregar Usuario | Sistema de Gestión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .password-strength {
            height: 5px;
            margin-top: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
        }
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        body {
            background-color: #f8f9fa;
            padding-top: 2rem;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .card-header {
            font-weight: bold;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .badge {
            font-size: 0.85em;
            padding: 0.35em 0.65em;
        }
        .actions-column {
            min-width: 160px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="form-container">
            <h2 class="mb-4 text-center">
                <i class="bi bi-person-plus"></i> Agregar Nuevo Usuario
            </h2>
            
            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?= strpos($mensaje, 'Error') !== false ? 'danger' : 'success' ?> alert-dismissible fade show">
                    <?= htmlspecialchars($mensaje) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php
            // Mensajes de operaciones de usuarios
            if (isset($_GET['success'])) {
                $success_code = filter_input(INPUT_GET, 'success', FILTER_VALIDATE_INT);
                $success_message = '';
                
                switch ($success_code) {
                    case 1:
                        $success_message = 'Usuario agregado correctamente.';
                        break;
                    case 2:
                        $success_message = 'Usuario actualizado correctamente.';
                        break;
                    case 3:
                        $success_message = 'Usuario eliminado correctamente.';
                        break;
                }
                
                if (!empty($success_message)) {
                    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                    echo '<i class="bi bi-check-circle-fill me-2"></i>' . htmlspecialchars($success_message);
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                }
            }
            
            if (isset($_GET['error']) && !empty($_GET['error'])) {
                $error_message = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_STRING);
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                echo '<i class="bi bi-exclamation-triangle-fill me-2"></i>' . htmlspecialchars($error_message);
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                echo '</div>';
            }
            ?>

            <form method="POST" id="userForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="bi bi-person-fill"></i> Nombre de Usuario *
                    </label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?= htmlspecialchars($username) ?>" 
                           required
                           pattern="[a-zA-Z0-9_]+"
                           title="Solo letras, números y guiones bajos">
                    <div class="form-text">No usar espacios ni caracteres especiales</div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock-fill"></i> Contraseña *
                    </label>
                    <input type="password" class="form-control" id="password" name="password" 
                           required minlength="8">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrength"></div>
                    </div>
                    <div class="form-text">Mínimo 8 caracteres</div>
                </div>
                
                <div class="mb-3">
                    <label for="nombre" class="form-label">
                        <i class="bi bi-card-heading"></i> Nombre Completo
                    </label>
                    <input type="text" class="form-control" id="nombre" name="nombre" 
                           value="<?= htmlspecialchars($nombre) ?>">
                </div>
                
                <div class="mb-3 form-check form-switch">
                    <input type="checkbox" class="form-check-input" id="es_admin" name="es_admin" 
                           <?= $es_admin ? 'checked' : '' ?>>
                    <label class="form-check-label" for="es_admin">
                        <i class="bi bi-shield-lock"></i> ¿Es administrador?
                    </label>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                     <a href="/proyecto_pedidos/index.php" class="btn btn-secondary me-md-2">
                       <i class="bi bi-arrow-left"></i> Cancelar
                 </a>
                 <button type="submit" class="btn btn-primary">
        <i class="bi bi-save"></i> Guardar Usuario
    </button>
</div>
                
            </form>
        </div>
    </div>
    
    <!-- Lista de Usuarios -->
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h3 class="mb-0"><i class="bi bi-people"></i> Lista de Usuarios</h3>
                <a href="agregar_usuario.php" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-clockwise"></i> Refrescar
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Consultar todos los usuarios
                            try {
                                $stmt = $conn->query("SELECT id, username, nombre, es_admin, fecha_creacion FROM usuarios ORDER BY id");
                                $usuarios = $stmt->fetchAll();
                                
                                if (empty($usuarios)) {
                                    echo '<tr><td colspan="6" class="text-center py-3">No hay usuarios registrados</td></tr>';
                                }
                                
                                foreach ($usuarios as $usuario): 
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($usuario['id']) ?></td>
                                    <td><?= htmlspecialchars($usuario['username']) ?></td>
                                    <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                                    <td>
                                        <span class="badge <?= $usuario['es_admin'] ? 'bg-danger' : 'bg-success' ?>">
                                            <?= $usuario['es_admin'] ? 'Administrador' : 'Usuario' ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($usuario['fecha_creacion']))) ?></td>
                                    <td class="actions-column">
                                        <div class="btn-group">
                                            <a href="editar_usuario.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil-square"></i> Editar
                                            </a>
                                            <a href="eliminar_usuario.php?id=<?= $usuario['id'] ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('¿Está seguro de eliminar este usuario?')">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                endforeach; 
                            } catch (PDOException $e) {
                                echo '<tr><td colspan="6" class="text-danger">Error al cargar usuarios: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación de contraseña en tiempo real
        document.getElementById('password').addEventListener('input', function(e) {
            const strengthBar = document.getElementById('passwordStrength');
            const password = e.target.value;
            let strength = 0;
            
            if (password.length > 7) strength += 1;
            if (password.length > 11) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Actualizar barra de fuerza
            const width = strength * 20;
            strengthBar.style.width = width + '%';
            strengthBar.style.backgroundColor = 
                strength < 2 ? '#dc3545' : 
                strength < 4 ? '#ffc107' : '#28a745';
        });

        // Validación antes de enviar
        document.getElementById('userForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            if (password.length < 8) {
                alert('La contraseña debe tener al menos 8 caracteres');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>