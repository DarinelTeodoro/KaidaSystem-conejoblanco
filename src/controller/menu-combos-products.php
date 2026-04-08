<?php
include('../model/querys.php');
$id_combo = $_POST['id'];

$data_grupos = data_grupos($id_combo);

if ($data_grupos) {
    foreach ($data_grupos as $grupo) {
        $bg_color = $grupo['tipo'] === 'multiple' ? 'bg-danger' : ($grupo['tipo'] === 'unico' ? 'bg-primary' : 'bg-success');
        $tipo_seccion = $grupo['tipo'] === 'multiple' ? 'Puedes elegir varios de los siguientes productos.' : ($grupo['tipo'] === 'unico' ? 'Solo puedes elegir uno de los siguientes productos.' : 'Los siguientes productos siempre van en el combo.');
        ?>

        <div class="d-flex align-items-center justify-content-between pe-1 ps-1">
            <div class="d-flex align-items-center justify-content-start">
                <div class="<?= $bg_color ?> me-1" style="width: 5px; height: 5px;"></div><span class="fw-bold"><?= $grupo['grupo'] ?></span>
            </div>
            <i class="bi bi-trash text-danger" onclick="delete_group(<?= $grupo['id'] ?>)" style="cursor: pointer;"></i>
        </div>
        <div class="line mt-1 mb-0"></div>
        <i class="text-muted" style="font-size: 0.8rem;" align="justify"><?= $tipo_seccion ?></i>

        <?php
        $productos = json_decode($grupo['productos'], true) ?? [];
        if (!empty($productos)) {
            echo '<ol>';
            foreach ($productos as $producto) {
                $prod = consultar_producto($producto);
                echo '
                    <li>'.$prod['producto'].'</li>
                ';
            }
            echo '</ol>';
        }
    }
} else {
    ?>

    <div class="d-flex align-items-center justify-content-center ps-3 pe-3">
        <span class="fw-bold">Sin Grupos/Productos</span>
    </div>

    <?php
}
?>