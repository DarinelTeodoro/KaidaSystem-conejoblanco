<?php
session_start();
include('../../../cdn.html');
include('../../model/querys.php');

$zona_horaria = new DateTimeZone('America/Mexico_City');
$fecha = new DateTime('now', $zona_horaria);

if (empty($_SESSION['data-useractive'])) {
    header('Location: ../../../index.php');
} else {
    $datauser = consultar_usuario($_SESSION['data-useractive']);

    if ($datauser['rol'] == 'Administrador') {
        header('Location: ../administrador/home.php');
    } elseif ($datauser['rol'] == 'Caja') {
        header('Location: ../caja/home.php');
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
    <title>Mesero - Inicio</title>
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
    <a href="form-add-comanda.php" class="floating_button">
        <i class="bi bi-clipboard-plus-fill"></i>
    </a>

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
                <button type="sumbit" class="btn_dropdown" name="logout"><i
                        class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</button>
            </form>
        </div>
    </div>

    <!--Contenedor Principal Inicio-->
    <div class="container_main_home" id="container_main_home">
        <div class="lista_comandas">
            <button type="button" class="btn_list_comandas lc_selected" data-vista="lista-comandas-">Pendientes</button>
            <button type="button" class="btn_list_comandas" data-vista="lista-comandas-">Finalizados</button>
            <button type="button" class="btn_list_comandas" data-vista="lista-comandas-">Todas</button>
        </div>

        <div id="container_lista_comandas"></div>
    </div>


    <!--Modal detalle comanda-->
    <div class="fade_system fixed-top" id="modal_detalle_comanda">
        <div class="system_modal">
            <div class="header_modal">
                <span>Detalle de Comanda</span>
                <i class="bi bi-x-lg icon_close_modal" id="close_modal_detalle_comanda"></i>
            </div>
            <div class="body_modal pe-4 ps-4 p-3" id="body_modal_detalle_comanda">
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
</body>

</html>

<script src="../../../script.js?v=2"></script>
<script src="script.js?v=2"></script>