<?php
include('../model/querys.php');
$array_categorias = data_categorias_aux();
$array_productos = data_productos();
$array_combos = data_combos();

// Agrupacion por categorias
$grupoProductos = [];
if ($array_productos) {
    foreach ($array_productos as $p) {
        $categoriaId = $p['id_categoria'];
        $grupoProductos[$categoriaId][] = $p;
    }
}
?>

<div class="division_categorias_secciones">
    <span class="fw-bold">COMBOS</span>
</div>

<div class="carrousel_productos pt-2 pb-2 mb-1">
    <?php
    if ($array_combos) {
        foreach ($array_combos as $combo) {
            $comboName = mb_strtolower($combo['combo']);
            $searchCombo = htmlspecialchars($comboName . ' combos');
            if ($combo['disponibilidad'] == 0) {
                $accion_combo = 'onclick="armar_combo(' . $combo['id'] . ')"';
                $class_combo = 'card_producto';
                $text_agotado = '';
            } else if ($combo['disponibilidad'] == 1) {
                $accion_combo = '';
                $class_combo = 'card_producto agotado';
                $text_agotado = '<span class="p-1 fw-bold rounded shadow bg-secondary text-light">Agotado</span>';

            }
            ?>
            <div class="<?= $class_combo ?>" data-search="<?= $searchCombo ?>" <?= $accion_combo ?>>
                <div style="height: 100%;
                        width: 100%; 
                        background: rgb(0, 0, 0, 0.1) url('../../files/img_products/default.webp') center center / cover no-repeat; 
                        border-radius: 10px 10px 0px 0px;
                        background-blend-mode: darken;">
                    <div class="d-flex align-items-center justify-content-center"
                        style="font-size: 0.8rem; height: 100%; width: 100%;"><?= $text_agotado ?></div>
                </div>
                <div class="comanda_producto_descipcion">
                    <div class="p-1"><span class="lh-1 text-uppercase">
                            <?= $combo['combo'] ?>
                        </span></div>
                    <div class="pe-1 ps-1"><span class="text-primary">$
                            <?= number_format($combo['precio'], 2) ?>
                        </span></div>
                </div>
            </div>
            <?php
        }
    }
    ?>
</div>
<?php
if ($array_categorias) {
    foreach ($array_categorias as $categoria) {
        $categoriaId = $categoria['id'];
        $productos = $grupoProductos[$categoriaId] ?? [];
        //$count_productos = count($productos);

        if (!$productos)
            continue;
        ?>
        <div class="division_categorias_secciones" data-cat="<?= htmlspecialchars(mb_strtolower($categoria['categoria'])) ?>">
            <span class="fw-bold"><?= $categoria['categoria'] ?></span>
        </div>
        <div class="carrousel_productos pt-2 pb-2 mb-1"
            data-cat="<?= htmlspecialchars(mb_strtolower($categoria['categoria'])) ?>">
            <?php
            foreach ($productos as $producto) {
                $count_variantes = variantes_producto($producto['id']);
                $n_variantes = $count_variantes ? count($count_variantes) : 0;

                if ($n_variantes === 0) {
                    $pId = (int) $producto['id'];
                    $pName = htmlspecialchars($producto['producto'], ENT_QUOTES);
                    $pPrecio = (float) $producto['precio'];
                    $df = strtolower($categoria['destino']);
                    $funcion = "addprodto_pedido($pId, '$pName', $pPrecio,'$df')";
                } else {
                    $funcion = 'select_variante(' . (int) $producto['id'] . ')';
                }

                $catName = mb_strtolower($categoria['categoria']);
                $prodName = mb_strtolower($producto['producto']);
                $searchText = htmlspecialchars($prodName . ' ' . $catName);
                if ($producto['disponibilidad']== 0) {
                    $accion_producto = 'onclick="'.$funcion.'"';
                    $class_producto = 'card_producto';
                    $agotado = '';
                } else if ($producto['disponibilidad'] = 1) {
                    $accion_producto = '';
                    $class_producto = 'card_producto agotado';
                    $agotado = '<span class="p-1 fw-bold rounded shadow bg-secondary text-light">Agotado</span>';
                }
                ?>

                <div class="<?= $class_producto ?>" data-search="<?= $searchText ?>" <?= $accion_producto ?>>
                    <div style="height: 100%;
                        width: 100%; 
                        background: rgb(0, 0, 0, 0.1) url('../../files/img_products/<?= $producto['photo'] ?>') center center / cover no-repeat; 
                        border-radius: 10px 10px 0px 0px;
                        background-blend-mode: darken;">
                        <div class="d-flex align-items-center justify-content-center"
                        style="font-size: 0.8rem; height: 100%; width: 100%;"><?= $agotado ?></div>
                    </div>
                    <div class="comanda_producto_descipcion">
                        <div class="p-1"><span class="lh-1 text-uppercase"><?= $producto['producto'] ?></span>
                        </div>
                        <div class="pe-1 ps-1"><span class="text-primary">$<?= number_format($producto['precio'], 2) ?></span></div>
                    </div>
                </div>

                <?php
            }
            ?>
        </div>
        <?php
    }
}
?>