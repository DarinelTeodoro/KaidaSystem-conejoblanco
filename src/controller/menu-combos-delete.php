<?php
include('../model/conexion.php');
$response = array();
$id = $_POST['id_combo'];

$conexion = new Conexion();
$query_delete_combo = $conexion->prepare('DELETE FROM menu_combos WHERE id = :id');
$query_delete_combo->bindParam(':id', $id);
$query_delete_combo->execute();

if ($query_delete_combo) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Combo Eliminado';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo eliminar el combo. Intente nuevamente.';
}

echo json_encode($response);
?>