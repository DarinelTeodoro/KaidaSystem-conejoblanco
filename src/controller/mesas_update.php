<?php
include('../model/querys.php');
$response = array();

$nmesas = $_POST['n_mesas'];
$keyword = 'mesas_max';


$conexion = new Conexion();
$update_mesas = $conexion->prepare('UPDATE mesas SET n_mesas=:nmesas WHERE keyword=:keyword');
$update_mesas->bindParam(':nmesas', $nmesas);
$update_mesas->bindParam(':keyword', $keyword);
$update_mesas->execute();


if ($update_mesas) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Actualización Exitosa';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo actualizar la información. Intente de nuevo.';
}

echo json_encode($response);
?>