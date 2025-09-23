<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Auth.php';

// Conexión a PostgreSQL
try {
    $pdo = new PDO(
        "pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME, 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    $auth = new Auth($pdo);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Procesar formulario
$mensaje = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';

    try {
        if ($auth->hacerLogin($username, $password)) {
            if ($auth->esAdmin()) {
                header("Location: index.php");
            } else {
                header("Location: pages/formulario_pedidos.php");
            }
            exit();
        } else {
            $mensaje = "Usuario o contraseña incorrectos";
        }
    } catch (Exception $e) {
        $mensaje = "Error en el sistema. Por favor intente más tarde.";
        error_log("Login error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <style>
        /* Estilos mejorados */
        body { font-family: 'Segoe UI', sans-serif; background: #f5f7fa; }
        .login-box { 
            max-width: 400px; margin: 5% auto; padding: 2rem;
            background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-control { 
            width: 100%; padding: 0.75rem; border: 1px solid #ddd;
            border-radius: 4px; font-size: 1rem; 
        }
        .btn { 
            background: #4361ee; color: white; border: none;
            padding: 0.75rem; width: 100%; border-radius: 4px;
            cursor: pointer; font-size: 1rem;
        }
        .alert { 
            padding: 0.75rem; margin-bottom: 1rem; 
            border-radius: 4px; text-align: center;
        }
        .alert-error { background: #fee; color: #d32f2f; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Iniciar Sesión</h2>
        
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" class="form-control" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn">Ingresar</button>
        </form>
    </div>
</body>
</html>