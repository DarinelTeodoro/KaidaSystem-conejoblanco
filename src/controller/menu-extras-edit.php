<?php
include('../model/querys.php');
$response = array();

$id = $_POST['id_editextra'];
$extra = $_POST['name_editextra'];
$precio = $_POST['precio_editextra'];
$destino = $_POST['destino_editextra'];



$conexion = new Conexion();
$edit_extra = $conexion->prepare('UPDATE menu_extras SET extra=:ext, destino=:dest, precio=:precio WHERE id=:id');
$edit_extra->bindParam(':id', $id);
$edit_extra->bindParam(':ext', $extra);
$edit_extra->bindParam(':dest', $destino);
$edit_extra->bindParam(':precio', $precio);
$edit_extra->execute();


if ($edit_extra) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Datos Actualizados';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo actualizar la información del extra. Intente de nuevo.';
}

echo json_encode($response);
?>