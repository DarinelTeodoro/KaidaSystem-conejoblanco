<?php
include('../model/conexion.php');
$response = array();
$id = $_POST['id_grupo'];

$conexion = new Conexion();
$query_delete_group = $conexion->prepare('DELETE FROM menu_combos_grupos WHERE id = :id');
$query_delete_group->bindParam(':id', $id);
$query_delete_group->execute();

if ($query_delete_group) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Sección Eliminada';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo eliminar la sección. Intente nuevamente.';
}

echo json_encode($response);
?>