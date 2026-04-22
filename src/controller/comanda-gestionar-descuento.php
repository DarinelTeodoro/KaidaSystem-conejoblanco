<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include('../model/conexion.php');
$response = array();

$comanda = $_POST['id_comanda'];
$tipo = $_POST['tipo_descuento'];
$valor = $_POST['valor_descuento'];
$total_pagar = floatval($_POST['total_pagar']);

if ($tipo == 'porcentaje') {
    $descuento = ($total_pagar / 100) * $valor;
} else {
    $descuento = $_POST['valor_descuento'];
}

$motivo = $_POST['motivo'];

$conexion = new Conexion();
$init = $conexion->prepare('INSERT INTO descuentos(id_comanda, tipo_descuento, valor_descuento, descuento, motivo, usuario) VALUES (:comanda, :tipo, :valor, :descuento, :motivo, :usuario)');
$init->bindParam(':comanda', $comanda);
$init->bindParam(':tipo', $tipo);
$init->bindParam(':valor', $valor);
$init->bindParam(':descuento', $descuento);
$init->bindParam(':motivo', $motivo);
$init->bindParam(':usuario', $_SESSION['data-useractive']);
$init->execute();


if ($init) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Operacion Exitosa';
    $response['comanda'] = $comanda;
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo cargar la información. Intente nuevamente';
}

echo json_encode($response);
?>