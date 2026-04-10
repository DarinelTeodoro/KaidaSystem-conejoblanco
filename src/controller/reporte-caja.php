<?php
session_start();
include('../model/conexion.php');

// Si hay error, devolvemos JSON
if (!isset($_POST['dia']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['dia'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Fecha no válida']);
    exit;
}

$fecha = $_POST['dia'];
$cnx = new Conexion();

try {
    $cortes_caja = $cnx->prepare("
        SELECT *
        FROM caja
        WHERE DATE(fecha) = :fecha
        ORDER BY fecha ASC
    ");
    $cortes_caja->execute([':fecha' => $fecha]);
    $data_cortes = $cortes_caja->fetchAll(PDO::FETCH_ASSOC);


    $stmtTotal_ventas = $cnx->prepare("
    SELECT 
        SUM(calc_propina) as propinas, 
        SUM(total) as total,
        
        -- Total efectivo
        SUM(CASE 
            WHEN tipo_pago = 'efectivo' AND recibido < total AND n_cuenta = 0 
                THEN recibido - calc_propina
            WHEN tipo_pago = 'efectivo' 
                THEN total - calc_propina
            ELSE 0 
        END) as total_efectivo,
        
        -- Total tarjeta
        SUM(CASE 
            WHEN tipo_pago = 'tarjeta' AND total = 0 AND n_cuenta = 0 
                THEN recibido - calc_propina
            WHEN tipo_pago = 'tarjeta' AND (total > 0 OR n_cuenta > 0)
                THEN total - calc_propina
            ELSE 0 
        END) as total_tarjeta,
        
        -- Propinas por método
        SUM(CASE 
            WHEN tipo_pago = 'efectivo' THEN calc_propina
            ELSE 0 
        END) as propina_efectivo,
        
        SUM(CASE 
            WHEN tipo_pago = 'tarjeta' THEN calc_propina
            ELSE 0 
        END) as propina_tarjeta,
        
        -- Contadores
        COUNT(CASE WHEN tipo_pago = 'efectivo' THEN 1 END) as count_efectivo,
        COUNT(CASE WHEN tipo_pago = 'tarjeta' THEN 1 END) as count_tarjeta,
        COUNT(CASE WHEN tipo_pago NOT IN ('efectivo', 'tarjeta') THEN 1 END) as count_otros

    FROM ventas
    WHERE DATE(fecha) = :fecha
");
    $stmtTotal_ventas->execute([':fecha' => $fecha]);
    $totales_ventas = $stmtTotal_ventas->fetch(PDO::FETCH_ASSOC);

    // Obtener todos los pagos de la fecha especificada
    $stmt = $cnx->prepare("
        SELECT c.*, u.name, p.total_comanda, p.id_pago
        FROM purchases p
        LEFT JOIN comandas c ON p.comanda_id = c.id
        LEFT JOIN usuarios u ON c.user_id = u.username
        WHERE DATE(p.fecha_pago) = :fecha
        ORDER BY c.id ASC
    ");
    $stmt->execute([':fecha' => $fecha]);
    $detalles_comanda = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // Obtener todos los pagos de la fecha especificada
    $mas_comandas = $cnx->prepare("
        SELECT c.*, u.name
        FROM comandas c
        LEFT JOIN usuarios u ON c.user_id = u.username
        WHERE DATE(c.created_at) = :fecha
        AND estado != 'finalizado'
        ORDER BY c.id ASC
    ");
    $mas_comandas->execute([':fecha' => $fecha]);
    $detalles_plus = $mas_comandas->fetchAll(PDO::FETCH_ASSOC);


    function calcularTotalComanda($comanda_id)
    {
        $cnx = new Conexion();
        $totalComanda = 0;

        // Obtener todos los batches de la comanda
        $stmtB = $cnx->prepare("SELECT * FROM comanda_batches WHERE comanda_id = :id ORDER BY seq ASC");
        $stmtB->execute([':id' => $comanda_id]);
        $batches = $stmtB->fetchAll(PDO::FETCH_ASSOC);

        foreach ($batches as $b) {
            $batchId = (int) $b['id'];

            // Obtener items del batch
            $stmtI = $cnx->prepare("SELECT * FROM comanda_items WHERE comanda_id = :cid AND batch_id = :bid ORDER BY id ASC");
            $stmtI->execute([':cid' => $comanda_id, ':bid' => $batchId]);
            $items = $stmtI->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $it) {
                $itemId = (int) $it['id'];

                // Calcular subtotal base del item
                $subtotal = ((float) $it['precio']) * ((int) $it['qty']);

                // Obtener componentes del item
                $stmtComp = $cnx->prepare("SELECT * FROM comanda_item_componentes WHERE item_id = :iid ORDER BY id ASC");
                $stmtComp->execute([':iid' => $itemId]);
                $comps = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

                // Sumar el precio de los extras al subtotal
                foreach ($comps as $cp) {
                    if ($cp['kind'] === 'extra') {
                        $subtotal += ((float) $cp['precio']) * ((int) $cp['qty']);
                    }
                }

                $totalComanda += $subtotal;
            }
        }

        return $totalComanda;
    }
    ?>

    <style>
        .card-data-ventas {
            height: 100px;
            display: grid;
            grid-template-rows: 1fr 1fr;

            border: 1px solid #475569;
        }

        .table-modern thead th {
            color: #475569;
            font-weight: 600;
            border-bottom: 1px solid #e5e7eb;
            font-size: .85rem;
            white-space: nowrap;
        }

        .table-modern tbody tr td {
            white-space: nowrap;
        }
    </style>

    <div>
        <span class="text-muted">Dia Seleccionado: </span><span
            class="fw-bold text-muted"><?= date('d-m-Y', strtotime($fecha)) ?></span>
    </div>

    <div>
        <div class="row">
            <div class="col-12 col-md-6 col-xl-3 p-2">
                <div class="bg-light rounded shadow p-3 card-data-ventas">
                    <span class="text-muted" style="font-size: 0.8rem;">COMANDAS COBRADAS</span>
                    <div class="d-flex align-items-center"><span
                            class="fw-bold fs-4 text-primary"></span></div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3 p-2">
                <div class="bg-light rounded shadow p-3 card-data-ventas">
                    <span class="text-muted" style="font-size: 0.8rem;">INGRESO NETO (SIN PROPINAS)</span>
                    <div class="d-flex align-items-center">
                        <span class="fw-bold fs-4 text-info">$
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3 p-2">
                <div class="bg-light rounded shadow p-3 card-data-ventas">
                    <span class="text-muted" style="font-size: 0.8rem;">PROPINAS</span>
                    <div class="d-flex align-items-center">
                        <span class="fw-bold fs-4 text-warning">$
                            <?= number_format($totales_ventas['propinas'], 2) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3 p-2">
                <div class="bg-light rounded shadow p-3 card-data-ventas">
                    <span class="text-muted" style="font-size: 0.8rem;">INGRESOS TOTALES</span>
                    <div class="d-flex align-items-center">
                        <span class="fw-bold fs-4 text-success">$
                            <?= number_format($totales_ventas['total'], 2) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div>

        <div class="row">
            <div class="col-12 p-2">
                <div class="bg-light shadow border border-1 border-dark"
                    style="box-shadow: rgba(0, 0, 0, 0.02) 0px 1px 3px 0px, rgba(27, 31, 35, 0.15) 0px 0px 0px 1px;">
                    <div class="p-2" style="border-bottom: 1px solid rgb(0, 0, 0, 0.4);">
                        <span class="text-muted fw-bold" style="font-size: 0.9rem;">Movimientos de Caja</span>
                    </div>
                    <div class="table-responsive p-2 pb-0">
                        <table class="table table-sm table-modern table-hover align-middle table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Folio</th>
                                    <th>Mesero</th>
                                    <th>Desglose de Pago <span class="text-muted">(Cuenta + Propina = Total)</span></th>
                                </tr>
                            </thead>
                            <tbody style="font-size: 0.85rem;">
                                <?php
                                if (count($detalles_comanda) > 0) {
                                    foreach ($detalles_comanda as $detalle) {
                                        $tablepagos = $cnx->prepare("
                                    SELECT SUM(calc_propina) AS propinas FROM ventas WHERE id_pago = :idpago
                                ");
                                        $tablepagos->execute([':idpago' => $detalle['id_pago']]);
                                        $resultpagos = $tablepagos->fetch(PDO::FETCH_ASSOC);

                                        $show = $cnx->prepare("
                                    SELECT * FROM ventas WHERE id_pago = :idpago
                                ");
                                        $show->execute([':idpago' => $detalle['id_pago']]);
                                        $resultshow = $show->fetchAll(PDO::FETCH_ASSOC);

                                        echo '
                                <tr>
                                    <td>' . $detalle['id'] . '</td>
                                    <td>' . $detalle['name'] . '</td>
                                    <td>';

                                        $n = 1;
                                        if (count($resultshow) > 0) {
                                            foreach ($resultshow as $show) {
                                                if ($show['tipo_pago'] == 'efectivo') {
                                                    if ($show['recibido'] < $show['total'] && $show['n_cuenta'] == 0) {
                                                        $cantidad = $show['recibido'] - $show['calc_propina'];
                                                    } else {
                                                        $cantidad = $show['total'] - $show['calc_propina'];
                                                    }
                                                } else if ($show['tipo_pago'] == 'tarjeta') {
                                                    if ($show['total'] == 0 && $show['n_cuenta'] == 0) {
                                                        $cantidad = $show['recibido'] - $show['calc_propina'];
                                                    } else if ($show['total'] > 0 && $show['n_cuenta'] == 0) {
                                                        $cantidad = $show['total'] - $show['calc_propina'];
                                                    } else if ($show['n_cuenta'] > 0) {
                                                        $cantidad = $show['total'] - $show['calc_propina'];
                                                    }
                                                }
                                                $color = $show['tipo_pago'] == 'efectivo' ? 'text-success' : 'text-danger';
                                                echo '<div><span>' . $n . ' - </span><span class="text-capitalize"><b class="' . $color . '">' . $show['tipo_pago'] . '</b> <span class="text-muted">($' . number_format($cantidad, 2) . ')' . ($show['calc_propina'] > 0 ? ' + ($' . number_format($show['calc_propina'], 2) . ') = ($' . number_format(($cantidad + $show['calc_propina']), 2) . ')' : '') . '</span></span><span class="ms-2">' . ($show['tipo_pago'] == 'efectivo' ? '<b class="text-dark">Recibido:</b><span class="text-muted"> $' . number_format($show['recibido'], 2) . '</span></span><span class="ms-2"><b class="text-dark">Cambio:</b><span class="text-muted"> $' . number_format($show['cambio'], 2) : '') . '</span></span></div>';
                                                $n++;
                                            }
                                        }

                                        echo '</td>
                                </tr>
                                ';
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center">No hay Comandas Pagadas</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-12 p-2">
                <div class="bg-light shadow border border-1 border-dark">
                    <div class="p-2" style="border-bottom: 1px solid rgb(0, 0, 0, 0.4);">
                        <span class="text-muted fw-bold" style="font-size: 0.9rem;">Cortes de Caja</span>
                    </div>
                    <div class="table-responsive p-2 pb-0">
                        <table class="table table-sm table-modern table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha & Hora</th>
                                    <th class="text-center">Usuario</th>
                                    <th class="text-center">Monto Inicial</th>
                                    <th class="text-center">Monto en Corte</th>
                                    <th class="text-center">Cantidad Real</th>
                                    <th class="text-center">Faltante/Restante</th>
                                </tr>
                            </thead>
                            <tbody style="font-size: 0.85rem;">
                                <?php 
                                if ($data_cortes) {
                                    foreach ($data_cortes as $corte) {
                                        $resultado = $corte['cantidad_real'] - $corte['corte'];
                                        $residuo = $resultado <= 0 ? $resultado * -1 : $resultado;
                                        ?>
                                        <tr>
                                            <td><?= $corte['fecha'] ?></td>
                                            <td class="text-center"><?= $corte['usuario'] ?></td>
                                            <td class="text-center"><?= number_format($corte['inicial'], 2) ?></td>
                                            <td class="text-center"><?= number_format($corte['corte'], 2) ?></td>
                                            <td class="text-center"><?= number_format($corte['cantidad_real'], 2) ?></td>
                                            <td class="text-center <?= $resultado <= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($residuo, 2) ?></td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <?php

} catch (Exception $e) {
    // SOLO EN CASO DE ERROR, DEVOLVEMOS JSON
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>