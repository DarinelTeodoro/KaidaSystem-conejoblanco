<?php
include('../../model/querys.php');
$countmesas = n_mesas();
?>
<!--Contenedor Principal Inicio-->
<div class="container_main_mesas">
    <div class="page-head d-flex justify-content-between align-items-md-center mb-2 gap-2">
        <div>
            <h4 class="mb-0"><i class="bi bi-grid-3x3-gap me-2"></i>Configurar mesas</h4>
            <small class="text-muted">Escribe cuántas mesas tienes. El sistema usará el rango 1..N.</small>
        </div>
    </div>
    <div class="line"></div>

    <div class="container-fluid">
        <form action="" method="post" class="m-0" id="form_new_comanda">
            <div class="row">
                <div class="col-12 col-md-6 col-xl-4 d-grid p-2 ps-1 pe-1">
                    <label for="n_mesas">Numero Total de Mesas</label>
                    <input type="number" name="n_mesas" id="n_mesas" value="<?= $countmesas['n_mesas'] ?>">
                </div>
                <div class="col-12 col-md-6 col-xl-4 d-flex align-items-end p-2 ps-1 pe-1">
                    <button type="submit" class="btn-edit pt-1 pb-1"><i class="bi bi-save me-1"></i>Guardar</button>
                </div>
            </div>
        </form>
        <span class="text-muted">Actualmente configurado: <b>1</b> al <b><?= $countmesas['n_mesas'] ?></b></span>
    </div>
</div>

<script>
    $('#form_new_comanda').submit(function (event) {
        event.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: '../../controller/mesas_update.php',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_main_home').load('configurar-mesas.php');
                } else {
                    show_alert(response.status, response.message);
                }
            }
        });
    });
</script>