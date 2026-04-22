<?php
date_default_timezone_set('America/Mexico_City');
include('../../model/querys.php');

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
        <div class="container-fluid p-0">
            <div class="mb-2"><button type="button" class="btn-edit pt-1 pb-1 border border-dark"
                    id="open_modal_montoinicial">Cambiar Monto Inicial</button></div>
            <div class="d-flex justify-content-end align-items-center">
                <div><button type="button" class="btn btn-success" style="height: 38px; border-radius: 0px;"><i
                            class="bi bi-arrow-left"></i></button></div>
                <div><input type="date" name="dia" id="dia"
                        style="border-radius: 0px; border-color: #198754; border-right: none; border-left: none;"></div>
                <div><button type="button" class="btn btn-success" style="height: 38px; border-radius: 0px;"><i
                            class="bi bi-arrow-right"></i></button></div>
            </div>
            <!--<div class="d-flex align-items-center justify-content-end mt-2"><button type="submit" class="btn btn-primary" style="width: 100px;">Ir</button></div>-->
        </div>

        <div class="container-data-caja" id="container-data-caja"></div>
    </div>

    <div class="fade_modal_system fixed-top" id="modal_monto_inicial">
        <div class="container_form_modal">
            <div class="head_form_modal">
                <span>Cambiar Monto Inicial</span>
                <i class="bi bi-x-lg icon_close_modal" id="close_modal_montoinicial"></i>
            </div>
            <form action="" method="post" class="body_form_modal" id="form_config_caja">
                <div class="d-grid pt-3 ps-4 pe-4 pb-1">
                    <label for="amount_caja">Cantidad Inicial</label>
                    <input type="number" name="amount_caja" id="amount_caja" step="0.01" required>
                </div>
                <div class="pt-3 ps-4 pe-4 pb-1" align="center">
                    <button type="submit" class="btn-edit pt-1 pb-1"><i class="bi bi-save me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="reporte-caja.js?v=2"></script>
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