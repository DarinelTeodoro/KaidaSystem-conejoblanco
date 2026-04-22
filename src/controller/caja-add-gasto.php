<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include('../model/conexion.php');
$response = array();

$fecha = date('Y-m-d H:i:s');
$cantidad = floatval($_POST['monto_gasto']);
$concepto = $_POST['concepto_gasto'];
$tipo = $_POST['tipo_movimiento'];

$conexion = new Conexion();
$init = $conexion->prepare('INSERT INTO gastos_extras(cantidad, fecha, concepto, usuario, tipo) VALUES (:cantidad, :fecha, :concepto, :usuario, :tipo)');
$init->bindParam(':cantidad', $cantidad);
$init->bindParam(':fecha', $fecha);
$init->bindParam(':concepto', $concepto);
$init->bindParam(':usuario', $_SESSION['data-useractive']);
$init->bindParam(':tipo', $tipo);
$init->execute();


if ($init) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Operacion Exitosa';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo enviar la información. Intente nuevamente';
}

echo json_encode($response);
?>