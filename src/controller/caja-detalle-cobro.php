<?php
session_start();
include('../model/querys.php');

function list_descuentos($comanda_id)
{
    $cnx = new Conexion();
    $list_descuentos = $cnx->prepare("SELECT * FROM descuentos WHERE id_comanda = :id ORDER BY id ASC");
    $list_descuentos->execute([':id' => $comanda_id]);

    return $list_descuentos->fetchAll(PDO::FETCH_ASSOC);
}

function suma($comanda_id)
{
    $cnx = new Conexion();
    $sum_descuentos = $cnx->prepare("SELECT SUM(descuento) as descuento FROM descuentos WHERE id_comanda = :id");
    $sum_descuentos->execute([':id' => $comanda_id]);

    return $sum_descuentos->fetch(PDO::FETCH_ASSOC);
}

$comanda_id = (int) ($_POST['id'] ?? 0);
if (!$comanda_id) {
    http_response_code(400);
    exit('ID de comanda no válido');
}

$cnx = new Conexion();

// Obtener datos de la comanda
$stmtComanda = $cnx->prepare("SELECT * FROM comandas WHERE id = :id");
$stmtComanda->execute([':id' => $comanda_id]);
$comanda = $stmtComanda->fetch(PDO::FETCH_ASSOC);

if (!$comanda) {
    http_response_code(404);
    exit('Comanda no encontrada');
}

$descuentos = list_descuentos($comanda_id);

if (!$descuentos) {
    $cantidad_descuento = 0;
} else {
    $data_descuento = suma($comanda_id);
    $cantidad_descuento = floatval($data_descuento['descuento']);
}

$mesero = consultar_usuario($comanda['user_id']);
$name_mesero = $mesero['name'];

// 🔴 Obtener SOLO batches que tienen items (consulta más eficiente)
$stmtBatches = $cnx->prepare("
    SELECT DISTINCT b.* 
    FROM comanda_batches b
    INNER JOIN comanda_items i ON b.id = i.batch_id
    WHERE b.comanda_id = :id 
    ORDER BY b.seq ASC
");
$stmtBatches->execute([':id' => $comanda_id]);
$batches = $stmtBatches->fetchAll(PDO::FETCH_ASSOC);

$detalle = [
    'comanda' => $comanda,
    'batches' => [],
    'total' => 0,
    'mesero' => $name_mesero
];

$totalGeneral = 0;

foreach ($batches as $batch) {
    $batchDetalle = [
        'id' => $batch['id'],
        'seq' => $batch['seq'],
        'created_at' => $batch['created_at'],
        'items' => []
    ];

    // Obtener items del batch
    $stmtItems = $cnx->prepare("SELECT * FROM comanda_items WHERE comanda_id = :cid AND batch_id = :bid AND tipo != 'extra' ORDER BY id ASC");
    $stmtItems->execute([':cid' => $comanda_id, ':bid' => $batch['id']]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $itemDetalle = [
            'id' => $item['id'],
            'tipo' => $item['tipo'],
            'nombre' => $item['nombre'],
            'qty' => $item['qty'],
            'precio' => $item['precio'],
            'nota' => $item['nota'],
            'componentes' => [],
            'extras' => [],
            'subtotal' => $item['precio'] * $item['qty']
        ];

        // Obtener componentes
        $stmtComp = $cnx->prepare("SELECT * FROM comanda_item_componentes WHERE item_id = :iid ORDER BY id ASC");
        $stmtComp->execute([':iid' => $item['id']]);
        $componentes = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

        $stmtExtras = $cnx->prepare("SELECT * FROM comanda_items WHERE item_id = :id ORDER BY id ASC");
        $stmtExtras->execute([':id' => $item['id']]);
        $array_extras = $stmtExtras->fetchAll(PDO::FETCH_ASSOC);

        foreach ($array_extras as $ext) {
            $itemDetalle['extras'][] = [
                'nombre' => $ext['nombre'],
                'qty' => $ext['qty'],
                'precio' => $ext['precio'],
                'total' => $ext['precio'] * $ext['qty']
            ];
            $itemDetalle['subtotal'] += $ext['precio'] * $ext['qty'];
        }

        foreach ($componentes as $comp) {
            if ($item['tipo'] === 'combo') {
                $grupo = $comp['grupo_nombre'] ?? 'Otros';
                $itemDetalle['componentes'][$grupo][] = [
                    'nombre' => $comp['nombre'],
                    'kind' => $comp['kind']
                ];
            } else {
                $itemDetalle['componentes'][] = [
                    'nombre' => $comp['nombre'],
                    'kind' => $comp['kind']
                ];
            }
        }

        $batchDetalle['items'][] = $itemDetalle;
        $totalGeneral += $itemDetalle['subtotal'];
    }

    $detalle['batches'][] = $batchDetalle;
}

$detalle['total'] = $totalGeneral - $cantidad_descuento;

header('Content-Type: application/json');
echo json_encode($detalle);
?>