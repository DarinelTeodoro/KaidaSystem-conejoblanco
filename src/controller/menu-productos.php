<?php
include('../model/querys.php');
$array_categoriastable = data_categorias();

function renderCategoriasOptions($categorias)
{
    if (!$categorias)
        return;

    foreach ($categorias as $c) {
        echo '<option class="text-uppercase" value="' . $c['id'] . '">' .
            htmlspecialchars($c['categoria']) .
            '</option>';
    }
}
?>

<style>
    .details_producto {
        width: 100%;

        background: rgb(255, 255, 255);
        display: grid;
        grid-template-columns: 81.6px minmax(0, 1fr) 70px;

        box-shadow: rgba(50, 50, 93, 0.25) 0px 2px 5px -1px, rgba(0, 0, 0, 0.3) 0px 1px 3px -1px;
    }

    .details_producto>div:nth-child(2) {
        min-width: 0;
    }
</style>

<div class="container-fliud d-flex align-items-center justify-content-between container_title_seccion">
    <div><strong>Productos</strong></div>
    <button type="button" class="btn-add" id="open_modal_addproducto">Agregar Producto</button>
</div>

<!-- Grid de tarjetas de productos-->
<div class="pt-3">
    <div class="accordion" id="accorrdionProductos">
        <?php
        $array_productostable = data_productos();

        // Agrupacion por categorias
        $productosPorCategoria = [];
        if ($array_productostable) {
            foreach ($array_productostable as $p) {
                $catId = $p['id_categoria'];
                $productosPorCategoria[$catId][] = $p;
            }
        }
        ?>

        <?php if ($array_categoriastable): ?>
            <?php foreach ($array_categoriastable as $cat): ?>
                <?php
                $catId = $cat['id'];
                $productos = $productosPorCategoria[$catId] ?? [];
                $count_productos = count($productos);
                if (!$productos)
                    continue;
                ?>

                <div class="accordion-item mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed text-uppercase" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapse-producto-<?= $cat['id'] ?>" aria-expanded="false" aria-controls="collapse-producto-<?= $cat['id'] ?>">
                            <?= htmlspecialchars($cat['categoria']) ?><b class="ms-2 bg-secondary ps-2 pe-2 rounded text-light"><?= $count_productos ?></b>
                        </button>
                    </h2>

                    <div id="collapse-producto-<?= $cat['id'] ?>" class="accordion-collapse collapse row g-3 pt-3 pb-3" data-bs-parent="#collapse-producto-<?= $cat['id'] ?>">
                        <?php foreach ($productos as $productotable):
                            //$icono_producto = $productotable[''] === 'Barra' ? '<i class="bi bi-egg-fried me-2"></i>' : '<i class="bi bi-cup-hot me-2"></i>';
                        ?>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="details_producto">
                                    <div style="background: rgb(0, 0, 0, 0) url('../../files/img_products/<?= $productotable['photo'] ?>') center center / cover no-repeat;">
                                        <!--<img src="../../files/img_products/"
                                            style="height: 81.6px; width: 81.6px;">-->
                                    </div>
                                    <div>
                                        <div class="p-2">
                                            <h6 class="mb-0 fw-semibold text-uppercase text-truncate"
                                                title="<?= htmlspecialchars($productotable['producto']) ?>">
                                                <?= htmlspecialchars($productotable['producto']) ?>
                                            </h6>
                                        </div>
                                        <div class="p-2 pt-0">
                                            <div class="text-muted small">
                                                <span class="fw-bold text-success">
                                                    $
                                                    <?= number_format((float) $productotable['precio'], 2) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-grid justify-content-end align-items-center">
                                        <button type="button" class="btn-edit" onclick="edit_producto('<?= $productotable['id'] ?>')">
                                            <i class="bi bi-pen"></i>
                                        </button>
                                        <button type="button" class="btn-delete"
                                            onclick="delete_producto('<?= $productotable['id'] ?>')">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>


<div class="fade_modal_system fixed-top" id="modal_add_producto">
    <div class="container_form_modal">
        <div class="head_form_modal">
            <span>Agregar Producto</span>
            <i class="bi bi-x-lg icon_close_modal" id="close_modal_addproducto"></i>
        </div>
        <form action="" method="post" class="body_form_modal" id="form_add_producto" enctype="multipart/form-data">
            <div class="d-grid justify-content-center pt-3 ps-4 pe-4 pb-1" align="center">
                <label class="label_img_cards" for="img_addproducto" id="label_img_addproducto">
                    <i class="bi bi-camera-fill"></i>
                </label>
                <input type="file" accept="image/png, image/jpeg, image/webp" name="img_addproducto"
                    id="img_addproducto">
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="name_addproducto">Nombre del Producto</label>
                <input type="text" name="name_addproducto" id="name_addproducto" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="descripcion_addproducto">Descripción</label>
                <textarea rows="3" name="descripcion_addproducto" id="descripcion_addproducto"></textarea>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="precio_addproducto">Precio del Producto</label>
                <input type="number" name="precio_addproducto" id="precio_addproducto" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="categoria_addproducto">Categoria</label>
                <select name="categoria_addproducto" id="categoria_addproducto">
                    <?php
                    renderCategoriasOptions($array_categoriastable);
                    ?>
                </select>
            </div>
            <div class="p-2 ps-4 pe-4 pb-4 d-grid">
                <button type="submit" class="btn_execute_modal">Agregar Producto</button>
            </div>
        </form>
    </div>
</div>


<div class="fade_modal_system fixed-top" id="modal_edit_producto">
    <div class="container_form_modal">
        <div class="head_form_modal">
            <span>Editar Producto</span>
            <i class="bi bi-x-lg icon_close_modal" id="close_modal_editproducto"></i>
        </div>
        <form action="" method="post" class="body_form_modal" id="form_edit_producto" enctype="multipart/form-data">
            <div class="d-grid justify-content-center pt-3 ps-4 pe-4 pb-1" align="center">
                <label class="label_img_cards" for="img_editproducto" id="label_img_editproducto">
                    <i class="bi bi-camera-fill"></i>
                </label>
                <input type="file" accept="image/png, image/jpeg, image/webp" name="img_editproducto"
                    id="img_editproducto">
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="name_editproducto">Nombre del Producto</label>
                <input type="text" name="name_editproducto" id="name_editproducto" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="descripcion_editproducto">Descripción</label>
                <textarea rows="3" name="descripcion_editproducto" id="descripcion_editproducto"></textarea>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="precio_editproducto">Precio</label>
                <input type="number" name="precio_editproducto" id="precio_editproducto">
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="categoria_editproducto">Categoria</label>
                <select name="categoria_editproducto" id="categoria_editproducto">
                    <?php
                    renderCategoriasOptions($array_categoriastable);
                    ?>
                </select>
            </div>
            <div class="p-2 ps-4 pe-4 pb-4 d-grid">
                <input type="hidden" name="id_editproducto" id="id_editproducto" readonly>
                <button type="submit" class="btn_execute_modal">Actualizar Producto</button>
            </div>
        </form>
    </div>
</div>

<script>
    $('#form_add_producto').submit(function (event) {
        event.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: '../../controller/menu-productos-add.php',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-productos.php');
                } else {
                    show_alert(response.status, response.message);
                }
            }
        });
    });

    $('#form_edit_producto').submit(function (event) {
        event.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: '../../controller/menu-productos-edit.php',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-productos.php');
                } else {
                    show_alert(response.status, response.message);
                }
            }
        });
    });
</script>