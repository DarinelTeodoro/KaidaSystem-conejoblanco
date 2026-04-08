<?php
include('../model/conexion.php');
$response = array();

$producto = $_POST['producto_addvariante'];
$variante = $_POST['name_addvariante'];
$precio = $_POST['precio_addvariante'];


$conexion = new Conexion();
$insert_variante = $conexion->prepare('INSERT INTO menu_variantes(id_producto, variante, incremento) VALUES (:idproducto, :variante, :incremento)');
$insert_variante->bindParam(':idproducto', $producto);
$insert_variante->bindParam(':variante', $variante);
$insert_variante->bindParam(':incremento', $precio);
$insert_variante->execute();

if ($insert_variante) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Nueva Variante';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo crear una variante. Intente de nuevo.';
}

echo json_encode($response);
?>