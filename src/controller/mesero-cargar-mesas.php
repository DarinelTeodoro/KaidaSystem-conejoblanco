<?php
session_start();
include('../model/querys.php');

if (empty($_SESSION['data-useractive'])) {
    http_response_code(401);
    exit('No autorizado');
}

$comanda_id = isset($_POST['comanda_id']) ? (int)$_POST['comanda_id'] : 0;

$countmesas = n_mesas();
$max_mesas = $countmesas['n_mesas'];
$min_mesas = 1;

$conexion = new Conexion();

// Obtener la mesa de la comanda actual (si estamos editando)
$mesa_actual = 0;
if ($comanda_id > 0) {
    $query_mesa = $conexion->prepare("SELECT mesa FROM comandas WHERE id = :id");
    $query_mesa->execute([':id' => $comanda_id]);
    $mesa_actual = (int)$query_mesa->fetchColumn();
}

// Consultar mesas ocupadas por OTRAS comandas (excluyendo la actual)
$query = $conexion->prepare("
    SELECT DISTINCT mesa 
    FROM comandas 
    WHERE estado = 'pendiente' 
    AND mesa IS NOT NULL 
    AND tipo = 'mesa'
    AND id != :comanda_id
");
$query->bindParam(':comanda_id', $comanda_id, PDO::PARAM_INT);
$query->execute();
$mesas_ocupadas = $query->fetchAll(PDO::FETCH_COLUMN);

$mesas_ocupadas_array = array_flip($mesas_ocupadas);

$options = '<option value="0">Selecciona una mesa</option>';

for ($i = $min_mesas; $i <= $max_mesas; $i++) {
    $ocupada_por_otro = isset($mesas_ocupadas_array[$i]);
    $disabled = $ocupada_por_otro ? 'disabled' : '';
    $selected = ($i == $mesa_actual) ? 'selected' : '';
    $texto = 'Mesa - ' . $i;
    
    if ($ocupada_por_otro) {
        $texto .= ' (Ocupada)';
    } elseif ($i == $mesa_actual) {
        $texto .= ' (Actual)';
    }
    
    $options .= '<option value="' . $i . '" ' . $disabled . ' ' . $selected . '>' . $texto . '</option>';
}

echo $options;
?>