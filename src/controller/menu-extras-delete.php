<?php
include('../model/conexion.php');
$response = array();
$id = $_POST['id'];

$conexion = new Conexion();
$query_delete_extra = $conexion->prepare('DELETE FROM menu_extras WHERE id = :id');
$query_delete_extra->bindParam(':id', $id);
$query_delete_extra->execute();

if ($query_delete_extra) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Extra Eliminado';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo eliminar extra. Intente nuevamente.';
}

echo json_encode($response);
?>