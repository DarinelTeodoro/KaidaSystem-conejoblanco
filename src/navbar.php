<?php
$caja = data_servicio();
?>

<div class="system_navbar fixed-top">
    <div class="head_navbar">
        <div class="container_circle_photo">
            <div class="circle_photo"
                style="background: rgb(0, 0, 0, 0) url('../../files/img_users/<?= $datauser['photo'] ?>') center center / cover no-repeat;">
                <!-- strtoupper(substr($datauser['name'], 0, 1)) -->
            </div>
        </div>
        <div class="container_info_user" style="line-height: 16px;">
            <div><b><?= $datauser['name'] ?></b></div>
            <div><i>Panel de Administración</i></div>
        </div>
    </div>
    <form method="post" action="" class="body_navbar">
        <div class="title_secciones_menu">Navegación</div>
        <button type="button" data-modulo="dashboard" class="navbar_option">
            <div class="cont_icon_menu"><i class="bi bi-house-door"></i></div>
            <div class="cont_text_menu">Inicio</div>
        </button>

        <div class="title_secciones_menu">Gestión</div>
        <button type="button" data-modulo="editar-menu" class="navbar_option">
            <div class="cont_icon_menu"><i class="bi bi-pencil-square"></i></div>
            <div class="cont_text_menu">Editar Menú</div>
        </button>
        <button type="button" data-modulo="configurar-mesas" class="navbar_option">
            <div class="cont_icon_menu"><i class="bi bi-grid-3x3-gap"></i></div>
            <div class="cont_text_menu">Mesas</div>
        </button>
        <button type="button" data-modulo="gestion-comandas" class="navbar_option">
            <div class="cont_icon_menu"><i class="bi bi-clipboard"></i></div>
            <div class="cont_text_menu">Gestionar Comandas</div>
        </button>
        <!--<button type="button" data-modulo="menu-publico" class="navbar_option">
            <div class="cont_icon_menu"><i class="bi bi-egg-fried"></i></div>
            <div class="cont_text_menu">Ver mi Menú</div>
        </button>-->

        <div class="title_secciones_menu">Estadísticas</div>
        <button type="button" data-modulo="estadisticas-operativas" class="navbar_option">
            <div class="cont_icon_menu"><i class="bi bi-graph-up"></i></div>
            <div class="cont_text_menu">Ventas</div>
        </button>
        <button type="button" data-modulo="reporte-caja" class="navbar_option">
            <div class="cont_icon_menu"><i class="bi bi-cash-coin"></i></div>
            <div class="cont_text_menu">Caja</div>
        </button>

        <div class="title_secciones_menu">Administración</div>
        <button type="button" data-modulo="gestion-users" class="navbar_option">
            <div class="cont_icon_menu"><i class="bi bi-people"></i></div>
            <div class="cont_text_menu">Usuarios</div>
        </button>
        <button type="submit" class="navbar_option_logout" name="logout">
            <div class="cont_icon_menu"><i class="bi bi-power"></i></div>
            <div class="cont_text_menu">Salir</div>
        </button>
    </form>
</div>


<form method="post" action="" class="system_navbar_responsive fixed-bottom">
    <button type="button" data-modulo="dashboard" class="option_responsive">
        <div class="icon_responsive"><i class="bi bi-house-door"></i></div>
        <div class="text_menu_responsive">Inicio</div>
    </button>
    <button type="button" data-modulo="editar-menu" class="option_responsive">
        <div class="icon_responsive"><i class="bi bi-pencil-square"></i></div>
        <div class="text_menu_responsive">Editar Menu</div>
    </button>
    <button type="button" data-modulo="configurar-mesas" class="option_responsive">
        <div class="icon_responsive"><i class="bi bi-grid-3x3-gap"></i></div>
        <div class="text_menu_responsive">Mesas</div>
    </button>
    <button type="button" data-modulo="gestion-comandas" class="option_responsive">
        <div class="icon_responsive"><i class="bi bi-clipboard"></i></div>
        <div class="text_menu_responsive">Gestionar Comandas</div>
    </button>
    <!--<button type="button" data-modulo="menu-publico" class="option_responsive">
        <div class="icon_responsive"><i class="bi bi-egg-fried"></i></div>
        <div class="text_menu_responsive">Ver mi Menú</div>
    </button>-->
    <button type="button" data-modulo="estadisticas-operativas" class="option_responsive">
        <div class="icon_responsive"><i class="bi bi-graph-up"></i></div>
        <div class="text_menu_responsive">Ventas</div>
    </button>
    <button type="button" data-modulo="reporte-caja" class="option_responsive">
        <div class="icon_responsive"><i class="bi bi-cash-coin"></i></div>
        <div class="text_menu_responsive">Caja</div>
    </button>
    <button type="button" data-modulo="gestion-users" class="option_responsive">
        <div class="icon_responsive"><i class="bi bi-people"></i></div>
        <div class="text_menu_responsive">Usuarios</div>
    </button>
    <button type="submit" class="option_responsive" name="logout">
        <div class="icon_responsive"><i class="bi bi-power"></i></div>
        <div class="text_menu_responsive">Salir</div>
    </button>
</form>

<script src="../../script.js"></script>

<?php
if (isset($_POST['logout'])) {
    session_destroy();
    echo '<script>window.location.href = "../../../index.php";</script>';
}
?>