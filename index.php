<?php
// Verificar si se está ejecutando desde el servidor web
if (php_sapi_name() === 'cli-server' && basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    die('Acceso no permitido');
}

// Usar rutas absolutas con __DIR__
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/Auth.php';

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
    
    // Verificar sesión y redirigir según rol
    if (!$auth->verificarSesion()) {
        header("Location: login.php");
        exit();
    }
    
    if (!$auth->esAdmin()) {
        header("Location: pages/formulario_pedidos.php");
        exit();
    }
    
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Sistema de Pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card {
            transition: transform 0.3s;
            height: 100%;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .card-body {
            display: flex;
            flex-direction: column;
        }
        .card-text {
            flex-grow: 1;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="container my-5">
        <div class="text-center mb-5">
            <h1 class="display-4">Bienvenido, <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?></h1>
            <p class="lead">Sistema de gestión de pedidos</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-primary">
                    <div class="card-body text-center">
                        <i class="bi bi-cart-plus fs-1 text-primary mb-3"></i>
                        <h5 class="card-title">Realizar Pedido</h5>
                        <p class="card-text">Haz un nuevo pedido de productos.</p>
                        <a href="pages/formulario_pedidos.php" class="btn btn-primary align-self-end mt-auto">Ir al Formulario</a>
                    </div>
                </div>
            </div>
            
            <?php if ($auth->esAdmin()): ?>
            <div class="col-md-4">
                <div class="card h-100 border-success">
                    <div class="card-body text-center">
                        <i class="bi bi-box-seam fs-1 text-success mb-3"></i>
                        <h5 class="card-title">Agregar Producto</h5>
                        <p class="card-text">Añade un nuevo producto al catálogo.</p>
                        <a href="pages/agregar_producto.php" class="btn btn-success align-self-end mt-auto">Agregar Producto</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-warning">
                    <div class="card-body text-center">
                        <i class="bi bi-person-plus fs-1 text-warning mb-3"></i>
                        <h5 class="card-title">Agregar Usuario</h5>
                        <p class="card-text">Registra un nuevo usuario al sistema.</p>
                        <a href="pages/agregar_usuario.php" class="btn btn-warning align-self-end mt-auto">Agregar Usuario</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>