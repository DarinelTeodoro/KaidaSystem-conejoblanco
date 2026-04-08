<?php
include('../model/conexion.php');
$response = array();
$id = $_POST['id'];

$conexion = new Conexion();
$query_delete_variante = $conexion->prepare('DELETE FROM menu_variantes WHERE id = :id');
$query_delete_variante->bindParam(':id', $id);
$query_delete_variante->execute();

if ($query_delete_variante) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Variante Eliminado';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo eliminar variante. Intente nuevamente.';
}

echo json_encode($response);
?>