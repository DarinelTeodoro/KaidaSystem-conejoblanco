<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include('../model/querys.php');
?>
<table class="table table-bordered border-primary">
    <thead class="bg-dark">
        <tr>
            <th>Cantidad</th>
            <th>Fecha</th>
            <th>Concepto</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $gastos = gastos($_SESSION['data-useractive']);

        if ($gastos) {
            foreach ($gastos as $gasto) {
                $movimiento = $gasto['tipo'] == 'gasto' ? ($gasto['cantidad'] * -1) : $gasto['cantidad'];
                ?>
                <tr>
                    <td class="fw-bold <?= $gasto['tipo'] == 'gasto' ? 'text-danger' : 'text-success' ?>">$<?= number_format($movimiento, 2) ?></td>
                    <td><?= $gasto['fecha'] ?></td>
                    <td><?= $gasto['concepto'] ?></td>
                    <td class="text-center"><?= $gasto['confirmado'] == 0 ? '<button class="btn btn-danger" onclick="eliminar_movimiento('.$gasto['id'].')"><i class="bi bi-trash3-fill"></i></button>' : '' ?></td>
                </tr>
                <?php
            }
        } else {
            echo '<tr><td colspan="4" class="text-center">No hay movimientos extras registrados</td></tr>';
        }
        ?>
    </tbody>
</table>