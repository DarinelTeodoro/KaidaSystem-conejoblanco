<?php
include('../model/conexion.php');
$response = array();

$id_combo = $_POST['id_offcanvascombo'];
$grupo = $_POST['offcanvas-nameseccion'];
$tipo = $_POST['offcanvas-tiposeccion'];

$productos = $_POST['checkbox_producto'] ?? [];
$productos = array_values(array_unique(array_map('intval', (array)$productos)));
$productos_json = json_encode($productos, JSON_UNESCAPED_UNICODE);

$conexion = new Conexion();
$combo_group = $conexion->prepare('INSERT INTO menu_combos_grupos(id_combo, grupo, tipo, productos) VALUES (:id_combo, :grupo, :tipo, :arrayprod)');
$combo_group->bindParam(':id_combo', $id_combo);
$combo_group->bindParam(':grupo', $grupo);
$combo_group->bindParam(':tipo', $tipo);
$combo_group->bindParam(':arrayprod', $productos_json);
$combo_group->execute();

if ($combo_group) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Nueva Seccion Agregada al Combo';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo crear la seccion. Intente de nuevo.';
}

echo json_encode($response);
?>