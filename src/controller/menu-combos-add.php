<?php
include('../model/conexion.php');
$response = array();

$combo = $_POST['name_addcombo'];
$descripcion = $_POST['descripcion_addcombo'];
$precio = $_POST['precio_addcombo'];


$conexion = new Conexion();
$insert_combo = $conexion->prepare('INSERT INTO menu_combos(combo, descripcion, precio) VALUES (:combo, :descripcion, :precio)');
$insert_combo->bindParam(':combo', $combo);
$insert_combo->bindParam(':descripcion', $descripcion);
$insert_combo->bindParam(':precio', $precio);
$insert_combo->execute();

if ($insert_combo) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Nuevo Combo';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo crear el combo. Intente de nuevo.';
}

echo json_encode($response);
?>