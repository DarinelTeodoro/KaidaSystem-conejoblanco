<?php
session_start();
include('../model/querys.php');

$comanda_id = (int)($_POST['id'] ?? 0);
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

foreach ($batches as $batch) {
    $batchDetalle = [
        'id' => $batch['id'],
        'seq' => $batch['seq'],
        'created_at' => $batch['created_at'],
        'items' => []
    ];

    // Obtener items del batch
    $stmtItems = $cnx->prepare("SELECT * FROM comanda_items WHERE comanda_id = :cid AND batch_id = :bid ORDER BY id ASC");
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

        foreach ($componentes as $comp) {
            if ($comp['kind'] === 'extra') {
                $itemDetalle['extras'][] = [
                    'nombre' => $comp['nombre'],
                    'qty' => $comp['qty'],
                    'precio' => $comp['precio'],
                    'total' => $comp['precio'] * $comp['qty']
                ];
                $itemDetalle['subtotal'] += $comp['precio'] * $comp['qty'];
            } else {
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
        }

        $batchDetalle['items'][] = $itemDetalle;
        $detalle['total'] += $itemDetalle['subtotal'];
    }

    $detalle['batches'][] = $batchDetalle;
}

header('Content-Type: application/json');
echo json_encode($detalle);
?>