<?php
include('../model/conexion.php');
$response = array();
$id = $_POST['id_user'];

$conexion = new Conexion();
$query_delete_user = $conexion->prepare('DELETE FROM usuarios WHERE id = :id');
$query_delete_user->bindParam(':id', $id);
$query_delete_user->execute();

if ($query_delete_user) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Usuario Eliminado';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo eliminar el usuario. Intente nuevamente.';
}

echo json_encode($response);
?>