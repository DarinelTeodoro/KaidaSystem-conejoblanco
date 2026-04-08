<?php
include('../model/conexion.php');
$response = array();

$extra = $_POST['name_addextra'];
$precio = $_POST['precio_addextra'];
$destino = $_POST['destino_addextra'];


$conexion = new Conexion();
$insert_extra = $conexion->prepare('INSERT INTO menu_extras(extra, destino, precio) VALUES (:extra, :destino, :precio)');
$insert_extra->bindParam(':extra', $extra);
$insert_extra->bindParam(':destino', $destino);
$insert_extra->bindParam(':precio', $precio);
$insert_extra->execute();

if ($insert_extra) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Nuevo Extra';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo crear un extra. Intente de nuevo.';
}

echo json_encode($response);
?>