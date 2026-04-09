<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include('../../../cdn.html');
include('../../model/querys.php');

$day = date('d');

if (empty($_SESSION['data-useractive'])) {
    header('Location: ../../../index.php');
} else {
    $datauser = consultar_usuario($_SESSION['data-useractive']);
    $data_caja = data_servicio();

    if ($datauser['rol'] == 'Administrador') {
        header('Location: ../administrador/home.php');
    } elseif ($datauser['rol'] == 'Mesero') {
        header('Location: ../mesero/home.php');
    } elseif ($datauser['rol'] == 'Barra') {
        header('Location: ../barra/home.php');
    } elseif ($datauser['rol'] == 'Cocina') {
        header('Location: ../cocina/home.php');
    }

    if ($day >= 01 && $day <= 05) {
        $recordatorio = data_servicio();

        if ($recordatorio['visto_caja'] == 0) {
            $recordar = 1;
        } else if ($recordatorio['visto_caja'] == 1) {
            $recordar = 0;
        }
    } else {
        $recordar = 0;
    }
}

if (isset($_POST['logout'])) {
    session_destroy();
    echo '<script>window.location.href = "../../../index.php";</script>';
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="../../../favicon.ico">
    <title>Caja - Inicio</title>
    <link href="../../../style.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">

    <style>
        #body-page {
            background: #eff0ff;
            padding: 100px 20px 20px;
        }
    </style>
</head>

<body id="body-page">
    <div class="system_navbar_general fixed-top">
        <a href="home.php">
            <img src="../../../img/shortlogo-nbg.png" class="img_logo_navbar">
        </a>
        <div class="d-flex align-items-center justify-content-between dropdown">
            <span class="text_logo"></span>
            <div class="navbar_box_user" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div style="height: 60px;" class="d-flex justify-content-center flex-column lh-1">
                    <span class="text-success fw-bold">Hola</span>
                    <span><?= $datauser['name'] ?></span>
                </div>
                <div style="width: 60; height: 60px;" class="d-flex align-items-center justify-content-center">
                    <div class="foto_perfil"
                        style="height: 50px; width: 50px; background: rgb(0, 0, 0, 0) url('../../files/img_users/<?= $datauser['photo'] ?>') center center / cover no-repeat;">
                    </div>
                </div>
            </div>

            <form method="post" action="" class="dropdown-menu p-0" style="width: 200px;">
                <a href="home.php" type="sumbit" class="btn_dropdown" name="logout"><i
                        class="bi bi-cash-coin me-2"></i>Comandas</a>
                <button type="sumbit" class="btn_dropdown" name="logout"><i
                        class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</button>
            </form>
        </div>
    </div>

    <!--Contenedor Principal Inicio-->
    <div class="container_main_home" id="container_main_home">
        <?php
        if ($data_caja['caja'] == 0) {
            ?>
            <div class="bg-warning rounded p-2 d-flex align-items-center justify-content-center" align="justify">
                <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 2rem;"></i> Aun no se ha configurado el
                monto inicial de caja. Solicita al administrador que ingrese el monto inicial desde el panel de
                administracion en la opcion "Mesas & Caja".
            </div>
            <?php
        } else {
            ?>
            <div>
                <label>Ingresa el monto que hay en la caja en las diferentes denominaciones.</label>
                <div class="line"></div>
            </div>
            <form method="post" action="" id="cut-checkout" class="container-fluid">
                <div class="row">
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="d-flex align-items-center justify-content-center p-2">
                            <label class="me-2" for="mil">$1,000</label>
                            <input type="number" name="mil" id="mil">
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="d-flex align-items-center justify-content-center p-2">
                            <label class="me-2" for="quinientos">$500</label>
                            <input type="number" name="quinientos" id="quinientos">
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="d-flex align-items-center justify-content-center p-2">
                            <label class="me-2" for="docientos">$200</label>
                            <input type="number" name="docientos" id="docientos">
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="d-flex align-items-center justify-content-center p-2">
                            <label class="me-2" for="cien">$100</label>
                            <input type="number" name="cien" id="cien">
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="d-flex align-items-center justify-content-center p-2">
                            <label class="me-2" for="cincuenta">$50</label>
                            <input type="number" name="cincuenta" id="cincuenta">
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="d-flex align-items-center justify-content-center p-2">
                            <label class="me-2" for="veinte">$20</label>
                            <input type="number" name="veinte" id="veinte">
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="d-flex align-items-center justify-content-center p-2">
                            <label class="me-2" for="pesos">$10/5/2/1</label>
                            <input type="number" name="pesos" id="pesos">
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="d-flex align-items-center justify-content-center p-2">
                            <label class="me-2" for="centavos">$0.50/0.20/0.10</label>
                            <input type="number" name="centavos" id="centavos" step="0.01">
                        </div>
                    </div>
                </div>

                <div class="mt-2" align="center">
                    <button class="btn_execute ps-3 pt-1 pb-1 pe-3">Realizar Corte</button>
                </div>
            </form>
            <?php
        }
        ?>
    </div>


    <!--Contenedor Screen Alert-->
    <div class="container_main_alert fixed-top" id="container_main_alert">
        <div class="container_alert">
            <div class="head_alert">
                <span id="text_title_alert">¡ Alerta !</span>
            </div>
            <div class="body_alert">
                <div class="mb-3"><span id="text_message_alert">Mensaje Alerta</span></div>
                <div id="container-btn-acept"><button type="button" class="btn_accept_alert" id="acept_alert"
                        onclick="hide_alert()">Aceptar</button></div>
            </div>
        </div>
    </div>

</body>

</html>

<script src="../../../script.js"></script>

<script>
$('#cut-checkout').submit(function (event) {
    event.preventDefault();

    function calcularTotal() {
        let mil = parseFloat($('#mil').val()) || 0;
        let quinientos = parseFloat($('#quinientos').val()) || 0;
        let docientos = parseFloat($('#docientos').val()) || 0;
        let cien = parseFloat($('#cien').val()) || 0;
        let cincuenta = parseFloat($('#cincuenta').val()) || 0;
        let veinte = parseFloat($('#veinte').val()) || 0;
        let pesos = parseFloat($('#pesos').val()) || 0;
        let centavos = parseFloat($('#centavos').val()) || 0;
        
        return mil + quinientos + docientos + 
               cien + cincuenta + veinte + 
               pesos + centavos;
    }

    let total = calcularTotal();
    let totalFormateado = total.toLocaleString('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    // Usar SweetAlert2 para confirmación
    Swal.fire({
        title: 'Confirmar Corte de Caja',
        html: `
            <div style="text-align: left;">
                <span>Total a enviar:</span>
                <p style="font-size: 24px; color: #2e7d32; font-weight: bold;">$${totalFormateado}</p>
                <hr>
                <div align="center">¿Estás seguro que la información es correcta?</div>
                <div style="font-size: 14px; color: #666;" align="center">Esta acción registrará el corte de caja.</div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Procesando...',
                text: 'Enviando información del corte',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            var formData = new FormData(this);
            formData.append('total_general', total);

            $.ajax({
                type: 'POST',
                url: '../../controller/caja-arqueo-ingresos.php',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'EXITO') {
                        Swal.fire({
                            title: '¡Éxito!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            document.getElementById('cut-checkout').reset();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message,
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Ocurrió un error al procesar la solicitud',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
            });
        }
    });
});
</script>