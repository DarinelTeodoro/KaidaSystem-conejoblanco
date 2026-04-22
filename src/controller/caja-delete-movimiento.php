<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include('../model/conexion.php');
$response = array();

$id = $_POST['id'];

$conexion = new Conexion();
$init = $conexion->prepare('DELETE FROM gastos_extras WHERE id = :id');
$init->bindParam(':id', $id);
$init->execute();


if ($init) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Operacion Exitosa';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo borrar el dato. Intente nuevamente';
}

echo json_encode($response);
?>