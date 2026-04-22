<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include('../model/conexion.php');
$response = array();

$id = $_POST['id'];


$conexion = new Conexion();
$stmt = $conexion->prepare('SELECT * FROM descuentos WHERE id = :id');
$stmt->bindParam(':id', $id);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$id_comanda = $result['id_comanda'];


$sql = $conexion->prepare('DELETE FROM descuentos WHERE id = :id');
$sql->bindParam(':id', $id);
$sql->execute();



if ($sql) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Operacion Exitosa';
    $response['comanda'] = $id_comanda;
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo eliminar el registro. Intente nuevamente';
}

echo json_encode($response);
?>