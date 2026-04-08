<?php
header('Content-Type: application/json; charset=utf-8');
include('../model/querys.php');

$extras = data_extras();
$out = [];

if ($extras) {
  foreach ($extras as $e) {
    $out[] = [
      "id" => (int)$e["id"],
      "nombre" => $e["extra"],
      "precio" => (float)$e["precio"],
      "destino" => $e["destino"],
      "disponibilidad" => $e["disponibilidad"],
      "tipo" => strtolower($e["destino"]),
    ];
  }
}

echo json_encode($out);
?>