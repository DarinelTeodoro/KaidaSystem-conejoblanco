<?php
include('../model/conexion.php');
$response = array();
$id = $_POST['id'];

$conexion = new Conexion();
$query_delete_categoria = $conexion->prepare('DELETE FROM menu_categorias WHERE id = :id');
$query_delete_categoria->bindParam(':id', $id);
$query_delete_categoria->execute();

if ($query_delete_categoria) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Categoria Eliminada';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo eliminar la categoria. Intente nuevamente.';
}

echo json_encode($response);
?>