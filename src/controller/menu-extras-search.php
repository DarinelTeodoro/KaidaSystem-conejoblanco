<?php
include('../model/querys.php');
$response = array();
$id = $_POST['id'];

$data_extra = consultar_extra($id);

if ($data_extra) {
    $response['status'] = 'EXITO';
    $response['idext'] = $data_extra['id'];
    $response['extra'] = $data_extra['extra'];
    $response['precio'] = $data_extra['precio'];
    $response['destino'] = $data_extra['destino'];
} else {
    $response['status'] = 'ERROR';
}

echo json_encode($response);
?>