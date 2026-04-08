<?php
include('../model/querys.php');
$array_productos = data_productos();
?>

<style>
    .variantes_precios {
        font-size: 1.4rem;
    }

    .icono-change-value {
        font-size: 1.4rem;
    }
</style>

<div class="container-fliud d-flex align-items-center justify-content-between container_title_seccion">
    <div><strong>Variantes</strong></div>
    <button type="button" class="btn-add" id="open_modal_addvariante">Agregar Variante</button>
</div>


<!-- Grid de tarjetas de productos-->
<div class="pt-3">
    <div class="accordion row g-3" id="accorrdionVariantes">
        <?php
        $array_variantes = data_variantes();

        // Agrupacion por producto
        $variantesPorProducto = [];
        if ($array_variantes) {
            foreach ($array_variantes as $var) {
                $prodId = $var['id_producto'];
                $variantesPorProducto[$prodId][] = $var;
            }
        }
        ?>

        <?php if ($array_productos):
            foreach ($array_productos as $pro): ?>
                <?php
                $prod_Id = $pro['id'];
                $variantes = $variantesPorProducto[$prod_Id] ?? [];
                $count_variantes = count($variantes);
                if (!$variantes)
                    continue;
                ?>

                <div class="accordion-item col-12 col-md-6 col-xl-4">
                   <h2 class="accordion-header">
                        <button class="accordion-button collapsed text-uppercase" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapse-variante-<?= $pro['id'] ?>" aria-expanded="false" aria-controls="collapse-variante-<?= $pro['id'] ?>">
                            <i class="bi bi-box-seam me-2"></i><?= htmlspecialchars($pro['producto']) ?><b class="ms-2 bg-secondary ps-2 pe-2 rounded text-light"><?= $count_variantes ?></b>
                        </button>
                    </h2> 

                    <div id="collapse-variante-<?= $pro['id'] ?>" class="accordion-collapse collapse pt-2 pb-2 chip_list" data-bs-parent="#collapse-variante-<?= $pro['id'] ?>">
                        <?php foreach ($variantes as $variante): 
                            $precioBase = (float)$pro['precio'];
                            $incremento = (float)$variante['incremento'];

                            $precioFinal = $precioBase + $incremento;
                        ?>
                                <div class="chip">
                                    <span class="me-2"><?php echo $variante['variante'] ?></span>
                                    <span class="me-3">$<?php echo number_format($precioFinal, 2) ?></span>
                                    <span>|</span>
                                    <i class="bi bi-pen" onclick="edit_variante('<?=$variante['id'] ?>')"></i> |
                                    <i class="bi bi-trash" onclick="delete_variante('<?=$variante['id'] ?>')"></i> |
                                </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>


<div class="fade_modal_system fixed-top" id="modal_add_variante">
    <div class="container_form_modal">
        <div class="head_form_modal">
            <span>Agregar Variante</span>
            <i class="bi bi-x-lg icon_close_modal" id="close_modal_addvariante"></i>
        </div>
        <form action="" method="post" class="body_form_modal" id="form_add_variante">
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="producto_addvariante">Producto</label>
                <select name="producto_addvariante" id="producto_addvariante">
                    <option value="0" selected disabled>Selecciona un Producto</option>
                    <?php
                    if ($array_productos) {
                        foreach ($array_productos as $producto) {
                            echo '
                                <option value="' . $producto['id'] . '">' . $producto['producto'] . '</option>
                            ';
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="name_addvariante">Variante</label>
                <input type="text" name="name_addvariante" id="name_addvariante" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="precio_addvariante">Precio Agregado</label>
                <input type="number" name="precio_addvariante" id="precio_addvariante" required>
            </div>
            <div class="d-flex justify-content-around align-items-center text-center mt-2 mb-2">
                <div>
                    <div><span>Precio Basico</span></div>
                    <div><span class="variantes_precios text-primary">$<span id="var_precioproducto">0.00</span></span>
                    </div>
                    <input type="hidden" id="inputvar_precioproducto">
                </div>
                <div class="icono-change-value"><i class="bi bi-arrow-right"></i></div>
                <div>
                    <div><span>Precio Variante</span></div>
                    <div><span class="variantes_precios text-success">$<span id="var_preciofinal">0.00</span></span>
                    </div>
                </div>
            </div>
            <div class="p-2 ps-4 pe-4 pb-4 d-grid">
                <button type="submit" class="btn_execute_modal" id="btn_add_variante">Agregar Variante</button>
            </div>
        </form>
    </div>
</div>



<div class="fade_modal_system fixed-top" id="modal_edit_variante">
    <div class="container_form_modal">
        <div class="head_form_modal">
            <span>Editar Variante</span>
            <i class="bi bi-x-lg icon_close_modal" id="close_modal_editvariante"></i>
        </div>
        <form action="" method="post" class="body_form_modal" id="form_edit_variante">
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="name_editvariante">Variante</label>
                <input type="text" name="name_editvariante" id="name_editvariante" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="precio_editvariante">Precio Agregado</label>
                <input type="number" name="precio_editvariante" id="precio_editvariante" required>
            </div>
            <div class="p-2 ps-4 pe-4 pb-4 d-grid">
                <input type="hidden" name="id_editvariante" id="id_editvariante">
                <button type="submit" class="btn_execute_modal">Actualizar Variante</button>
            </div>
        </form>
    </div>
</div>


<script>
    document.getElementById('name_addvariante').disabled = true;
    document.getElementById('precio_addvariante').disabled = true;
    document.getElementById('btn_add_variante').disabled = true;


    $('#form_add_variante').submit(function (event) {
        event.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: '../../controller/menu-variantes-add.php',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-variantes.php');
                } else {
                    show_alert(response.status, response.message);
                }
            }
        });
    });

    $('#form_edit_variante').submit(function (event) {
        event.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: '../../controller/menu-variantes-edit.php',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-variantes.php');
                } else {
                    show_alert(response.status, response.message);
                }
            }
        });
    });
</script>