<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include('../model/conexion.php');
$response = array();

$fecha = date('Y-m-d H:i:s');
$cantidad = intval($_POST['mil']) + intval($_POST['quinientos']) + intval($_POST['docientos']) + intval($_POST['cien']) + intval($_POST['cincuenta']) + intval($_POST['veinte']) + intval($_POST['pesos']) + floatval($_POST['centavos']);

$conexion = new Conexion();
$last = $conexion->prepare('SELECT * FROM caja ORDER BY id DESC LIMIT 1');
$last->execute();
$data_last = $last->fetch(PDO::FETCH_ASSOC);

$ventas = $conexion->prepare("SELECT SUM(recibido) AS recibido, SUM(cambio) AS cambio FROM ventas WHERE tipo_pago = 'efectivo' AND fecha > :fecha_last AND fecha < :fecha_cut");
$ventas->bindParam(":fecha_last", $data_last['fecha']);
$ventas->bindParam(":fecha_cut", $fecha);
$ventas->execute();
$data_ventas = $ventas->fetch(PDO::FETCH_ASSOC);
$real = floatval($data_last['corte']) + (floatval($data_ventas['recibido']) - floatval($data_ventas['cambio']));

$init = $conexion->prepare('INSERT INTO caja(inicial, corte, usuario, cantidad_real, fecha) VALUES (:inicial, :corte, :usuario, :real, :fecha)');
$init->bindParam(':inicial', $data_last['corte']);
$init->bindParam(':corte', $cantidad);
$init->bindParam(':usuario', $_SESSION['data-useractive']);
$init->bindParam(':real', $real);
$init->bindParam(':fecha', $fecha);
$init->execute();

$residuo = floatval($real) - floatval($cantidad);
if ($residuo > 0) {
    $resultado = 'Faltante: $'.number_format($residuo, 2);
} else if ($residuo < 0) {
    $resultado = 'Restante: $'.number_format($residuo * -1, 2);
} else if ($residuo == 0) {
    $resultado = 'Balance en Orden';
}

if ($init) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Corte Realizado -> '. $resultado;
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo enviar la información. Intente nuevamente';
}

echo json_encode($response);
?>