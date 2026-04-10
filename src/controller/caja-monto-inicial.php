<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include('../model/conexion.php');
$response = array();

$fecha = date('Y-m-d H:i:s');
$cantidad = $_POST['amount_caja'];

$conexion = new Conexion();
$init = $conexion->prepare('INSERT INTO caja(inicial, corte, usuario, cantidad_real, fecha_inicial, fecha) VALUES (:inicial, :corte, :usuario, :real, :fecha_init, :fecha)');
$init->bindParam(':inicial', $cantidad);
$init->bindParam(':corte', $cantidad);
$init->bindParam(':usuario', $_SESSION['data-useractive']);
$init->bindParam(':real', $cantidad);
$init->bindParam(':fecha_init', $fecha);
$init->bindParam(':fecha', $fecha);
$init->execute();


if ($init) {
    $update = $conexion->prepare('UPDATE servicio SET caja = 1 WHERE id = 1');
    $update->execute();

    $response['status'] = 'EXITO';
    $response['message'] = 'Monto Ingresado';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo ingresar la cantidad. Intente de nuevo.';
}

echo json_encode($response);
?>