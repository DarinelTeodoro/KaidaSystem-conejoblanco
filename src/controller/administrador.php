<?php
session_start();
include('../model/conexion.php');
$columna = $_POST['ubicacion'];
function actualizar($columna) {
    $conexion = new Conexion();
    $sql = $conexion->prepare('UPDATE servicio SET visto_'.$columna.' = 1 WHERE id = 1');
    $sql->execute();
}
if (!empty($_SESSION['data-useractive'])) {
    actualizar($columna);
}
?>