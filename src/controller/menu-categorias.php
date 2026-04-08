<?php
include('../model/querys.php');
?>


<div class="container-fliud d-flex align-items-center justify-content-between container_title_seccion">
    <div><strong>Categorias</strong></div>
    <button type="button" class="btn-add" id="open_modal_addcategoria">Agregar Categoria</button>
</div>


<!-- Grid de tarjetas de categorias -->
<div class="row g-3 pt-3" id="catsContainer">
    <!--<div class="col-12 col-md-6 col-xl-4 cat-card">
        <div class="card h-100 shadow-sm border-0" style="border-radius:1rem;">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <h6 class="mb-0 fw-semibold text-truncate text-uppercase" title="COMBOS">
                        <i class="bi bi-folder2-open me-1"></i>COMBOS
                    </h6>
                    <span class="badge text-bg-warning">
                        <i class="bi bi-shuffle me-1"></i>Ambos
                    </span>
                </div>
                <div class="text-muted small flex-grow-1" style="min-height:2.2em;">
                    <span class="opacity-60">Paquetes de platillos y bebidas en una sola opción.</span>
                </div>
            </div>
        </div>
    </div>-->
    <?php
    $array_categoriastable = data_categorias();

    if ($array_categoriastable) {
        foreach ($array_categoriastable as $categoriatable) {

            $destino_bg = $categoriatable['destino'] === 'Barra' ? 'primary' : ($categoriatable['destino'] === 'Cocina' ? 'success' : 'warning');
            $destino_icon = $categoriatable['destino'] === 'Barra' ? 'bi-cup-hot' : ($categoriatable['destino'] === 'Cocina' ? 'bi-egg-fried' : 'bi-shuffle');

            $descripcion = empty(trim($categoriatable['descripcion'] ?? ''))
                ? 'Sin descripción.'
                : $categoriatable['descripcion'];

            echo '
                <div class="col-12 col-md-6 col-xl-4 cat-card">
                    <div class="card h-100 shadow border border-1 border-dark" style="border-radius:1rem;">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-start justify-content-between mb-2">
                                <h6 class="mb-0 fw-semibold text-truncate text-uppercase" title="' . $categoriatable['categoria'] . '">
                                    <i class="bi bi-folder2-open me-1"></i>' . $categoriatable['categoria'] . '
                                </h6>
                                <span class="badge text-bg-' . $destino_bg . '">
                                    <i class="bi ' . $destino_icon . ' me-1"></i>' . ucfirst($categoriatable['destino']) . '
                                </span>
                            </div>
                            <div class="text-muted small flex-grow-1" style="min-height:2.2em;">
                                <span class="opacity-60">' . $descripcion . '</span>
                            </div>
                            <div class="kpi-value mt-3" align="end">
                                <button type="button" class="btn-edit" onclick="edit_categoria(\'' . $categoriatable['id'] . '\')"><i class="bi bi-pen"></i></button>
                                <button type="button" class="btn-delete" onclick="delete_categoria(\'' . $categoriatable['id'] . '\')"><i class="bi bi-trash3"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            ';
        }
    }
    ?>
    <!--<div class="col-12 col-md-6 col-xl-4 cat-card">
        <div class="card h-100 shadow-sm border-0" style="border-radius:1rem;">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <h6 class="mb-0 fw-semibold text-truncate text-uppercase" title="EXTRAS">
                        <i class="bi bi-folder2-open me-1"></i>EXTRAS
                    </h6>
                    <span class="badge text-bg-warning">
                        <i class="bi bi-shuffle me-1"></i>Ambos
                    </span>
                </div>
                <div class="text-muted small flex-grow-1" style="min-height:2.2em;">
                    <span class="opacity-60">Mas opciones de acompañamiento para bebidas y comida.</span>
                </div>
            </div>
        </div>
    </div>-->
</div>


<div class="fade_modal_system fixed-top" id="modal_add_categoria">
    <div class="container_form_modal">
        <div class="head_form_modal">
            <span>Agregar Categoria</span>
            <i class="bi bi-x-lg icon_close_modal" id="close_modal_addcategoria"></i>
        </div>
        <form action="" method="post" class="body_form_modal" id="form_add_categoria">
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="name_addcategoria">Nombre de la Categoria</label>
                <input type="text" name="name_addcategoria" id="name_addcategoria" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="descripcion_addcategoria">Descripción</label>
                <textarea rows="3" name="descripcion_addcategoria" id="descripcion_addcategoria"></textarea>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="destino_addcategoria">Destino</label>
                <select name="destino_addcategoria" id="destino_addcategoria">
                    <option value="Barra">Barra</option>
                    <option value="Cocina">Cocina</option>
                </select>
            </div>
            <div class="p-2 ps-4 pe-4 pb-4 d-grid">
                <button type="submit" class="btn_execute_modal">Agregar Categoria</button>
            </div>
        </form>
    </div>
</div>


<div class="fade_modal_system fixed-top" id="modal_edit_categoria">
    <div class="container_form_modal">
        <div class="head_form_modal">
            <span>Editar Categoria</span>
            <i class="bi bi-x-lg icon_close_modal" id="close_modal_editcategoria"></i>
        </div>
        <form action="" method="post" class="body_form_modal" id="form_edit_categoria">
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="name_editcategoria">Nombre de la Categoria</label>
                <input type="text" name="name_editcategoria" id="name_editcategoria" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="descripcion_addcategoria">Descripción</label>
                <textarea rows="3" name="descripcion_editcategoria" id="descripcion_editcategoria"></textarea>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="destino_editcategoria">Destino</label>
                <select name="destino_editcategoria" id="destino_editcategoria">
                    <option value="Barra">Barra</option>
                    <option value="Cocina">Cocina</option>
                </select>
            </div>
            <div class="p-2 ps-4 pe-4 pb-4 d-grid">
                <input type="hidden" name="id_editcategoria" id="id_editcategoria" readonly>
                <button type="submit" class="btn_execute_modal">Actualizar Categoria</button>
            </div>
        </form>
    </div>
</div>

<script>
    $('#form_add_categoria').submit(function (event) {
        event.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: '../../controller/menu-categorias-add.php',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-categorias.php');
                } else {
                    show_alert(response.status, response.message);
                }
            }
        });
    });

    $('#form_edit_categoria').submit(function (event) {
        event.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: '../../controller/menu-categorias-edit.php',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-categorias.php');
                } else {
                    show_alert(response.status, response.message);
                }
            }
        });
    });
</script>