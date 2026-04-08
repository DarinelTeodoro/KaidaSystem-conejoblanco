<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include('../model/conexion.php');
$rol = $_POST['rol'];

$fecha = date('Y-m-d');

$cnx = new Conexion();

// Obtener todas las comandas pendientes
$stmt = $cnx->prepare("
        SELECT *
        FROM comandas 
        WHERE estado != 'cancelado'
        AND cocina != 0
        AND DATE(created_at) = :fecha
        ORDER BY id DESC
    ");

$stmt->bindParam(':fecha', $fecha);
$stmt->execute();
$comandas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($comandas) > 0) {
    foreach ($comandas as $comanda) {
        // Obtener batches de la comanda
        $stmtB = $cnx->prepare("SELECT * FROM comanda_batches WHERE comanda_id = :id ORDER BY seq ASC");
        $stmtB->execute([':id' => $comanda['id']]);
        $batches = $stmtB->fetchAll(PDO::FETCH_ASSOC);

        $batchesData = [];
        $totalComanda = 0;
        $comandaTieneItemsBarra = false;

        foreach ($batches as $batch) {
            // Obtener items del batch que sean de barra o ambos
            $stmtI = $cnx->prepare("SELECT * FROM comanda_items WHERE comanda_id = :cid AND batch_id = :bid AND (destino = 'Cocina' OR destino = 'Ambos') AND ready_cocina = 1 ORDER BY id ASC");
            $stmtI->execute([':cid' => $comanda['id'], ':bid' => $batch['id']]);
            $items = $stmtI->fetchAll(PDO::FETCH_ASSOC);

            if (empty($items)) {
                continue;
            }

            $itemsData = [];
            $batchTieneItems = false;

            foreach ($items as $item) {
                // Obtener componentes del item que sean de barra
                $stmtComp = $cnx->prepare("SELECT * FROM comanda_item_componentes WHERE item_id = :iid AND destino = 'Cocina' ORDER BY id ASC");
                $stmtComp->execute([':iid' => $item['id']]);
                $componentes = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

                $subtotal = (float) $item['precio'] * (int) $item['qty'];
                $extras = [];
                $componentesAgrupados = [];

                // Procesar componentes
                foreach ($componentes as $comp) {
                    if ($comp['kind'] === 'extra') {
                        $totalExtra = (float) $comp['precio'] * (int) $comp['qty'];
                        $extras[] = [
                            'nombre' => $comp['nombre'],
                            'qty' => $comp['qty'],
                            'precio' => $comp['precio'],
                            'total' => $totalExtra
                        ];
                        $subtotal += $totalExtra;
                    } else {
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

            if ($batchTieneItems) {
                $batchesData[] = [
                    'id' => $batch['id'],
                    'seq' => $batch['seq'],
                    'created_at' => $batch['created_at'],
                    'items' => $itemsData
                ];
            }
        }

        if ($comandaTieneItemsBarra && !empty($batchesData)) {
            // Obtener nombre del mesero
            $stmtUser = $cnx->prepare("SELECT name FROM usuarios WHERE username = :id");
            $stmtUser->execute([':id' => $comanda['user_id']]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
            $nombreMesero = $user ? $user['name'] : 'Desconocido';

            // Formatear la salida HTML con una estructura clara
            ?>
            <div style="border: 1px solid #000; margin-bottom: 20px; padding: 15px 15px 5px; border-radius: 8px; background: #f9f9f9;">
                <!-- Encabezado de la comanda -->
                <div
                    style="display: flex; justify-content: space-between; align-items: center; background: #e0e0e0; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9rem">
                    <div><b>Comanda #<?php echo $comanda['id']; ?></b></div>
                    <div><i class="text-muted"><?php echo $nombreMesero; ?></i></div>
                </div>

                <!-- Información del cliente/mesa -->
                <div style="margin-bottom: 10px; padding: 5px 10px; background: #fff; border-radius: 5px; font-size: 0.85rem; box-shadow: rgba(0, 0, 0, 0.02) 0px 1px 3px 0px, rgba(27, 31, 35, 0.15) 0px 0px 0px 1px;">
                    <?php if ($comanda['tipo'] === 'mesa'): ?>
                        <span>Mesa</span> <?php echo $comanda['mesa']; ?>
                    <?php else: ?>
                        <span>A domicilio: </span> <?php echo $comanda['cliente']; ?>
                    <?php endif; ?>
                </div>

                <!-- Items de la comanda -->
                <?php foreach ($batchesData as $batch): ?>
                    <div class="d-flex align-items-center justify-content-between" style="font-size: 0.8rem; margin-bottom: 10px;"><span><?php echo $batch['seq'] == 1 ? 'Creacion de comanda' : 'Agregado despues'; ?></span><i class="text-muted"><?php echo date('H:i:s', strtotime($batch['created_at'])); ?></i></div>
                    <?php foreach ($batch['items'] as $item): ?>
                        <div style="margin-bottom: 10px; padding: 5px 10px; background: #fff; border-left: 4px solid #ff9800; box-shadow: rgba(0, 0, 0, 0.02) 0px 1px 3px 0px, rgba(27, 31, 35, 0.15) 0px 0px 0px 1px;">
                            <div style="display: flex; justify-content: space-between;">
                                <div>
                                    <b class="text-uppercase" style="font-size: 0.8rem;"><?php echo $item['nombre']; ?></b>
                                </div>
                            </div>

                            <!-- Componentes (modificaciones) -->
                            <?php if (!empty($item['componentes'])): ?>
                                <div style="padding-left: 20px; border-left: 2px solid #ddd;">
                                    <?php foreach ($item['componentes'] as $grupo): ?>
                                        <?php if (!empty($grupo['items'])): ?>
                                            <div style="margin-bottom: 5px; font-size: 0.8rem;">
                                                <span style="color: #666; font-size: 0.9em;">
                                                    <div><?php echo $grupo['nombre']; ?></div> -
                                                </span>
                                                <?php
                                                $nombresItems = array_map(function ($i) {
                                                    return $i['nombre'];
                                                }, $grupo['items']);
                                                echo implode('<br>- ', $nombresItems);
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Extras -->
                            <?php if (!empty($item['extras'])): ?>
                                <div style="font-size: 0.8rem; color: #115ebb;">
                                    <?php foreach ($item['extras'] as $extra): ?>
                                        <div style="padding-left: 20px;">
                                            + <?php echo $extra['nombre']; ?> x<?php echo $extra['qty']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($item['nota'])): ?>
                                <div
                                    style="color: #; font-size: 0.8rem; margin-top: 5px; background: rgb(255, 193, 7, 0.3); padding: 4px 10px;">
                                    <em>
                                        <?php echo $item['nota']; ?>
                                    </em>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            <?php
        }
    }
} else {
    echo '<div class="text-center" style="padding: 20px; background: #d9d9d9; border-radius: 5px;">Sin Resultados</div>';
}
?>