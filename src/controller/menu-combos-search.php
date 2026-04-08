<?php
include('../model/querys.php');
$response = array();
$id = $_POST['id'];

$data_combo = consultar_combo($id);

if ($data_combo) {
    $response['status'] = 'EXITO';
    $response['idcom'] = $data_combo['id'];
    $response['combo'] = $data_combo['combo'];
    $response['descripcion'] = $data_combo['descripcion'];
    $response['precio'] = $data_combo['precio'];
} else {
    $response['status'] = 'ERROR';
}

echo json_encode($response);
?>