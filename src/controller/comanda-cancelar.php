<?php
include('../model/conexion.php');
$comanda_id = $_POST['id-comanda'];
$motivo = $_POST['motivo-cancelacion'];

$conexion = new Conexion();
$query = $conexion->prepare('UPDATE comandas SET estado = "cancelado", motivo_cancelacion = :motivo WHERE id = :id');
$query->bindParam(':motivo', $motivo);
$query->bindParam(':id', $comanda_id);
$query->execute();

echo $comanda_id;
?>