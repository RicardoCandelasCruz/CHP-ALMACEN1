<?php

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/Auth.php';

if (empty($_GET['id'])) {
    header("Location: lista_pedidos.php");
    exit();
}

$pedidoId = (int)$_GET['id'];
$pdfPath = __DIR__ . "/../pedidos/pedido_{$pedidoId}.pdf";

if (file_exists($pdfPath)) {
    header('Content-type: application/pdf');
    header('Content-Disposition: inline; filename="pedido_' . $pedidoId . '.pdf"');
    readfile($pdfPath);
    exit();
} else {
    die("El pedido solicitado no existe o no tiene PDF generado.");
}