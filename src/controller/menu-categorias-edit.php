<?php
include('../model/querys.php');
$response = array();

$id = $_POST['id_editcategoria'];
$categoria = $_POST['name_editcategoria'];
$descripcion = $_POST['descripcion_editcategoria'];
$destino = $_POST['destino_editcategoria'];



$conexion = new Conexion();
$edit_categoria = $conexion->prepare('UPDATE menu_categorias SET categoria=:cat, descripcion=:desp, destino=:dest WHERE id=:id');
$edit_categoria->bindParam(':id', $id);
$edit_categoria->bindParam(':cat', $categoria);
$edit_categoria->bindParam(':desp', $descripcion);
$edit_categoria->bindParam(':dest', $destino);
$edit_categoria->execute();


if ($edit_categoria) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Datos Actualizados';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo actualizar la información de la categoria. Intente de nuevo.';
}

echo json_encode($response);
?>