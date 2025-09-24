<?php
declare(strict_types=1);

// Configuración de errores (solo log, no mostrar en navegador)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/Auth.php';
require __DIR__ . '/../vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Limpiar cualquier buffer previo para evitar salida no JSON
if (ob_get_length()) {
    ob_end_clean();
}

// Respuesta inicial
$response = [
    'success'   => false,
    'message'   => '',
    'redirect'  => '',
    'pdf_url'   => '',
    'emailSent' => false
];

/**
 * Envía un correo con el PDF adjunto.
 */
function enviarCorreoPDF(string $pdfPath, int $pedidoId, string $nombreUsuario): bool {
    if (!file_exists($pdfPath)) {
        error_log("PDF no encontrado: " . $pdfPath);
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->SMTPDebug = 2; // Nivel de depuración aumentado para diagnosticar problemas
        $mail->Debugoutput = function($str, $level) { error_log("PHPMailer debug: $str"); };
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS; // ⚠ En producción, usar variables de entorno
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT; // Puerto para SMTP con STARTTLS
        $mail->CharSet = 'UTF-8';

        // Registrar información de configuración para depuración
        error_log("Configuración de correo: Host={$mail->Host}, Usuario={$mail->Username}, Puerto={$mail->Port}");

        $mail->setFrom(SMTP_USER, 'Cheese Pizza Almacen');
        $mail->addAddress(SMTP_FROM_EMAIL);

        $mail->isHTML(true);
        $mail->Subject = "Nuevo Pedido #{$pedidoId} - Cheese Pizza Almacen";
        $mail->Body = "Se ha generado un nuevo pedido:<br><br>"
                    . "Número de Pedido: {$pedidoId}<br>"
                    . "Cliente: {$nombreUsuario}<br>"
                    . "Fecha: " . date('d/m/Y H:i:s');
        $mail->AltBody = strip_tags($mail->Body);

        $mail->addAttachment($pdfPath, "pedido_{$pedidoId}.pdf");

        return $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $e->getMessage());
        error_log("Detalles del error de PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    if (!isset($_POST['productos']) || !is_array($_POST['productos'])) {
        throw new Exception('Datos de productos inválidos');
    }

    $pdo = new PDO(
        "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $auth = new Auth($pdo);
    if (!$auth->verificarSesion()) {
        throw new Exception('Sesión no válida');
    }

    $usuarioId     = $auth->obtenerUsuarioId();
    $nombreUsuario = $auth->obtenerUsuarioname();

    $pdo->beginTransaction();

    // Insertar pedido
    $stmtPedido = $pdo->prepare(
        "INSERT INTO pedidos (id, usuario_id, fecha) 
         VALUES ((SELECT COALESCE(MAX(id), 0) + 1 FROM pedidos), :usuario_id, NOW()) 
         RETURNING id"
    );
    $stmtPedido->execute(['usuario_id' => $usuarioId]);
    $pedidoId = $stmtPedido->fetchColumn();

    if (!$pedidoId) {
        throw new Exception('Error al crear el pedido');
    }

    // TODO: insertar detalles de productos aquí...
    // foreach ($_POST['productos'] as $producto) { ... }

    // Directorio para PDFs
    $pdfDir = __DIR__ . '/../pedidos/';
    if (!file_exists($pdfDir)) {
        if (!mkdir($pdfDir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio para PDFs');
        }
    }

    $pdfFilename = "pedido_{$pedidoId}.pdf";
    $pdfPath     = $pdfDir . $pdfFilename;

    // Generar PDF
    require_once __DIR__ . '/../libs/fpdf/fpdf.php';
    $pdf = new FPDF('P', 'mm', 'Letter'); // Formato carta, orientación vertical
    $pdf->SetMargins(10, 10, 10); // Reducir márgenes para aprovechar más espacio
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 6, 'CHEESE PIZZA ALMACEN - PEDIDO', 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, 'Pedido #' . $pedidoId, 0, 1, 'C');
    $pdf->Ln(2);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(30, 4, 'Cliente:', 0);
    $pdf->Cell(0, 4, $nombreUsuario, 0, 1);
    $pdf->Cell(30, 4, 'Fecha:', 0);
    $pdf->Cell(0, 4, date('d/m/Y H:i:s'), 0, 1);
    $pdf->Ln(3);
    
    // Preparar datos de productos para mostrar en dos columnas
    $productosParaMostrar = [];
    foreach ($_POST['productos'] as $productoId => $cantidad) {
        if ((int)$cantidad > 0) {
            // Obtener nombre del producto
            $stmtProducto = $pdo->prepare("SELECT nombre FROM productos WHERE id = :id");
            $stmtProducto->execute(['id' => $productoId]);
            $nombreProducto = $stmtProducto->fetchColumn() ?: 'Producto #' . $productoId;
            
            // Truncar nombre de producto si es muy largo para evitar desbordamiento
            if (strlen($nombreProducto) > 50) {
                $nombreProducto = substr($nombreProducto, 0, 47) . '...';
            }
            
            $productosParaMostrar[] = [
                'id' => $productoId,
                'nombre' => $nombreProducto,
                'cantidad' => $cantidad
            ];
            
            // Insertar detalle de pedido en la base de datos
            $stmtDetalle = $pdo->prepare(
                "INSERT INTO detalles_pedido (id, pedido_id, producto_id, cantidad) 
                 VALUES ((SELECT COALESCE(MAX(id), 0) + 1 FROM detalles_pedido), :pedido_id, :producto_id, :cantidad)"
            );
            $stmtDetalle->execute([
                'pedido_id' => $pedidoId,
                'producto_id' => $productoId,
                'cantidad' => $cantidad
            ]);
        }
    }
    
    // Calcular la distribución de productos en dos columnas
    $totalProductos = count($productosParaMostrar);
    $productosColumna1 = ceil($totalProductos / 2);
    
    // Configuración de las tablas
    $anchoColumna = 85; // Ancho de cada columna
    $espacioEntreColumnas = 10; // Espacio entre columnas
    $anchoID = 12;
    $anchoCantidad = 18;
    $anchoProducto = $anchoColumna - $anchoID - $anchoCantidad;
    
    // Primera columna - Encabezados
    $pdf->SetFont('Arial', 'B', 8);
    $posicionInicialY = $pdf->GetY(); // Guardar posición Y inicial
    $pdf->Cell($anchoID, 5, 'ID', 1, 0, 'C');
    $pdf->Cell($anchoProducto, 5, 'Producto', 1, 0, 'C');
    $pdf->Cell($anchoCantidad, 5, 'Cantidad', 1, 0, 'C');
    
    // Segunda columna - Encabezados (si hay productos suficientes)
    if ($totalProductos > $productosColumna1) {
        $pdf->SetX($pdf->GetX() + $espacioEntreColumnas);
        $pdf->Cell($anchoID, 5, 'ID', 1, 0, 'C');
        $pdf->Cell($anchoProducto, 5, 'Producto', 1, 0, 'C');
        $pdf->Cell($anchoCantidad, 5, 'Cantidad', 1, 0, 'C');
    }
    $pdf->Ln();
    
    // Mostrar productos en dos columnas
    $pdf->SetFont('Arial', '', 7);
    $alturaMaxima = 0;
    
    // Primera columna - Datos
    for ($i = 0; $i < $productosColumna1; $i++) {
        if (isset($productosParaMostrar[$i])) {
            $producto = $productosParaMostrar[$i];
            $alturaFila = 5; // Altura estándar de la fila
            
            $posYAntes = $pdf->GetY();
            $pdf->Cell($anchoID, $alturaFila, $producto['id'], 1, 0, 'C');
            $pdf->Cell($anchoProducto, $alturaFila, $producto['nombre'], 1, 0, 'L');
            $pdf->Cell($anchoCantidad, $alturaFila, $producto['cantidad'], 1, 0, 'C');
            
            // Si hay segunda columna, mostrar el producto correspondiente
            if ($totalProductos > $productosColumna1 && isset($productosParaMostrar[$i + $productosColumna1])) {
                $producto2 = $productosParaMostrar[$i + $productosColumna1];
                $pdf->SetX($pdf->GetX() + $espacioEntreColumnas);
                $pdf->Cell($anchoID, $alturaFila, $producto2['id'], 1, 0, 'C');
                $pdf->Cell($anchoProducto, $alturaFila, $producto2['nombre'], 1, 0, 'L');
                $pdf->Cell($anchoCantidad, $alturaFila, $producto2['cantidad'], 1, 0, 'C');
            }
            
            $pdf->Ln();
            $alturaActual = $pdf->GetY() - $posYAntes;
            $alturaMaxima = max($alturaMaxima, $alturaActual);
        }
    }
    
    // Agregar pie de página
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 4, 'Gracias por su pedido. Para cualquier consulta contacte a sistemacheesepizza@gmail.com', 0, 1, 'C');
    $pdf->Cell(0, 4, 'CHEESE PIZZA ALMACEN - Av. Independecia #112,Jesus Maria, Ags. - Tel:', 0, 1, 'C');
    
    
    // Guardar PDF
    $pdf->Output($pdfPath, 'F');

    // Enviar correo
    $emailSent = enviarCorreoPDF($pdfPath, $pedidoId, $nombreUsuario);

    $pdo->commit();

    $response = [
        'success'   => true,
        'message'   => 'Pedido procesado correctamente' .
                      ($emailSent ? ' y enviado por correo' : ' pero no se pudo enviar el correo'),
        'redirect'  => "ver_pedido.php?id={$pedidoId}",
        'pdf_url'   => 'pedidos/' . $pdfFilename,
        'emailSent' => $emailSent
    ];

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error de base de datos en procesar_pedidos.php: " . $e->getMessage());
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en procesar_pedidos.php: " . $e->getMessage());
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Salida siempre en JSON limpio
if (ob_get_length()) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
