<?php
date_default_timezone_set('America/Mexico_City');
include('../model/conexion.php');
$fecha = date('Y-m-d H:i:s');

$comanda_id = 86;
$data = json_decode($_POST['payload'], true);

$cnx = new Conexion();

// Función para obtener el destino de un producto desde su categoría
function obtenerDestinoProducto($cnx, $producto_id) {
    $query = $cnx->prepare("
        SELECT c.destino 
        FROM menu_productos p
        LEFT JOIN menu_categorias c ON p.id_categoria = c.id
        WHERE p.id = :id
    ");
    $query->execute([':id' => $producto_id]);
    return $query->fetchColumn();
}

// Función para obtener el destino de un extra
function obtenerDestinoExtra($cnx, $extra_id) {
    $query = $cnx->prepare("SELECT destino FROM menu_extras WHERE id = :id");
    $query->execute([':id' => $extra_id]);
    return $query->fetchColumn();
}

  $stmtestado = $cnx->prepare("SELECT estado FROM comandas WHERE id = :id");
  $stmtestado->execute([':id' => $comanda_id]);
  $result_estado = $stmtestado->fetch(PDO::FETCH_ASSOC);
  echo $result_estado['estado'];

?>