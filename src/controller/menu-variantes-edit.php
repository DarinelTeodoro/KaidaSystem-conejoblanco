<?php
include('../model/querys.php');
$response = array();

$id = $_POST['id_editvariante'];
$variante = $_POST['name_editvariante'];
$incremento = $_POST['precio_editvariante'];



$conexion = new Conexion();
$edit_variante = $conexion->prepare('UPDATE menu_variantes SET variante=:var, incremento=:incremento WHERE id=:id');
$edit_variante->bindParam(':id', $id);
$edit_variante->bindParam(':var', $variante);
$edit_variante->bindParam(':incremento', $incremento);
$edit_variante->execute();


if ($edit_variante) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Datos Actualizados';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo actualizar variante. Intente de nuevo.';
}

echo json_encode($response);
?>