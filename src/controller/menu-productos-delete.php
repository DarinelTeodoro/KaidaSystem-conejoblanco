<?php
include('../model/conexion.php');
$response = array();
$id = $_POST['id'];

$conexion = new Conexion();
$query_delete_producto = $conexion->prepare('DELETE FROM menu_productos WHERE id = :id');
$query_delete_producto->bindParam(':id', $id);
$query_delete_producto->execute();

if ($query_delete_producto) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Producto Eliminado';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo eliminar el producto. Intente nuevamente.';
}

echo json_encode($response);
?>