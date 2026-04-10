<?php
include('../../model/querys.php');
$countmesas = n_mesas();

$data_caja = data_servicio();
?>
<!--Contenedor Principal Inicio-->
<div class="container_main_mesas">
    <div class="page-head d-flex justify-content-between align-items-md-center mb-2 gap-2">
        <div>
            <h4 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Reporte de Caja</h4>
            <small class="text-muted">Informacion sobre movimientos y cortes de la caja.</small>
        </div>
    </div>
    <div class="line"></div>

    <div class="container-fluid">
        <?php
        if ($data_caja['caja'] == 0) {
            ?>
            <div class="mt-3 text-center bg-warning rounded p-1">
                <span class="text-muted">Por unica ocasión le solicitamos configurar la cantidad inicial de la caja.</span>
            </div>

            <form action="" method="post" class="m-0 mt-2" id="form_config_caja">
                <div class="row">
                    <div class="col-12 col-md-6 col-xl-4 d-grid p-2 ps-1 pe-1">
                        <label for="amount_caja">Cantidad Inicial</label>
                        <input type="number" name="amount_caja" id="amount_caja" step="0.01" required>
                    </div>
                    <div class="col-12 col-md-6 col-xl-4 d-flex align-items-end p-2 ps-1 pe-1">
                        <button type="submit" class="btn-edit pt-1 pb-1"><i class="bi bi-save me-1"></i>Guardar</button>
                    </div>
                </div>
            </form>
            <?php
        } else {
            ?>
            <form method="post" action="" class="container-fluid">
                <div class="d-flex justify-content-end align-items-center">
                    <div><button type="button" class="btn btn-success" style="height: 38px; border-radius: 0px;"><i
                                class="bi bi-arrow-left"></i></button></div>
                    <div><input type="date" name="dia" id="dia"
                            style="border-radius: 0px; border-color: #198754; border-right: none; border-left: none;"></div>
                    <div><button type="button" class="btn btn-success" style="height: 38px; border-radius: 0px;"><i
                                class="bi bi-arrow-right"></i></button></div>
                </div>
                <!--<div class="d-flex align-items-center justify-content-end mt-2"><button type="submit" class="btn btn-primary" style="width: 100px;">Ir</button></div>-->
            </form>

            <div class="container-data-caja" id="container-data-caja"></div>
            <?php
        }
        ?>
    </div>
</div>

<script src="reporte-caja.js"></script>
<script>
    $('#form_config_caja').submit(function (event) {
        event.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: '../../controller/caja-monto-inicial.php',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_main_home').load('reporte-caja.php');
                } else {
                    show_alert(response.status, response.message);
                }
            }
        });
    });
</script>