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

    if ($datauser['rol'] == 'Mesero') {
        header('Location: ../mesero/home.php');
    }elseif ($datauser['rol'] == 'Caja') {
        header('Location: ../caja/home.php');
    }elseif ($datauser['rol'] == 'Barra') {
        header('Location: ../barra/home.php');
    }elseif ($datauser['rol'] == 'Cocina') {
        header('Location: ../cocina/home.php');
    }

    if ($day >= 01 && $day <= 05) {
        $recordatorio = data_servicio();

        if ($recordatorio['visto_admin'] == 0) {
            $recordar = 1;
        } else if ($recordatorio['visto_admin'] == 1) {
            $recordar = 0;
        }
    } else {
        $recordar = 0;
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="../../../favicon.ico">
    <title>Conejo Blanco - Panel de Administración</title>
    <link href="../../../style.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>

<body class="body-page" id="body-page">
    <?php
    include('../../navbar.php');
    ?>
    <!--Contenedor Principal Inicio-->
    <div class="container_main_home" id="container_main_home">

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
            <input type="hidden" name="ubicacion" value="admin">
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
<script>
    $('#container_main_home').load('dashboard.php');
</script>