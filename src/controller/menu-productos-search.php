<?php
include('../model/querys.php');
$response = array();
$id = $_POST['id'];

$data_producto = consultar_producto($id);

if ($data_producto) {
    $response['status'] = 'EXITO';
    $response['idcat'] = $data_producto['id'];
    $response['producto'] = $data_producto['producto'];
    $response['descripcion'] = $data_producto['descripcion'];
    $response['photo'] = $data_producto['photo'];
    $response['precio'] = $data_producto['precio'];
    $response['categoria'] = $data_producto['id_categoria'];
} else {
    $response['status'] = 'ERROR';
}

echo json_encode($response);
?>