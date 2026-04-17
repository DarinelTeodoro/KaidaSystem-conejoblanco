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
        </tr>
    </thead>
    <tbody>
        <?php
        $gastos = gastos($_SESSION['data-useractive']);

        if ($gastos) {
            foreach ($gastos as $gasto) {
                ?>
                <tr>
                    <td>$<?= number_format($gasto['cantidad'], 2) ?></td>
                    <td><?= $gasto['fecha'] ?></td>
                    <td><?= $gasto['concepto'] ?></td>
                </tr>
                <?php
            }
        } else {
            echo '<tr><td colspan="3" class="text-center">No hay gastos extras registrados</td></tr>';
        }
        ?>
    </tbody>
</table>