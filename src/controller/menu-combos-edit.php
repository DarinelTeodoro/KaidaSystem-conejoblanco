<?php
include('../model/querys.php');
$response = array();

$id = $_POST['id_editcombo'];
$combo = $_POST['name_editcombo'];
$descripcion = $_POST['descripcion_editcombo'];
$precio = $_POST['precio_editcombo'];



$conexion = new Conexion();
$edit_combo = $conexion->prepare('UPDATE menu_combos SET combo=:combo, descripcion=:desp, precio=:precio WHERE id=:id');
$edit_combo->bindParam(':id', $id);
$edit_combo->bindParam(':combo', $combo);
$edit_combo->bindParam(':desp', $descripcion);
$edit_combo->bindParam(':precio', $precio);
$edit_combo->execute();


if ($edit_combo) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Datos Actualizados';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo actualizar la información del combo. Intente de nuevo.';
}

echo json_encode($response);
?>