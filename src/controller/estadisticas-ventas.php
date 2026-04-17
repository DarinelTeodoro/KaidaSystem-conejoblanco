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
$fecha_inicial = date('Y-m-d 06:00:00', strtotime($fecha));
$fecha_final = date('Y-m-d 06:00:00', strtotime($fecha . ' +1 day'));
$cnx = new Conexion();

try {
    $stmtTotal_comandas = $cnx->prepare("
        SELECT SUM(total_comanda) as total, COUNT(id_pago) as n_pagos
        FROM purchases
        WHERE fecha_pago > :fechaInit AND fecha_pago < :fechaFinish
    ");
    $stmtTotal_comandas->execute([':fechaInit' => $fecha_inicial, ':fechaFinish' => $fecha_final]);
    $totales_comandas = $stmtTotal_comandas->fetch(PDO::FETCH_ASSOC);

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
    WHERE fecha > :fechaInit AND fecha < :fechaFinish
");
    $stmtTotal_ventas->execute([':fechaInit' => $fecha_inicial, ':fechaFinish' => $fecha_final]);
    $totales_ventas = $stmtTotal_ventas->fetch(PDO::FETCH_ASSOC);

    // Obtener todos los pagos de la fecha especificada
    $stmt = $cnx->prepare("
        SELECT c.*, u.name, p.total_comanda, p.id_pago
        FROM purchases p
        LEFT JOIN comandas c ON p.comanda_id = c.id
        LEFT JOIN usuarios u ON c.user_id = u.username
        WHERE p.fecha_pago > :fechaInit AND p.fecha_pago < :fechaFinish
        ORDER BY c.id ASC
    ");
    $stmt->execute([':fechaInit' => $fecha_inicial, ':fechaFinish' => $fecha_final]);
    $detalles_comanda = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // Obtener todos los pagos de la fecha especificada
    $mas_comandas = $cnx->prepare("
        SELECT c.*, u.name
        FROM comandas c
        LEFT JOIN usuarios u ON c.user_id = u.username
        WHERE c.created_at > :fechaInit
        AND c.created_at < :fechaFinish
        AND estado != 'finalizado'
        ORDER BY c.id ASC
    ");
    $mas_comandas->execute([':fechaInit' => $fecha_inicial, ':fechaFinish' => $fecha_final]);
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
                            class="fw-bold fs-4 text-primary"><?= $totales_comandas['n_pagos'] ?></span></div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3 p-2">
                <div class="bg-light rounded shadow p-3 card-data-ventas">
                    <span class="text-muted" style="font-size: 0.8rem;">INGRESO NETO (SIN PROPINAS)</span>
                    <div class="d-flex align-items-center">
                        <span class="fw-bold fs-4 text-info">$
                            <?= number_format($totales_comandas['total'], 2) ?>
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
            <div class="col-12 col-md-6 col-xl-6 p-2">
                <div class="bg-light shadow border border-1 border-dark">
                    <div class="p-2" style="border-bottom: 1px solid rgb(0, 0, 0, 0.4);">
                        <span class="text-muted fw-bold" style="font-size: 0.9rem;">Meseros: Ventas y Propinas</span>
                    </div>
                    <div class="table-responsive p-2 pb-0">
                        <table class="table table-sm table-modern table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Mesero</th>
                                    <th class="text-center">Comandas</th>
                                    <th class="text-center">Ingresos</th>
                                    <th class="text-center">Propinas</th>
                                </tr>
                            </thead>
                            <tbody style="font-size: 0.85rem;">
                                <?php
                                $meseros = $cnx->prepare("
                                    SELECT * FROM usuarios WHERE rol = 'Mesero'
                                ");
                                $meseros->execute();
                                $detalles_meseros = $meseros->fetchAll(PDO::FETCH_ASSOC);
                                $contador_meseros_con_ventas = 0;

                                if (count($detalles_meseros) > 0) {
                                    foreach ($detalles_meseros as $mesero) {
                                        $ventasmeseros = $cnx->prepare("
                                            SELECT SUM(v.total_comanda) AS ingresos, SUM(v.calc_propina) propinas, COUNT(v.id) AS comandas
                                            FROM ventas v
                                            LEFT JOIN comandas c ON v.id_comanda = c.id
                                            WHERE c.user_id = :user AND DATE(v.fecha) = :fecha
                                        ");
                                        $ventasmeseros->execute([':user' => $mesero['username'], ':fecha' => $fecha]);
                                        $meserosganancias = $ventasmeseros->fetch(PDO::FETCH_ASSOC);

                                        $cm = $cnx->prepare("SELECT COUNT(p.id_pago) AS atendidos FROM purchases p LEFT JOIN comandas c ON p.comanda_id = c.id WHERE c.user_id = :user AND DATE(p.fecha_pago) = :fecha");
                                        $cm->execute([':user' => $mesero['username'], ':fecha' => $fecha]);
                                        $arraycm = $cm->fetch(PDO::FETCH_ASSOC);

                                        if ($meserosganancias && $meserosganancias['comandas'] > 0) {
                                            $contador_meseros_con_ventas++;
                                            echo '<tr>
                                                <td>' . $mesero['name'] . '</td>
                                                <td class="text-center">' . $arraycm['atendidos'] . '</td>
                                                <td class="text-center">$' . number_format($meserosganancias['ingresos'], 2) . '</td>
                                                <td class="text-center">$' . number_format($meserosganancias['propinas'], 2) . '</td>
                                            </tr>';
                                        }
                                    }

                                    if ($contador_meseros_con_ventas == 0) {
                                        echo '<tr>
                                            <td colspan="4" class="text-center">No hay registros</td>
                                        </tr>';
                                    }
                                } else {
                                    echo '<tr>
                                            <td colspan="4" class="text-center">No hay registros</td>
                                        <tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-6 p-2">
                <div class="bg-light shadow border border-1 border-dark">
                    <div class="p-2" style="border-bottom: 1px solid rgb(0, 0, 0, 0.4);">
                        <span class="text-muted fw-bold" style="font-size: 0.9rem;">Metodos de Pago</span>
                    </div>
                    <div class="table-responsive p-2 pb-0">
                        <table class="table table-sm table-modern table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Metodo</th>
                                    <!--<th>Comandas</th>-->
                                    <th class="text-center">Pagos</th>
                                    <th class="text-center">Comandas</th>
                                    <th class="text-center">Propinas</th>
                                    <th class="text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody style="font-size: 0.85rem;">
                                <tr>
                                    <td>Efectivo</td>
                                    <td class="text-center"><?= $totales_ventas['count_efectivo'] ?></td>
                                    <td class="text-center">$<?= number_format($totales_ventas['total_efectivo'], 2) ?></td>
                                    <td class="text-center">$<?= number_format($totales_ventas['propina_efectivo'], 2) ?>
                                    </td>
                                    <td class="text-center">
                                        $<?= number_format(($totales_ventas['total_efectivo'] + $totales_ventas['propina_efectivo']), 2) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Tarjeta</td>
                                    <td class="text-center"><?= $totales_ventas['count_tarjeta'] ?></td>
                                    <td class="text-center">$<?= number_format($totales_ventas['total_tarjeta'], 2) ?></td>
                                    <td class="text-center">$<?= number_format($totales_ventas['propina_tarjeta'], 2) ?>
                                    </td>
                                    <td class="text-center">
                                        $<?= number_format(($totales_ventas['total_tarjeta'] + $totales_ventas['propina_tarjeta']), 2) ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


        <div class="bg-light shadow border border-1 border-dark"
            style="box-shadow: rgba(0, 0, 0, 0.02) 0px 1px 3px 0px, rgba(27, 31, 35, 0.15) 0px 0px 0px 1px;">
            <div class="p-2" style="border-bottom: 1px solid rgb(0, 0, 0, 0.4);">
                <span class="text-muted fw-bold" style="font-size: 0.9rem;">Detalles de Comandas Pagadas</span>
            </div>
            <div class="table-responsive p-2 pb-0">
                <table class="table table-sm table-modern table-hover align-middle table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Folio</th>
                            <th>Entrega a</th>
                            <th>Mesero</th>
                            <th class="text-center">Cuenta</th>
                            <th class="text-center">Propina</th>
                            <th class="text-center">Total</th>
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
                                    <td>' . ($detalle['tipo'] == 'mesa' ? 'Mesa ' . $detalle['mesa'] : $detalle['cliente']) . '</td>
                                    <td>' . $detalle['name'] . '</td>
                                    <td class="text-center">$' . number_format($detalle['total_comanda'], 2) . '</td>
                                    <td class="text-center">$' . number_format($resultpagos['propinas'], 2) . '</td>
                                    <td class="text-center">$' . number_format(($detalle['total_comanda'] + $resultpagos['propinas']), 2) . '</td>
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



        <div class="bg-light mt-3 shadow border border-1 border-dark"
            style="box-shadow: rgba(0, 0, 0, 0.02) 0px 1px 3px 0px, rgba(27, 31, 35, 0.15) 0px 0px 0px 1px;">
            <div class="p-2" style="border-bottom: 1px solid rgb(0, 0, 0, 0.4);">
                <span class="text-muted fw-bold" style="font-size: 0.9rem;">Comandas Pendientes / Canceladas</span>
            </div>
            <div class="table-responsive p-2 pb-0">
                <table class="table table-sm table-modern table-hover align-middle table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Folio</th>
                            <th>Entrega a</th>
                            <th>Mesero</th>
                            <th class="text-center">Cuenta Actual</th>
                            <th class="text-center">Estado</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 0.85rem;">
                        <?php
                        if (count($detalles_plus) > 0) {
                            foreach ($detalles_plus as $plus) {
                                $cuenta_actual = calcularTotalComanda($plus['id']);

                                echo '
                                <tr>
                                    <td>' . $plus['id'] . '</td>
                                    <td>' . ($plus['tipo'] == 'mesa' ? 'Mesa ' . $plus['mesa'] : $plus['cliente']) . '</td>
                                    <td>' . $plus['name'] . '</td>
                                    <td class="text-center">$' . number_format($cuenta_actual, 2) . '</td>
                                    <td class="text-center">' . ($plus['estado'] == 'pendiente' ? '<span style="color: #db8a00;">Pendiente</span>' : '<span class="text-danger">Cancelado</span>') . '</td>
                                    <td>' . $plus['motivo_cancelacion'] . '</td>
                                </tr>
                                ';
                            }
                        } else {
                            echo '<tr><td colspan="7" class="text-center">No hay mas Comandas</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>


        <div class="row">
            <div class="col-12 col-md-6 col-xl-6 p-2">
                <div class="bg-light shadow border border-1 border-dark"
                    style="box-shadow: rgba(0, 0, 0, 0.02) 0px 1px 3px 0px, rgba(27, 31, 35, 0.15) 0px 0px 0px 1px;">
                    <div class="p-2" style="border-bottom: 1px solid rgb(0, 0, 0, 0.4);">
                        <span class="text-muted fw-bold" style="font-size: 0.9rem;">Platillos Vendidos</span>
                    </div>
                    <div class="table-responsive p-2 pb-0">
                        <table class="table table-sm table-modern table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Platillo</th>
                                    <th class="text-center">Unidad</th>
                                    <th class="text-center">Ingresos</th>
                                </tr>
                            </thead>
                            <tbody style="font-size: 0.85rem;">
                                <?php
                                $data_items = $cnx->prepare("
                                    SELECT
                                        nombre,
                                        COUNT(*) as cantidad_registros,
                                        SUM(precio) as suma_subtotal
                                    FROM view_productos_vendidos
                                    WHERE fecha_pago > :dateInit
                                    AND fecha_pago < :dateFinish 
                                    AND tipo != 'extra'
                                    GROUP BY nombre, precio
                                    ORDER BY cantidad_registros DESC, nombre ASC
                                ");
                                $data_items->execute([':dateInit' => $fecha_inicial, ':dateFinish' => $fecha_final]);
                                $detalles_di = $data_items->fetchAll(PDO::FETCH_ASSOC);

                                if (count($detalles_di) > 0) {
                                    foreach ($detalles_di as $ddi) {
                                        echo '<tr>
                                            <td>' . $ddi['nombre'] . '</td>
                                            <td class="text-center">' . $ddi['cantidad_registros'] . '</td>
                                            <td class="text-center">$' . number_format($ddi['suma_subtotal'], 2) . '</td>
                                        </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center">Sin Registros</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-6 p-2">
                <div class="bg-light shadow border border-1 border-dark"
                    style="box-shadow: rgba(0, 0, 0, 0.02) 0px 1px 3px 0px, rgba(27, 31, 35, 0.15) 0px 0px 0px 1px;">
                    <div class="p-2" style="border-bottom: 1px solid rgb(0, 0, 0, 0.4);">
                        <span class="text-muted fw-bold" style="font-size: 0.9rem;">Extras Solicitados</span>
                    </div>
                    <div class="table-responsive p-2 pb-0">
                        <table class="table table-sm table-modern table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Extra</th>
                                    <th class="text-center">Unidades</th>
                                    <th class="text-center">Ingresos</th>
                                </tr>
                            </thead>
                            <tbody style="font-size: 0.85rem;">
                                <?php
                                $data_extras = $cnx->prepare("
                                    SELECT 
                                        nombre,
                                        SUM(qty) as cantidad_registros,
                                        precio as precio_extra
                                    FROM view_productos_vendidos
                                    WHERE fecha_pago > :dateInit
                                    AND fecha_pago < :dateFinish 
                                    AND tipo = 'extra'
                                    GROUP BY nombre, precio
                                    ORDER BY cantidad_registros DESC, nombre ASC
                                ");
                                $data_extras->execute([':dateInit' => $fecha_inicial, ':dateFinish' => $fecha_final]);
                                $detalles_ex = $data_extras->fetchAll(PDO::FETCH_ASSOC);

                                if (count($detalles_ex) > 0) {
                                    foreach ($detalles_ex as $dex) {
                                        echo '<tr>
                                            <td>' . $dex['nombre'] . '</td>
                                            <td class="text-center">' . $dex['cantidad_registros'] . '</td>
                                            <td class="text-center">$' . number_format(($dex['precio_extra'] * $dex['cantidad_registros']), 2) . '</td>
                                        </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center">Sin Registros</td></tr>';
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