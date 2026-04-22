<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include('../../../cdn.html');
include('../../model/querys.php');

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
    <link href="../../../style.css?v=2" rel="stylesheet">
    <link href="style.css?v=2" rel="stylesheet">

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
                <a href="home.php" class="btn_dropdown" name="logout"><i class="bi bi-cash-coin me-2"></i>Comandas</a>
                <button type="submit" class="btn_dropdown" name="logout"><i
                        class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</button>
            </form>
        </div>
    </div>

    <!--Contenedor Principal Inicio-->
    <div class="container_main_home" id="container_main_home">
        <div class="d-flex align-items-center justify-content-between">
            <button type="button" class="btn-add" id="open_modal_cortecaja">Corte de Caja</button>
            <button type="button" class="btn-add" id="open_modal_gastos">Ingresos / Gastos</button>
        </div>

        <div>
            <div class="mt-2 mb-2">
                <strong>Ingresos & Gastos Extras</strong>
            </div>

            <div class="table-responsive" id="tabla-gastos-extras">

            </div>
        </div>
    </div>

    <div class="fade_modal_system fixed-top" id="modal_corte_caja">
        <div class="container_form_modal">
            <div class="head_form_modal">
                <span>Corte de Caja</span>
                <i class="bi bi-x-lg icon_close_modal" id="close_modal_cortecaja"></i>
            </div>
            <form action="" method="post" class="body_form_modal" id="cut-checkout">
                <?php
                if ($data_caja['caja'] == 0) {
                    ?>
                    <div class="bg-warning p-2 d-flex align-items-center justify-content-center" align="justify">
                        <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 2rem;"></i> Aun no se ha
                        configurado
                        el
                        monto inicial de caja. Solicita al administrador que ingrese el monto inicial desde el panel de
                        administracion en la seccion "Caja > Cambiar Monto Inicial".
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="pt-3 ps-4 pe-4 pb-1">
                        <label>Ingresa el monto que hay en la caja en las diferentes denominaciones.</label>
                        <div class="line"></div>
                    </div>

                    <div class="input-group pt-3 ps-4 pe-4 pb-1">
                        <span class="input-group-text bg-dark border border-primary text-warning">$1,000</span>
                        <input type="number" name="mil" id="mil" class="form-control border border-primary" min="0"
                            placeholder="0">
                    </div>

                    <div class="input-group pt-3 ps-4 pe-4 pb-1">
                        <span class="input-group-text bg-dark border border-primary text-warning">$500</span>
                        <input type="number" name="quinientos" id="quinientos" class="form-control border border-primary"
                            min="0" placeholder="0">
                    </div>

                    <div class="input-group pt-3 ps-4 pe-4 pb-1">
                        <span class="input-group-text bg-dark border border-primary text-warning">$200</span>
                        <input type="number" name="docientos" id="docientos" class="form-control border border-primary"
                            min="0" placeholder="0">
                    </div>

                    <div class="input-group pt-3 ps-4 pe-4 pb-1">
                        <span class="input-group-text bg-dark border border-primary text-warning">$100</span>
                        <input type="number" name="cien" id="cien" class="form-control border border-primary" min="0"
                            placeholder="0">
                    </div>

                    <div class="input-group pt-3 ps-4 pe-4 pb-1">
                        <span class="input-group-text bg-dark border border-primary text-warning">$50</span>
                        <input type="number" name="cincuenta" id="cincuenta" class="form-control border border-primary"
                            min="0" placeholder="0">
                    </div>

                    <div class="input-group pt-3 ps-4 pe-4 pb-1">
                        <span class="input-group-text bg-dark border border-primary text-warning">$20</span>
                        <input type="number" name="veinte" id="veinte" class="form-control border border-primary" min="0"
                            placeholder="0">
                    </div>

                    <div class="input-group pt-3 ps-4 pe-4 pb-1">
                        <span class="input-group-text bg-dark border border-primary text-warning">$10/5/2/1</span>
                        <input type="number" name="pesos" id="pesos" class="form-control border border-primary" min="0"
                            placeholder="0">
                    </div>

                    <div class="input-group pt-3 ps-4 pe-4 pb-1">
                        <span class="input-group-text bg-dark border border-primary text-warning">$0.50/$0.20/$0.10</span>
                        <input type="number" name="centavos" id="centavos" step="0.01"
                            class="form-control border border-primary" min="0" placeholder="0">
                    </div>

                    <div class="mb-3 mt-2" align="center">
                        <button type="submit" class="btn_execute pe-3 ps-3">Realizar Corte</button>
                    </div>
                    <?php
                }
                ?>
            </form>
        </div>
    </div>

    <div class="fade_modal_system fixed-top" id="modal_add_gasto">
        <div class="container_form_modal">
            <div class="head_form_modal">
                <span>Ingresos / Gastos</span>
                <i class="bi bi-x-lg icon_close_modal" id="close_modal_gastos"></i>
            </div>
            <form action="" method="post" class="body_form_modal" id="add-gasto">
                <?php
                if ($data_caja['caja'] == 0) {
                    ?>
                    <div class="bg-warning p-2 d-flex align-items-center justify-content-center" align="justify">
                        <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 2rem;"></i> Aun no se ha
                        configurado
                        el
                        monto inicial de caja. Solicita al administrador que ingrese el monto inicial desde el panel de
                        administracion en la seccion "Caja > Cambiar Monto Inicial".
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="d-grid pt-3 ps-4 pe-4 pb-1">
                        <label for="tipo_movimiento">Tipo de Movimiento</label>
                        <select name="tipo_movimiento" id="tipo_movimiento">
                            <option value="ingreso">Ingreso</option>
                            <option value="gasto">Gasto</option>
                        </select>
                    </div>

                    <div class="d-grid pt-3 ps-4 pe-4 pb-1">
                        <label for="monto_gasto">Cantidad</label>
                        <input type="number" name="monto_gasto" id="monto_gasto" step="0.01" min="0" required>
                    </div>

                    <div class="d-grid pt-3 ps-4 pe-4 pb-1">
                        <label for="concepto_gasto">Concepto del gasto</label>
                        <textarea placeholder="Pago de servicio, Devolución, etc." name="concepto_gasto" id="concepto_gasto"
                            required></textarea>
                    </div>

                    <div class="mb-3 mt-2" align="center">
                        <button type="submit" class="btn_execute pe-3 ps-3">Agregar</button>
                    </div>
                    <?php
                }
                ?>
            </form>
        </div>
    </div>

    <!--Contenedor Screen Alert-->
    <div class="container_main_alert fixed-top" id="container_main_alert">
        <div class="container_alert">
            <div class="head_alert">
                <span id="text_title_alert">¡ Alerta !</span>
            </div>
            <form method="post" action="" class="body_alert mb-0">
                <div class="mb-3"><span id="text_message_alert">Mensaje Alerta</span></div>
                <div id="container-btn-acept">
                    <a href="home.php" class="btn btn-success">Ver comandas</a>
                    <button type="submit" class="btn btn-danger" name="logout">Cerrar Sesión</button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>

