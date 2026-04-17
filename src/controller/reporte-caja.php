<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include('../model/conexion.php');

// Si hay error, devolvemos JSON
if (!isset($_POST['dia']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['dia'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Fecha no válida']);
    exit;
}

$dia = $_POST['dia'];

$fecha_inicial = date('Y-m-d 06:00:00', strtotime($dia));
$fecha_final = date('Y-m-d 06:00:00', strtotime($dia . ' +1 day'));
$cnx = new Conexion();

try {
    $cortes_caja = $cnx->prepare("
        SELECT c.*, u.name
        FROM caja c
        LEFT JOIN usuarios u ON c.usuario = u.username
        WHERE c.fecha > :init AND c.fecha < :finish
        ORDER BY c.fecha ASC
    ");
    $cortes_caja->execute([':init' => $fecha_inicial, ':finish' => $fecha_final]);
    $data_cortes = $cortes_caja->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todos los pagos de la fecha especificada
    $stmt = $cnx->prepare("
        SELECT c.*, u.name, p.total_comanda, p.id_pago
        FROM purchases p
        LEFT JOIN comandas c ON p.comanda_id = c.id
        LEFT JOIN usuarios u ON c.user_id = u.username
        WHERE p.fecha_pago > :init AND p.fecha_pago < :finish
        ORDER BY c.id ASC
    ");
    $stmt->execute([':init' => $fecha_inicial, ':finish' => $fecha_final]);
    $detalles_comanda = $stmt->fetchAll(PDO::FETCH_ASSOC);

    function movimientos($cnx, $fecha_init, $fecha_cut)
    {
        // Obtener todos los pagos de la fecha especificada
        $stmt = $cnx->prepare("
        SELECT * FROM ventas WHERE tipo_pago = 'efectivo' AND fecha > :fecha_init AND fecha < :fecha_cut
    ");
        $stmt->execute([':fecha_init' => $fecha_init, ':fecha_cut' => $fecha_cut]);
        $count = $stmt->rowCount();

        if ($count > 0) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }

    function gastos_extras($cnx, $fechaInit, $fechaFinish)
    {
        $query = $cnx->prepare("SELECT * FROM gastos_extras WHERE fecha > :fechaInit AND fecha < :fechaFinish");
        $query->bindParam(':fechaInit', $fechaInit);
        $query->bindParam(':fechaFinish', $fechaFinish);
        $query->execute();

        $data = $query->fetchAll(PDO::FETCH_ASSOC);

        return !empty($data) ? $data : false;
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
            class="fw-bold text-muted"><?= date('d-m-Y', strtotime($dia)) ?></span>
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
                                    SELECT * FROM ventas WHERE id_pago = :idpago AND tipo_pago = 'efectivo'
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
                                                }
                                                echo '<div><span>' . $n . ' - </span><span class="text-capitalize"><b class="text-success">' . $show['tipo_pago'] . '</b> <span class="text-muted">($' . number_format($cantidad, 2) . ')' . ($show['calc_propina'] > 0 ? ' + ($' . number_format($show['calc_propina'], 2) . ') = ($' . number_format(($cantidad + $show['calc_propina']), 2) . ')' : '') . '</span></span><span class="ms-2">' . ($show['tipo_pago'] == 'efectivo' ? '<b class="text-dark">Recibido:</b><span class="text-muted"> $' . number_format($show['recibido'], 2) . '</span></span><span class="ms-2"><b class="text-dark">Cambio:</b><span class="text-muted"> $' . number_format($show['cambio'], 2) : '') . '</span></span></div>';
                                                $n++;
                                            }
                                        }

                                        echo '</td>
                                </tr>
                                ';
                                    }
                                } else {
                                    echo '<tr><td colspan="3" class="text-center">No hay Comandas Pagadas</td></tr>';
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
                                            <td class="text-center"><?= $corte['name'] ?></td>
                                            <td class="text-center">$<?= number_format($corte['inicial'], 2) ?></td>
                                            <td class="text-center">$<?= number_format($corte['corte'], 2) ?></td>
                                            <td class="text-center">$<?= number_format($corte['cantidad_real'], 2) ?></td>
                                            <td class="text-center <?= $resultado <= 0 ? 'text-success' : 'text-danger' ?>">
                                                $<?= number_format($residuo, 2) ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center">No cortes realizados</td></tr>';
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
                <div class="bg-light shadow border border-1 border-dark"
                    style="box-shadow: rgba(0, 0, 0, 0.02) 0px 1px 3px 0px, rgba(27, 31, 35, 0.15) 0px 0px 0px 1px;">
                    <div class="p-2" style="border-bottom: 1px solid rgb(0, 0, 0, 0.4);">
                        <span class="text-muted fw-bold" style="font-size: 0.9rem;">Desglose de Cortes de Caja</span>
                    </div>
                    <div class="p-2 pb-0">
                        <?php
                        if ($data_cortes) {
                            foreach ($data_cortes as $corte) {
                                $resultado = $corte['cantidad_real'] - $corte['corte'];
                                $residuo = $resultado <= 0 ? $resultado * -1 : $resultado;

                                $movimientos = movimientos($cnx, $corte['fecha_inicial'], $corte['fecha']);
                                $gastos = gastos_extras($cnx, $corte['fecha_inicial'], $corte['fecha']);
                                ?>
                                <div class="mt-1 mb-3 border border-dark">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr;">
                                        <div class="bg-dark p-1 text-center text-light"><?= $corte['fecha'] ?></div>
                                        <div class="bg-dark p-1 text-center text-light"><?= $corte['name'] ?></div>
                                    </div>
                                    <div class="text-center p-1">
                                        <div>Monto Inicial</div>
                                        <div>$<?= number_format($corte['inicial'], 2) ?></div>
                                    </div>
                                    <div class="p-1 text-center"
                                        style="background: #fef9fd; border-top: 1px solid #000000; border-bottom: 1px solid #000000;">
                                        Movimientos
                                    </div>
                                    <div class="p-1">
                                        <table class="table table-sm table-modern table-hover align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">Folio</th>
                                                    <th class="text-center">Fecha</th>
                                                    <th class="text-center">Recibido (+)</th>
                                                    <th class="text-center">Cambio (-)</th>
                                                    <th class="text-center">Ingreso/Concepto</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if (!$movimientos & !$gastos) {
                                                    echo '<tr>
                                                        <td colspan="5" class="text-center">No hay movimientos</td>
                                                    </td>';
                                                }
                                                if ($movimientos) {
                                                    foreach ($movimientos as $movimiento) {
                                                        ?>
                                                        <tr>
                                                            <td class="text-center"><?= $movimiento['id_comanda'] ?></td>
                                                            <td class="text-center"><?= $movimiento['fecha'] ?></td>
                                                            <td class="text-center">$<?= number_format($movimiento['recibido'], 2) ?></td>
                                                            <td class="text-center">$<?= number_format($movimiento['cambio'], 2) ?></td>
                                                            <td class="text-center">
                                                                $<?= number_format(($movimiento['recibido'] - $movimiento['cambio']), 2) ?>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                    }
                                                }

                                                if ($gastos) {
                                                    foreach ($gastos as $gasto) {
                                                        ?>
                                                        <tr>
                                                            <td class="text-center">Gasto</td>
                                                            <td class="text-center"><?= $gasto['fecha'] ?></td>
                                                            <td class="text-center">$<?= number_format(0, 2) ?></td>
                                                            <td class="text-center">$<?= number_format($gasto['cantidad'], 2) ?></td>
                                                            <td class="text-center">
                                                                <?= $gasto['concepto'] ?>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; background: #ecf5fd;">
                                        <div class="text-center p-1">
                                            <div>Resultado Corte</div>
                                            <div>$<?= number_format($corte['corte'], 2) ?></div>
                                        </div>
                                        <div class="text-center p-1">
                                            <div>Cantidad Real</div>
                                            <div>$<?= number_format($corte['cantidad_real'], 2) ?></div>
                                        </div>
                                    </div>
                                    <div class="p-1">
                                        <div class="text-center">Restante / Faltante</div>
                                        <div class="text-center"><span
                                                class="<?= $resultado <= 0 ? 'text-success' : 'text-danger' ?>">$<?= number_format($residuo, 2) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div class="text-center mb-2">No hay cortes realizados</div>';
                        }
                        ?>
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