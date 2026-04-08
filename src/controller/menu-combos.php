<?php
include('../model/querys.php');
?>

<style>
    #text_nota_seccion {
        font-size: 0.8rem;
    }

    .cont_btn_checks {
        width: 100%;
        display: grid;
        grid-template-columns: 25% 25% 25% 25%;
    }

    .label_check {
        width: 100%;
        height: 80px;
        border-radius: 0px;

        display: grid;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        grid-template-columns: 80px 1fr;
    }

    .cont_products_combo {
        margin: 15px 0px 0px;
        padding: 10px 0px;
        background: rgb(243, 243, 237);
        border-radius: 10px;
    }

    .division_categorias_secciones {
        height: 50px;
        background: #f3f3ed;
        padding: 0px 10px;

        display: flex;
        align-items: center;
    }

    @media (width < 950px) {
        .cont_btn_checks {
            grid-template-columns: 33% 34% 33%;
        }
    }

    @media (width < 750px) {
        .cont_btn_checks {
            grid-template-columns: 50% 50%;
        }
    }

    @media (width < 540px) {
        .cont_btn_checks {
            grid-template-columns: 100%;
        }
    }
</style>


<div class="container-fliud d-flex align-items-center justify-content-between container_title_seccion">
    <div><strong>Combos</strong></div>
    <button type="button" class="btn-add" id="open_modal_addcombo">Agregar Combo</button>
</div>


<!-- Grid de tarjetas -->
<div class="row g-3 pt-3" id="catsContainer">
    <?php
    $array_combostable = data_combos();

    if ($array_combostable) {
        foreach ($array_combostable as $combotable) {

            $descripcion = empty(trim($combotable['descripcion'] ?? ''))
                ? 'Sin descripción.'
                : $combotable['descripcion'];

            echo '
                <div class="col-12 col-md-6 col-xl-4 cat-card">
                    <div class="card h-100 shadow-sm border-0" style="border-radius:1rem;">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-start justify-content-between mb-2">
                                <h6 class="mb-0 fw-semibold text-truncate text-uppercase" title="' . $combotable['combo'] . '">
                                    <i class="bi bi-folder2-open me-1"></i>' . $combotable['combo'] . '
                                </h6>
                                <span class="text-success fw-bold">$' . number_format($combotable['precio'], 2) . '</span>
                            </div>
                            <div class="text-muted small flex-grow-1" style="min-height:2.2em;">
                                <span class="opacity-60">' . $descripcion . '</span>
                            </div>
                            <div class="kpi-value mt-3" align="end">
                                <button type="button" class="btn-config" onclick="config_combo(\'' . $combotable['id'] . '\')"><i class="bi bi-gear"></i></button>
                                <button type="button" class="btn-edit" onclick="edit_combo(\'' . $combotable['id'] . '\')"><i class="bi bi-pen"></i></button>
                                <button type="button" class="btn-delete" onclick="delete_combo(\'' . $combotable['id'] . '\')"><i class="bi bi-trash3"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            ';
        }
    }
    ?>
</div>


<div class="fade_modal_system fixed-top" id="modal_add_combo">
    <div class="container_form_modal">
        <div class="head_form_modal">
            <span>Agregar Combo</span>
            <i class="bi bi-x-lg icon_close_modal" id="close_modal_addcombo"></i>
        </div>
        <form action="" method="post" class="body_form_modal" id="form_add_combo">
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="name_addcombo">Nombre del combo</label>
                <input type="text" name="name_addcombo" id="name_addcombo" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="descripcion_addcombo">Descripción</label>
                <textarea rows="3" name="descripcion_addcombo" id="descripcion_addcombo"></textarea>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="precio_addcombo">Precio del combo</label>
                <input type="number" name="precio_addcombo" id="precio_addcombo" required>
            </div>
            <div class="p-2 ps-4 pe-4 pb-4 d-grid">
                <button type="submit" class="btn_execute_modal">Agregar Combo</button>
            </div>
        </form>
    </div>
</div>


<div class="fade_modal_system fixed-top" id="modal_edit_combo">
    <div class="container_form_modal">
        <div class="head_form_modal">
            <span>Agregar Combo</span>
            <i class="bi bi-x-lg icon_close_modal" id="close_modal_editcombo"></i>
        </div>
        <form action="" method="post" class="body_form_modal" id="form_edit_combo">
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="name_editcombo">Nombre del combo</label>
                <input type="text" name="name_editcombo" id="name_editcombo" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="descripcion_editcombo">Descripción</label>
                <textarea rows="3" name="descripcion_editcombo" id="descripcion_editcombo"></textarea>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="precio_editcombo">Precio del combo</label>
                <input type="number" name="precio_editcombo" id="precio_editcombo" required>
            </div>
            <div class="p-2 ps-4 pe-4 pb-4 d-grid">
                <input type="hidden" name="id_editcombo" id="id_editcombo" readonly>
                <button type="submit" class="btn_execute_modal">Actualizar Combo</button>
            </div>
        </form>
    </div>
</div>



