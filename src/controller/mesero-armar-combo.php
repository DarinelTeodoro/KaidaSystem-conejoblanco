<?php
include('../model/querys.php');

$id_combo = $_POST['id'];
$data_grupos = data_grupos($id_combo);

$combo = consultar_combo($id_combo);
$comboNombre = $combo ? $combo['combo'] : 'Combo';
$comboPrecio = $combo ? $combo['precio'] : 0;
?>

<input type="hidden" id="combo_id_actual" value="<?= (int)$id_combo ?>">
<input type="hidden" id="combo_nombre_actual" value="<?= htmlspecialchars($comboNombre) ?>">
<input type="hidden" id="combo_precio_actual" value="<?= htmlspecialchars($comboPrecio) ?>">

<?php
if ($data_grupos) {

    foreach ($data_grupos as $grupo) {

        $grupo_id   = $grupo['id'];
        $grupo_tipo = $grupo['tipo']; // 'multiple' o 'unico'
        $grupo_nombre = $grupo['grupo'];
        $instruccion = $grupo['tipo'] === 'multiple' ? 'Elige las opciones que deseas.' : ($grupo['tipo'] === 'unico' ? 'Elige una de las siguientes opciones.' : 'Productos incluidos');

        $productos_guardados = $grupo['productos'];
        $productos = json_decode($productos_guardados, true) ?? [];

        if (!empty($productos)) {

            echo "<div class='grupo_opciones mt-3 mb-3' data-grupo-id='{$grupo_id}' data-tipo='{$grupo_tipo}'>";
            echo "<div class='division_categorias_secciones fw-bold'>{$grupo_nombre}</div>";
            echo "<div class='pt-1 pb-1'><span class='text-muted' style='font-size: 0.8rem;'>{$instruccion}</span></div>";

            foreach ($productos as $producto_id) {

                $prod = consultar_producto($producto_id);

                if (!$prod) continue;

                if ($grupo_tipo == 'predeterminado') {
                    echo "
                    <div class='pt-1 pb-1 d-grid align-items-center' style='grid-template-columns: auto 1fr;'>
                        <i class='bi bi-check-lg me-1' style='color: #0a6f1e; font-size: 1.3rem;'></i><input value='{$prod['producto']}' data-idproducto='{$prod['id']}' readonly style='border: none; cursor: default; background: #ffffff;'>
                    </div>
                    ";
                } else {
                    $input_type = ($grupo_tipo === 'multiple') ? 'checkbox' : 'radio';

                    // IMPORTANTE:
                    // radio → mismo name por grupo
                    // checkbox → name como array

                    $input_name = ($grupo_tipo === 'multiple')
                        ? "grupo_{$grupo_id}[]"
                        : "grupo_{$grupo_id}";

                    echo "
                        <div class='btn-group d-grid' role='group'>
                            <input 
                                class='btn-check'
                                type='{$input_type}'
                                name='{$input_name}'
                                value='{$producto_id}'
                                id='{$grupo_id}prod_{$producto_id}'
                            >
                            <label class='btn btn-outline-primary label_check' for='{$grupo_id}prod_{$producto_id}' style='border-radius: 0px; margin-top: 6px;'>
                                {$prod['producto']}
                            </label>
                        </div>
                    ";
                }
            }

            echo "</div>";
        }
    }
    echo '<div class="d-grid mb-2">
        <label for="notas_combo">Notas</label>
        <textarea name="notas_combo" id="notas_combo" rows="3"></textarea>
    </div>';
    echo "<div class='d-grid'><button type='button' class='btn-send-comanda' onclick='addcombto_pedido()'>Agregar al Pedido</button></div>";
} else {
?>
    <div class="d-flex align-items-center justify-content-center ps-3 pe-3" style="width: 100%; height: 100%;">
        <span class="fw-bold">Combo no Disponible</span>
    </div>
<?php
}
?>
