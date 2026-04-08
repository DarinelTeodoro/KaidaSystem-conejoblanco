<?php
include('../model/querys.php');
$response = array();
$id = $_POST['id'];

$data_variante = consultar_variante($id);

if ($data_variante) {
    $response['status'] = 'EXITO';
    $response['idvar'] = $data_variante['id'];
    $response['variante'] = $data_variante['variante'];
    $response['incremento'] = $data_variante['incremento'];
} else {
    $response['status'] = 'ERROR';
}

echo json_encode($response);
?>