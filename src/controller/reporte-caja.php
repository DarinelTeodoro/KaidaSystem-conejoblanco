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

    $movimientos = $cnx->prepare("
        SELECT *
        FROM gastos_extras
        WHERE fecha > :init AND fecha < :finish
        ORDER BY fecha ASC
    ");
    $movimientos->execute([':init' => $fecha_inicial, ':finish' => $fecha_final]);
    $data_mov = $movimientos->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todos los pagos de la fecha especificada
    $stmt = $cnx->prepare("
        SELECT c.*, u.name, v.total_comanda, v.id_pago
        FROM ventas v
        LEFT JOIN comandas c ON v.id_comanda = c.id
        LEFT JOIN usuarios u ON c.user_id = u.username
        WHERE v.fecha > :init AND v.fecha < :finish AND tipo_pago = 'efectivo'
        GROUP BY id_comanda
        ORDER BY c.id ASC
    ");
    $stmt->execute([':init' => $fecha_inicial, ':finish' => $fecha_final]);
    $detalles_comanda = $stmt->fetchAll(PDO::FETCH_ASSOC);

    function movimientos($cnx, $fecha_init, $fecha_cut)
    {
        // Obtener todos los pagos de la fecha especificada
        $stmt = $cnx->prepare("SELECT * FROM view_movimientos WHERE fecha > :fecha_init AND fecha < :fecha_cut ORDER BY fecha ASC");
        $stmt->execute([':fecha_init' => $fecha_init, ':fecha_cut' => $fecha_cut]);
        $count = $stmt->rowCount();

        if ($count > 0) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return false;
        }
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

                        <table class="table table-sm table-modern table-hover align-middle table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Concepto</th>
                                    <th>Cantidad</th>
                                    <th>Usuario</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody style="font-size: 0.85rem;">
                                <?php
                                if (count($data_mov) > 0) {
                                    foreach ($data_mov as $mov) {
                                        echo '
                                        <tr>
                                            <td>' . $mov['concepto'] . '</td>
                                            <td>' . ($mov['tipo'] == 'gasto' ? '<span class="text-danger fw-bold">- $'.number_format($mov['cantidad'], 2).'</span>' : '<span class="text-success fw-bold">+ $'.number_format($mov['cantidad'], 2).'</span>') . '</td>
                                            <td>' . $mov['usuario'] . '</td>
                                            <td>' . $mov['fecha'] . '</td>
                                        </tr>
                                        ';
                                    }
                                } else {
                                    echo '<tr><td colspan="4" class="text-center">No hay Gastos o Ingresos Extras</td></tr>';
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
                                            <td class="text-center fw-bold <?= $resultado <= 0 ? 'text-success' : 'text-danger' ?>">
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

                                if ($corte['tipo'] == 1) {
                                    ?>
                                    <div class="border border-dark rounded-bottom bg-warning bg-opacity-25 shadow mb-2">
                                        <div class="p-1 bg-dark text-center"><span class="text-warning fw-bold">Nuevo Monto
                                                Inicial</span></div>
                                        <div class="d-flex align-items-center justify-content-between p-2">
                                            <div><span class="fw-bold"><?= $corte['fecha'] ?></span></div>
                                            <div><span class="fw-bold"><?= $corte['name'] ?></span></div>
                                        </div>
                                        <div class="p-1 text-center border-top border-dark"><span
                                                class="fs-5 text-success fw-bold">$<?= number_format($corte['inicial'], 2) ?></span>
                                        </div>
                                    </div>
                                    <?php
                                } else {
                                    ?>
                                    <div class="mt-1 mb-3">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr;">
                                            <div class="bg-dark p-1 text-center text-light"><?= $corte['fecha'] ?></div>
                                            <div class="bg-dark p-1 text-center text-light"><?= $corte['name'] ?></div>
                                        </div>

                                        <div class="table-responsive">
                                            <table
                                                class="table table-sm table-bordered border-primary border-opacity-50 table-modern align-middle mb-0">
                                                <tr>
                                                    <th class="text-center">Inicial</th>
                                                    <td class="text-center">$<?= number_format($corte['inicial'], 2) ?></td>
                                                </tr>
                                            </table>
                                        </div>

                                        <div class="m-3 border border-dark opacity-border-50 table-responsive">
                                            <table class="table table-sm table-modern table-hover align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Folio/Concepto</th>
                                                        <th class="text-center">Fecha & Hora</th>
                                                        <th class="text-center">Movimiento</th>
                                                    </tr>
                                                </thead>
                                                <tbody style="font-size: 0.85rem;">
                                                    <?php
                                                    if (!$movimientos) {
                                                        echo '<tr>
                                                                <td colspan="5" class="text-center">No hay movimientos</td>
                                                            </td>';
                                                    } else if ($movimientos) {
                                                        foreach ($movimientos as $movimiento) {
                                                            ?>
                                                            <tr>
                                                                <td><?= $movimiento['motivo'] ?></td>
                                                                <td class="text-center"><?= $movimiento['fecha'] ?></td>
                                                                <td class="text-center fw-bold <?= $movimiento['tipo'] == 'gasto' ? 'text-danger' : 'text-success' ?>">
                                                                    <?= $movimiento['tipo'] == 'gasto' ? '-' : '+' ?>
                                                                    $<?= number_format($movimiento['monto'], 2) ?>
                                                                </td>
                                                            </tr>
                                                            <?php
                                                        }
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="table-responsive">
                                            <table
                                                class="table table-sm table-bordered border-primary border-opacity-50 table-modern align-middle mb-0">
                                                <tr>
                                                    <th class="text-center">Corte</th>
                                                    <td class="text-center">$<?= number_format($corte['corte'], 2) ?></td>
                                                    <th class="text-center">Real</th>
                                                    <td class="text-center">$<?= number_format($corte['cantidad_real'], 2) ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="text-center" colspan="2"><?= $resultado <= 0 ? 'Restante' : 'Faltante' ?></th>
                                                    <td class="text-center" colspan="2">
                                                        <span class="fw-bold <?= $resultado <= 0 ? 'text-success' : 'text-danger' ?>">$<?= number_format($residuo, 2) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                    <?php
                                }
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