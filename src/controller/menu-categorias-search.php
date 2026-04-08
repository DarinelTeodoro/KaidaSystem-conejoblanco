<?php
include('../model/querys.php');
$response = array();
$id = $_POST['id'];

$data_categoria = consultar_categoria($id);

if ($data_categoria) {
    $response['status'] = 'EXITO';
    $response['idcat'] = $data_categoria['id'];
    $response['categoria'] = $data_categoria['categoria'];
    $response['descripcion'] = $data_categoria['descripcion'];
    $response['destino'] = $data_categoria['destino'];
} else {
    $response['status'] = 'ERROR';
}

echo json_encode($response);
?>