<div class="fade_modal_system fixed-top" id="modal_config_combo">
    <div class="container_form_modal">
        <div class="head_form_modal">
            <span>Configurar Combo</span>
            <i class="bi bi-x-lg icon_close_modal" id="close_modal_configcombo"></i>
        </div>
        <div class="body_form_modal ps-4 pe-4 p-2">
            <div class="d-flex align-items-center justify-content-between">
                <label>Personalización</label>
                <div>
                    <button type="button" class="btn-edit p-1 pe-4 ps-4" id="btn_seccion_combo">Crear Sección</button>
                </div>
            </div>
            <div class="line mt-2"></div>

            <div class="d-flex align-items-center justify-content-center flex-column pt-3 lh-sm">
                <div><label>Secciones y Productos de:</label></div>
                <div><strong id="offcanvas_title_combo"></strong></div>
            </div>
            <div class="cont_products_combo mb-2 p-3" id="cont_products_combo">
                <div class="text-center">Cargando Productos...</div>
            </div>
        </div>
    </div>
</div>



<div class="offcanvas offcanvas-top" data-bs-backdrop="static" tabindex="-1" id="offcanvas-productos"
    aria-labelledby="offcanvasTopLabel">
    <div class="d-flex align-items-center justify-content-between p-3">
        <h5 class="offcanvas-title" id="offcanvasProductosLabel">Seleccionar Productos</h5>
        <i class="bi bi-x-lg" data-bs-dismiss="offcanvas" aria-label="Close"></i>
    </div>
    <form method="post" action="" class="offcanvas-body p-0 m-0" id="form_config_combo">
        <div class="d-grid p-2 ps-4 pe-4">
            <label for="offcanvas-tiposeccion">Tipo de Seccion</label>
            <select name="offcanvas-tiposeccion" class="mb-2" id="offcanvas-tiposeccion">
                <option value="multiple">Selección Multiple</option>
                <option value="unico">Una sola Selección</option>
                <option value="predeterminado">Siempre Incluidos</option>
            </select>
            <i class="text-muted lh-1" id="text_nota_seccion" align="justify"><b>Sección Multiple: </b>Se pueden
                seleccionar mas de un producto de esta sección.</i>
        </div>
        <div class="d-grid p-2 ps-4 pe-4 mb-4">
            <label for="offcanvas-nameseccion">Nombre de la Seccion</label>
            <input type="text" name="offcanvas-nameseccion" id="offcanvas-nameseccion" required>
        </div>
        <div class="d-flex align-items-center justify-content-end pt-2 ps-4 pe-4 pb-0">
            <input type="search" name="offcanvas-searchproductos" id="offcanvas-searchproductos"
                placeholder="Buscar Producto">
        </div>
        <div>
            <input type="hidden" name="id_offcanvascombo" id="id_offcanvascombo">

            <div class="p-3 pe-4 ps-4" id="cont_list_combo">
                <!--Lista de productos-->
            </div>

            <div class="d-grid p-2 ps-4 pe-4 mb-4 pb-0">
                <button type="submit" class="btn_execute_modal">Agregar Sección</button>
            </div>
        </div>
    </form>
</div>

<script>
    $('#form_add_combo').submit(function (event) {
        event.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: '../../controller/menu-combos-add.php',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-combos.php');
                } else {
                    show_alert(response.status, response.message);
                }
            }
        });
    });


    $('#form_edit_combo').submit(function (event) {
        event.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: '../../controller/menu-combos-edit.php',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-combos.php');
                } else {
                    show_alert(response.status, response.message);
                }
            }
        });
    });


    $('#form_config_combo').submit(function (event) {
        event.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: '../../controller/menu-combos-groups.php',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    document.getElementById('body-page').style.overflow = 'auto';
                    document.getElementById('body-page').style.paddingRight = '20px';
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-combos.php');
                } else {
                    select_products.toggle();
                    show_alert(response.status, response.message);
                }
            }
        });
    });

    (function () {
        const input = document.getElementById('offcanvas-searchproductos');
        const cont = document.getElementById('cont_list_combo');

        if (!input || !cont) return;

        function normalize(str) {
            return (str || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '') // quita tildes
                .trim();
        }

        function filterProducts() {
            const q = normalize(input.value);

            // Cada bloque es: .division_categorias_secciones + .btn-group.cont_btn_checks
            const sections = cont.querySelectorAll('.division_categorias_secciones');

            sections.forEach(section => {
                const group = section.nextElementSibling;

                // Si por alguna razón no está el grupo al lado, no rompas
                if (!group || !group.classList.contains('cont_btn_checks')) return;

                const items = group.querySelectorAll('.p-1');
                let anyVisibleInCategory = false;

                items.forEach(item => {
                    const checkbox = item.querySelector('input[type="checkbox"]');
                    const label = item.querySelector('label');

                    // Texto del producto
                    const name = normalize(label ? label.textContent : '');

                    const isChecked = checkbox ? checkbox.checked : false;

                    // Regla:
                    // - Si q está vacío: mostrar todo
                    // - Si q tiene texto: mostrar si coincide O si está checked
                    const match = (q === '') ? true : (name.includes(q) || isChecked);

                    item.style.display = match ? '' : 'none';

                    if (match) anyVisibleInCategory = true;
                });

                // La categoría se muestra solo si hay algún producto visible
                section.style.display = anyVisibleInCategory ? '' : 'none';
                group.style.display = anyVisibleInCategory ? '' : 'none';
            });
        }

        // Filtrar al escribir
        input.addEventListener('input', filterProducts);

        // Importante: si el usuario marca/desmarca y hay búsqueda activa,
        // re-ejecuta filtro para que “checked se quede fijo”
        cont.addEventListener('change', (e) => {
            if (e.target && e.target.matches('input[type="checkbox"]')) {
                filterProducts();
            }
        });

        // Estado inicial
        filterProducts();
    })();
</script>