<?php
session_start();
date_default_timezone_set('America/Mexico_City');
header('Content-Type: application/json');
include('../model/conexion.php');

$fecha = date('Y-m-d');

try {
    $cnx = new Conexion();

    // Obtener todas las comandas pendientes
    $stmt = $cnx->prepare("
        SELECT *
        FROM comandas 
        WHERE estado = 'pendiente'
        AND barra = 1
        ORDER BY id ASC
    ");

    $stmt->execute();
    $comandas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultado = [];

    foreach ($comandas as $comanda) {
        // Obtener batches de la comanda
        $stmtB = $cnx->prepare("SELECT * FROM comanda_batches WHERE comanda_id = :id ORDER BY seq DESC");
        $stmtB->execute([':id' => $comanda['id']]);
        $batches = $stmtB->fetchAll(PDO::FETCH_ASSOC);

        $batchesData = [];
        $totalComanda = 0;
        $comandaTieneItemsBarra = false; // Bandera para saber si la comanda tiene al menos un batch con items

        foreach ($batches as $batch) {
            // Obtener items del batch que sean de barra o ambos
            $stmtI = $cnx->prepare("SELECT * FROM comanda_items WHERE comanda_id = :cid AND batch_id = :bid AND (destino = 'Barra' OR destino = 'Ambos') AND ready_barra = 0 AND tipo != 'extra' ORDER BY id ASC");
            $stmtI->execute([':cid' => $comanda['id'], ':bid' => $batch['id']]);
            $items = $stmtI->fetchAll(PDO::FETCH_ASSOC);

            // Si no hay items de barra en este batch, saltamos este batch completamente
            if (empty($items)) {
                continue; // No agregamos este batch al resultado
            }

            $itemsData = [];
            $batchTieneItems = false; // Bandera para este batch específico

            foreach ($items as $item) {
                // Obtener componentes del item que sean de barra
                $stmtComp = $cnx->prepare("SELECT * FROM comanda_item_componentes WHERE item_id = :iid AND destino = 'Barra' ORDER BY id ASC");
                $stmtComp->execute([':iid' => $item['id']]);
                $componentes = $stmtComp->fetchAll(PDO::FETCH_ASSOC);


                $stmtE = $cnx->prepare("SELECT * FROM comanda_items WHERE item_id = :id ORDER BY id ASC");
                $stmtE->execute([':id' => $item['id']]);
                $itemExt = $stmtE->fetchAll(PDO::FETCH_ASSOC);

                $subtotal = (float) $item['precio'] * (int) $item['qty'];
                $extras = [];
                $componentesAgrupados = [];

                foreach ($itemExt as $ext) {
                    $totalExtra = (float) $ext['precio'] * (int) $ext['qty'];
                    $extras[] = [
                        'nombre' => $ext['nombre'],
                        'qty' => $ext['qty'],
                        'precio' => $ext['precio'],
                        'total' => $totalExtra
                    ];
                    $subtotal += $totalExtra;
                }

                // Procesar componentes
                foreach ($componentes as $comp) {
                    $grupoId = $comp['grupo_id'] ?? 0;
                    $grupoNombre = $comp['grupo_nombre'] ?? '';

                    if (!isset($componentesAgrupados[$grupoId])) {
                        $componentesAgrupados[$grupoId] = [
                            'nombre' => $grupoNombre,
                            'items' => []
                        ];
                    }
                    $componentesAgrupados[$grupoId]['items'][] = [
                        'nombre' => $comp['nombre'],
                        'kind' => $comp['kind']
                    ];
                }

                $itemsData[] = [
                    'id' => $item['id'],
                    'nombre' => $item['nombre'],
                    'tipo' => $item['tipo'],
                    'qty' => $item['qty'],
                    'precio' => $item['precio'],
                    'nota' => $item['nota'],
                    'subtotal' => $subtotal,
                    'extras' => $extras,
                    'componentes' => $componentesAgrupados
                ];

                $totalComanda += $subtotal;
                $batchTieneItems = true;
                $comandaTieneItemsBarra = true;
            }

            // Solo agregar el batch si tiene items
            if ($batchTieneItems) {
                $batchesData[] = [
                    'id' => $batch['id'],
                    'seq' => $batch['seq'],
                    'created_at' => $batch['created_at'],
                    'items' => $itemsData
                ];
            }
        }

        // Solo agregar la comanda si tiene al menos un batch con items
        if ($comandaTieneItemsBarra && !empty($batchesData)) {
            // Obtener nombre del mesero
            $stmtUser = $cnx->prepare("SELECT name FROM usuarios WHERE username = :id");
            $stmtUser->execute([':id' => $comanda['user_id']]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            // Determinar cliente/mesa
            $clienteInfo = ($comanda['tipo'] === 'mesa')
                ? 'Mesa ' . $comanda['mesa']
                : 'Para Llevar';

            $resultado[] = [
                'id' => $comanda['id'],
                'tipo' => $comanda['tipo'],
                'cliente_info' => $clienteInfo,
                'fecha' => $comanda['created_at'],
                'mesero' => $user['name'] ?? 'Sin asignar',
                'batches' => $batchesData,
                'total' => $totalComanda
            ];
        }
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>