<?php
include('../model/querys.php');
$array_categorias = data_categorias();
$array_productos = data_productos();

// Agrupacion por categorias
$grupoProductos = [];
if ($array_productos) {
    foreach ($array_productos as $p) {
        $categoriaId = $p['id_categoria'];
        $grupoProductos[$categoriaId][] = $p;
    }
}

if ($array_categorias) {
    foreach ($array_categorias as $categoria) {
        $categoriaId = $categoria['id'];
        $productos = $grupoProductos[$categoriaId] ?? [];
        //$count_productos = count($productos);

        if (!$productos)
            continue;
        ?>
        <div class="division_categorias_secciones">
            <span><?= $categoria['categoria'] ?></span>
        </div>
        <div class="btn-group cont_btn_checks" role="group">
            <?php
            foreach ($productos as $producto) {
                ?>

                <div class="p-1">
                    <input type="checkbox" class="btn-check" name="checkbox_producto[]" id="<?= $producto['id'] ?>"
                        value="<?= $producto['id'] ?>">
                    <label class="btn btn-outline-primary label_check p-0" for="<?= $producto['id'] ?>">
                        <div class="d-flex align-items-center justify-content-center">
                            <div
                                style="height: 70; width: 70px; background: rgb(0, 0, 0, 0) url('../../files/img_products/<?= $producto['photo'] ?>') center center / cover no-repeat;">
                            </div>
                        </div>
                        <div>
                            <?= $producto['producto'] ?>
                        </div>
                    </label>
                </div>

                <?php
            }
            ?>
        </div>
        <?php
    }
}
?>