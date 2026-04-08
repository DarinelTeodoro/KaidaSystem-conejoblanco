<?php
include('../model/conexion.php');
$response = array();

$categoria = $_POST['name_addcategoria'];
$descripcion = $_POST['descripcion_addcategoria'];
$destino = $_POST['destino_addcategoria'];


$conexion = new Conexion();
$insert_categoria = $conexion->prepare('INSERT INTO menu_categorias(categoria, descripcion, destino) VALUES (:categoria, :descripcion, :destino)');
$insert_categoria->bindParam(':categoria', $categoria);
$insert_categoria->bindParam(':descripcion', $descripcion);
$insert_categoria->bindParam(':destino', $destino);
$insert_categoria->execute();

if ($insert_categoria) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Nueva Categoria';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo crear una nueva categoria. Intente de nuevo.';
}

echo json_encode($response);
?>