<script src="../../../script.js?v=2"></script>

<script>
    function tabla_gastos() {
        $.ajax({
            type: "post",
            url: "../../controller/caja-tabla-gastos.php",
            data: {},
            success: function (response) {
                $("#tabla-gastos-extras").html(response);
            }
        });
    }

    $(document).ready(function () {
        tabla_gastos();
    });

    document.addEventListener('click', e => {
        if (e.target.id === 'open_modal_cortecaja') {
            document.getElementById('modal_corte_caja')?.classList.add('visible');
        }
        if (e.target.id === 'close_modal_cortecaja') {
            document.getElementById('modal_corte_caja')?.classList.remove('visible');
        }
    });


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
                            Swal.close();
                            show_alert('Exito', response.message, true);
                            document.getElementById('cut-checkout').reset();
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'Aceptar'
                            });
                        }
                    },
                    error: function () {
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


    document.addEventListener('click', e => {
        if (e.target.id === 'open_modal_gastos') {
            document.getElementById('modal_add_gasto')?.classList.add('visible');
        }
        if (e.target.id === 'close_modal_gastos') {
            document.getElementById('modal_add_gasto')?.classList.remove('visible');
        }
    });

    $('#add-gasto').submit(function (event) {
        event.preventDefault();


        // Usar SweetAlert2 para confirmación
        Swal.fire({
            title: 'Confirmar Movimiento',
            html: `
            <div style="text-align: left;">
                <div style="font-size: 14px; color: #666;" align="center">Esta acción ajustara efectivo a la caja.</div>
            </div>
        `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Continuar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Enviando información',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                var formData = new FormData(this);

                $.ajax({
                    type: 'POST',
                    url: '../../controller/caja-add-gasto.php',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'EXITO') {
                            tabla_gastos();
                            Swal.close();
                            document.getElementById('add-gasto').reset();
                            document.getElementById('modal_add_gasto')?.classList.remove('visible');
                            Swal.fire({
                                title: 'Exito',
                                text: 'Se agrego un movimiento extra a la caja',
                                icon: 'success',
                                confirmButtonText: 'Aceptar'
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
                    error: function () {
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

    function eliminar_movimiento(id) {
        Swal.fire({
            title: 'Eliminar Movimiento',
            html: `
            <div style="text-align: left;">
                <div style="font-size: 14px; color: #666;" align="center">Esta acción revertira el ajuste de caja.</div>
            </div>
        `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Continuar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Enviando información',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                $.ajax({
                    type: 'POST',
                    url: '../../controller/caja-delete-movimiento.php',
                    data: {id: id},
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'EXITO') {
                            tabla_gastos();
                            Swal.close();
                            Swal.fire({
                                title: 'Exito',
                                text: 'Se elimino el movimiento extra de caja',
                                icon: 'success',
                                confirmButtonText: 'Aceptar'
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
                    error: function () {
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
    }
</script>