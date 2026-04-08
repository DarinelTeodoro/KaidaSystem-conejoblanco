<?php
include('../model/querys.php');
$response = array();
$id = $_POST['user'];

$data_user = consultar_usuario($id);

if ($data_user) {
    $response['status'] = 'EXITO';
    $response['user'] = $data_user['username'];
    $response['nombre'] = $data_user['name'];
    $response['rol'] = $data_user['rol'];
    $response['foto'] = $data_user['photo'];
} else {
    $response['status'] = 'ERROR';
}

echo json_encode($response);
?>