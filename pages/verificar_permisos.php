<?php
// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}

// Función para verificar si el usuario es admin
function esAdmin() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] == 'admin';
}

// Función para verificar si el usuario es encargado
function esEncargado() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] == 'encargado';
}
?>