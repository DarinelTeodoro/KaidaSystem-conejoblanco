<?php
include('../model/querys.php');
?>

<div class="container-fliud d-flex align-items-center justify-content-between container_title_seccion">
    <div><strong>Extras</strong></div>
    <button type="button" class="btn-add" id="open_modal_addextra">Agregar Extra</button>
</div>

<!-- Grid de tarjetas -->
<div class="pt-3 pb-3">
    <div class="table-responsive">
        <table class="table table-success table-hover align-middle">
            <thead>
                <tr>
                    <th scope="col">Extra</th>
                    <th scope="col" class="text-center">Precio</th>
                    <th scope="col" class="text-center">Destino</th>
                    <th scope="col" class="text-center">Acción</th>
                </tr>
            </thead>
            <?php
            $array_extrastable = data_extras();

            if ($array_extrastable) {
                foreach ($array_extrastable as $extratable) {
                    $destino_icon = $extratable['destino'] === 'Barra' ? 'bi-cup-hot' : 'bi-egg-fried';
                    $destino_bg = $extratable['destino'] === 'Barra' ? 'bg-primary' : 'bg-success';
                    echo '
                <tr class="table-light">
                    <td><i class="bi bi-folder2-open me-1"></i>' . $extratable['extra'] . '</td>
                    <td class="text-center"><span class="fw-bold text-success">$' . number_format($extratable['precio'], 2) . '</span></td>
                    <td class="text-center"><span class="p-1 rounded text-light fw-bold text-truncate ' . $destino_bg . '" style="font-size: 0.8rem;"><i class="bi ' . $destino_icon . ' me-1"></i>' . $extratable['destino'] . '</span></td>
                    <td class="text-center">
                        <button type="button" class="btn-edit" onclick="edit_extra(\'' . $extratable['id'] . '\')"><i class="bi bi-pen"></i></button>
                        <button type="button" class="btn-delete" onclick="delete_extra(\'' . $extratable['id'] . '\')"><i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
            ';
                }
            }
            ?>
        </table>
    </div>
</div>

<div class="fade_modal_system fixed-top" id="modal_add_extra">
    <div class="container_form_modal">
        <div class="head_form_modal">
            <span>Agregar Extra</span>
            <i class="bi bi-x-lg icon_close_modal" id="close_modal_addextra"></i>
        </div>
        <form action="" method="post" class="body_form_modal" id="form_add_extra">
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="name_addextra">Extra</label>
                <input type="text" name="name_addextra" id="name_addextra" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="precio_addextra">Precio del extra</label>
                <input type="number" name="precio_addextra" id="precio_addextra" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="destino_addextra">Destino</label>
                <select name="destino_addextra" id="destino_addextra">
                    <option value="Barra">Barra</option>
                    <option value="Cocina">Cocina</option>
                </select>
            </div>
            <div class="p-2 ps-4 pe-4 pb-4 d-grid">
                <button type="submit" class="btn_execute_modal">Agregar Extra</button>
            </div>
        </form>
    </div>
</div>


<div class="fade_modal_system fixed-top" id="modal_edit_extra">
    <div class="container_form_modal">
        <div class="head_form_modal">
            <span>Agregar Extra</span>
            <i class="bi bi-x-lg icon_close_modal" id="close_modal_editextra"></i>
        </div>
        <form action="" method="post" class="body_form_modal" id="form_edit_extra">
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="name_editextra">Extra</label>
                <input type="text" name="name_editextra" id="name_editextra" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="precio_editextra">Precio del extra</label>
                <input type="number" name="precio_editextra" id="precio_editextra" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="destino_editextra">Destino</label>
                <select name="destino_editextra" id="destino_editextra">
                    <option value="Barra">Barra</option>
                    <option value="Cocina">Cocina</option>
                </select>
            </div>
            <div class="p-2 ps-4 pe-4 pb-4 d-grid">
                <input type="hidden" name="id_editextra" id="id_editextra" readonly>
                <button type="submit" class="btn_execute_modal">Actualizar Extra</button>
            </div>
        </form>
    </div>
</div>

<script>
    $('#form_add_extra').submit(function (event) {
        event.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: '../../controller/menu-extras-add.php',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-extras.php');
                } else {
                    show_alert(response.status, response.message);
                }
            }
        });
    });


    $('#form_edit_extra').submit(function (event) {
        event.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: '../../controller/menu-extras-edit.php',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-extras.php');
                } else {
                    show_alert(response.status, response.message);
                }
            }
        });
    });
</script>