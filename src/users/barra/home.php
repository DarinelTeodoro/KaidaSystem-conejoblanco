<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include('../../../cdn.html');
include('../../model/querys.php');

$day = date('d');

if (empty($_SESSION['data-useractive'])) {
    echo '<script>window.location.href = "../../../index.php";</script>';
} else {
    $datauser = consultar_usuario($_SESSION['data-useractive']);

    if ($datauser['rol'] == 'Administrador') {
        header('Location: ../administrador/home.php');
    }elseif ($datauser['rol'] == 'Caja') {
        header('Location: ../caja/home.php');
    }elseif ($datauser['rol'] == 'Mesero') {
        header('Location: ../mesero/home.php');
    }elseif ($datauser['rol'] == 'Cocina') {
        header('Location: ../cocina/home.php');
    }

    if ($day >= 01 && $day <= 05) {
        $recordatorio = data_servicio();

        if ($recordatorio['visto_barra'] == 0) {
            $recordar = 1;
        } else if ($recordatorio['visto_barra'] == 1) {
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
    <title>Barra - Inicio</title>
    <link href="../../../style.css?v=2" rel="stylesheet">
    <link href="style.css?v=2" rel="stylesheet">

    <style>
        #body-page {
            background: #eff0ff;
            padding: 80px 0px 0px;
        }
    </style>
</head>

<body id="body-page">
    <div class="system_navbar_general fixed-top">
        <div>
            <img src="../../../img/shortlogo-nbg.png" class="img_logo_navbar">
        </div>
        <div class="d-flex align-items-center justify-content-between dropdown">
            <div class="text_logo"></div>
            <button type="button" id="btnAudioBarra" class="btn btn-danger ms-3 me-3"
                style="border-radius: 0px; width: 50px; height: 35pxpx;">
                <i class="bi bi-volume-mute"></i>
            </button>

            <div class="navbar_box_user" type="button" data-bs-toggle="dropdown" data-bs-auto-close="false" aria-expanded="false">
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
                <button type="button" class="btn_dropdown" id="open_modal_outproducts"><i
                        class="bi bi-cart-x-fill me-2"></i>Productos Agotados</button>
                <button type="button" class="btn_dropdown" id="open_modal_history"><i
                        class="bi bi-clipboard-minus-fill me-2"></i>Historial Realizados</button>
                <div class="d-flex align-items-center justify-content-center" style="width: 100%; height: 100px;">
                    <label class="me-2">Audio:</label>
                    <button type="button" id="btnAudioBarra" class="btn btn-danger"
                        style="border-radius: 0px; width: 50px; height: 35pxpx;">
                        <i class="bi bi-volume-mute"></i>
                    </button>
                </div>
                <button type="sumbit" class="btn_dropdown" name="logout"><i
                        class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</button>
            </form>
        </div>
    </div>

    <!--Contenedor Principal Inicio-->
    <div class="container_main_home" id="container_main_home">
        <div id="tickets-container">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando comandas...</p>
            </div>
        </div>
    </div>


    <!--Modal productos agotados-->
    <div class="fade_system fixed-top" id="modal_out_products">
        <div class="system_modal">
            <div class="header_modal">
                <span>Productos Agotados</span>
                <i class="bi bi-x-lg icon_close_modal" id="close_modal_outproducts"></i>
            </div>
            <div class="body_modal pe-4 ps-4 p-3" id="body_modal_outproductos">
                <div class="text-center text-muted">Cargando...</div>
            </div>
        </div>
    </div>


    <!--Modal historial-->
    <div class="fade_system fixed-top" id="modal_history">
        <div class="system_modal">
            <div class="header_modal">
                <span>Pedidos Completados</span>
                <i class="bi bi-x-lg icon_close_modal" id="close_modal_history"></i>
            </div>
            <div class="body_modal pe-4 ps-4 p-3" id="body_modal_history">
                <div class="text-center text-muted">Cargando...</div>
            </div>
        </div>
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

    <?php
    if ($recordar == 1) {
        echo '
        <form method="post" action="" id="item-recordatorio" class="recordatorio bg-warning visible">
            <div><span>'.$recordatorio['alerta'].'</span></div>
            <input type="hidden" name="ubicacion" value="barra">
            <div>
                <button type="submit">Ok</button>
            </div>
        </form>
        ';
    }
    ?>
</body>

</html>

<script src="../../../script.js?v=2"></script>
<script src="script.js?v=2"></script>