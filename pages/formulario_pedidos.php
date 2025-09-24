<?php
declare(strict_types=1);

echo "Ruta actual: " . __DIR__ . "\n";
echo "Buscando: " . __DIR__ . "/includes/config.php\n";

require __DIR__ . '/includes/config.php';

echo "Ruta actual: " . __DIR__ . "\n";
echo "Buscando: " . __DIR__ . "/includes/Auth.php\n";

require __DIR__ . '/includes/Auth.php';

// Inicializar variables
$productos = [];
$error = '';

try {
    // Establecer conexión a PostgreSQL
    $pdo = new PDO(
        "pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME, 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Verificar autenticación
    $auth = new Auth($pdo);
    if (!$auth->verificarSesion()) {
        header("Location: ../login.php");
        exit();
    }
    
    // Obtener productos con caché
    $cacheKey = 'lista_productos_pedidos';
    $cacheDir = __DIR__ . '/../cache/';
    
    if (!file_exists($cacheDir)) {
        if (!mkdir($cacheDir, 0755, true)) {
            throw new Exception("No se pudo crear el directorio de caché");
        }
    }
    
    $cacheFile = $cacheDir . $cacheKey . '.cache';
    $cacheTime = 3600; // 1 hora

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        $productos = unserialize(file_get_contents($cacheFile));
    } else {
        $stmt = $pdo->query("SELECT id, nombre FROM productos ORDER BY nombre ASC");
        $productos = $stmt->fetchAll();
        file_put_contents($cacheFile, serialize($productos));
    }
    
} catch (PDOException $e) {
    $error = "Error al conectar con la base de datos: " . $e->getMessage();
    error_log($error);
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log($error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .producto-row:hover { background-color: #f8f9fa; }
        .quantity-input { width: 80px; }
        #buscador { max-width: 400px; }
        .table-responsive { max-height: 500px; overflow-y: auto; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0"><i class="bi bi-cart-plus"></i> Formulario de Pedidos</h2>
            </div>
            
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="buscador" class="form-control" placeholder="Buscar producto...">
                        </div>
                    </div>
                </div>
               
                <form action="procesar_pedidos.php" method="post" id="formPedido">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-productos">
                                <?php if (!empty($productos)): ?>
                                    <?php foreach ($productos as $producto): ?>
                                        <tr class="producto-row">
                                            <td><?= htmlspecialchars((string)$producto['id']) ?></td>
                                            <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                            <td>
                                                <input type="number" 
                                                       name="productos[<?= $producto['id'] ?>]" 
                                                       class="form-control quantity-input" 
                                                       min="0" 
                                                       max="100"
                                                       value="0"
                                                       inputmode="numeric"
                                                       pattern="[0-9]*">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No hay productos disponibles</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Confirmar Pedido
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Filtrado de productos
            $('#buscador').on('input', function() {
                const termino = $(this).val().toLowerCase();
                $('#tabla-productos tr.producto-row').each(function() {
                    const textoFila = $(this).text().toLowerCase();
                    $(this).toggle(textoFila.includes(termino));
                });
            });

            // Validación inputs cantidad
            $('.quantity-input').on('input', function() {
                let value = $(this).val();
                if (value === '') return;
                let numValue = parseInt(value);
                if (isNaN(numValue) || numValue < 0) $(this).val(0);
                if (numValue > 100) $(this).val(100);
            });

            $('.quantity-input').on('keydown', function(e) {
                if ($.inArray(e.keyCode, [46, 8, 9, 27, 13]) !== -1 ||
                    (e.keyCode === 65 && (e.ctrlKey || e.metaKey)) ||
                    (e.keyCode >= 35 && e.keyCode <= 40)) {
                    return;
                }
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && 
                    (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });

            $('.quantity-input').on('blur', function() {
                let value = $(this).val();
                if (value === '' || isNaN(parseInt(value))) {
                    $(this).val(0);
                }
            });

            // Envío del formulario
            $('#formPedido').on('submit', function(e) {
                e.preventDefault();
                
                let alMenosUnProducto = false;
                $('input[name^="productos["]').each(function() {
                    if (parseInt($(this).val()) > 0) {
                        alMenosUnProducto = true;
                        return false;
                    }
                });

                if (!alMenosUnProducto) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Debe seleccionar al menos un producto'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Procesando pedido...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json'
                })
                .done(function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: response.message,
                            confirmButtonText: 'Ver pedido'
                        }).then((result) => {
                            if (result.isConfirmed && response.redirect) {
                                window.location.href = response.redirect;
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Ocurrió un error al procesar el pedido'
                        });
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error("Estado:", textStatus);
                    console.error("Error:", errorThrown);
                    console.log("Respuesta cruda:\n", jqXHR.responseText);

                    Swal.fire({
                        icon: 'error',
                        title: 'Error en la petición',
                        html: `<b>Estado:</b> ${textStatus}<br>
                               <b>Error:</b> ${errorThrown}<br><br>
                               <pre style="text-align:left;white-space:pre-wrap;">${jqXHR.responseText}</pre>`
                    });
                });
            });
        });
    </script>
</body>
</html>
