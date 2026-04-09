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
                <a href="corte-caja.php" type="sumbit" class="btn_dropdown" name="logout"><i class="bi bi-cash-coin me-2"></i>Corte de Caja</a>
                <button type="sumbit" class="btn_dropdown" name="logout"><i
                        class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</button>
            </form>
        </div>
    </div>

    <!--Contenedor Principal Inicio-->
    <div class="container_main_home" id="container_main_home">
        <div class="lista_comandas">
            <button type="button" class="btn_list_comandas lc_selected" data-vista="lista-comandas-">Por cobrar</button>
            <button type="button" class="btn_list_comandas" data-vista="lista-comandas-">Cobrados</button>
            <button type="button" class="btn_list_comandas" data-vista="lista-comandas-">Todas</button>
        </div>

        <div id="container_lista_comandas"></div>
    </div>



    <!--Contenedor Modal Cobrar Comanda-->
    <div class="fade_system fixed-top" id="detalles_comanda">
        <div class="system_modal">
            <div class="header_modal">
                <span>Cobrar Comanda</span>
                <i class="bi bi-x-lg icon_close_modal" id="close_modal_cobrarcomanda"></i>
            </div>
            <div class="body_modal pe-4 ps-4 p-3" id="body_modal_detallescomanda">

            </div>
        </div>
    </div>


    <!--Contenedor Metodo de Pago-->
    <div class="fade_system fixed-top" id="modal_metodo_pago">
        <div class="system_modal">
            <div class="header_modal">
                <span id="text_metodo_pago">Metodo de Pago</span>
                <i class="bi bi-x-lg icon_close_modal" id="close_modal_metodopago"></i>
            </div>
            <div class="body_modal pe-4 ps-4 p-3 pt-0" id="body_modal_metodopago">

            </div>
        </div>
    </div>



    <!--Ticket-->
    <div class="fade_system fixed-top" id="modal_ticket">
        <div class="system_modal">
            <div class="header_modal">
                <span id="text_metodo_pago">Ticket</span>
                <button type="button" class="btn btn-light" id="print-ticket"><i class="bi bi-printer-fill" style="font-size: 1.4rem;"></i></button>
                <i class="bi bi-x-lg icon_close_modal" id="close_modal_ticket"></i>
            </div>
            <iframe id="ticketFrame" style="width:100%; height: 100%; background: #e2e2e2;">

            </iframe>

            <iframe id="ticketFrameAuxiliar" style="display: none;">

            </iframe>
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
            <input type="hidden" name="ubicacion" value="caja">
            <div>
                <button type="submit">Ok</button>
            </div>
        </form>
        ';
    }
    ?>
</body>

</html>

<script src="../../../script.js"></script>
<script src="script.js"></script>