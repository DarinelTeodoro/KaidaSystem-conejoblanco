<?php
include('../model/conexion.php');

function consultar_productos()
{
    $conexion = new Conexion();
    $query_producto = $conexion->prepare("SELECT mp.*,
                                          mc.destino
                                   FROM menu_productos mp
                                   LEFT JOIN menu_categorias mc ON mp.id_categoria = mc.id
                                   WHERE mc.destino = 'Barra'
                                   ORDER BY mp.producto ASC");
    $query_producto->execute();
    $count_producto = $query_producto->rowCount();

    if ($count_producto > 0) {
        return $query_producto->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

function consultar_variantes($idprod)
{
    $conexion = new Conexion();
    $query_variante = $conexion->prepare("SELECT * FROM menu_variantes WHERE id_producto = :idprod ORDER BY incremento ASC");
    $query_variante->bindParam(":idprod", $idprod);
    $query_variante->execute();
    $count_variante = $query_variante->rowCount();

    if ($count_variante > 0) {
        return $query_variante->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

function consultar_extras()
{
    $conexion = new Conexion();
    $query_extra = $conexion->prepare("SELECT * FROM menu_extras WHERE destino = 'Barra' ORDER BY extra ASC");
    $query_extra->execute();
    $count_extra = $query_extra->rowCount();

    if ($count_extra > 0) {
        return $query_extra->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

function consultar_combos()
{
    $conexion = new Conexion();
    $query_combo = $conexion->prepare("SELECT * FROM menu_combos ORDER BY combo ASC");
    $query_combo->execute();
    $count_combo = $query_combo->rowCount();

    if ($count_combo > 0) {
        return $query_combo->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

// Cargar toda la información sin filtros
?>

<div class="d-grid mb-3">
    <input type="search" 
           id="search-varprod" 
           name="search-varprod" 
           placeholder="Buscar Producto/Variante/Extra" 
           autocomplete="off">
    <!--<small class="text-muted mt-1" id="search-info">Mostrando todos los items</small>-->
</div>

<div id="text-no-reults"></div>



<div class="bg-warning p-2 pt-1 pb-1 fw-bold mt-2" id="title-seccion-combos">Combos</div>
<div id="combos-container">
<?php
$datos_combo = consultar_combos();

if ($datos_combo) {
    foreach ($datos_combo as $combo) {
        $check_c = isset($combo['disponibilidad']) && $combo['disponibilidad'] == 0 ? 'checked' : '';
        $combo_nombre = htmlspecialchars($combo['combo']);
        $combo_id = $combo['id'];
        
        echo '<div class="combo-wrapper" data-searchable="'.strtolower($combo_nombre).'">';
        echo '<div class="combo-item" style="border: 1px solid rgb(0,0,0,.2); border-radius: 10px; margin: 7px 0px; padding: 10px 15px;box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px; background: #ffffff;">';
            echo '<div class="d-flex align-items-center" style="padding: 5px 10px;">
                '. $combo_nombre . '
                <div class="form-check form-switch ms-2">
                    <input class="form-check-input switch-item" type="checkbox" role="switch" 
                           data-tipo="combo" 
                           data-id="'.$combo_id.'" 
                           id="switchcombo_'.$combo_id.'" '.$check_c.'>
                </div>    
            </div>
        </div>';
        echo '</div>'; // Cierre extra-wrapper
    }
} else {
    echo '<div class="text-center text-muted p-3">No hay combos disponibles</div>';
}
?>
</div>



<div class="bg-danger p-2 pt-1 pb-1 fw-bold text-light" id="title-seccion-productos">Productos y Variantes</div>
<div id="productos-container">
<?php
$datos_producto = consultar_productos();

if ($datos_producto) {
    foreach ($datos_producto as $producto) {
        $check_p = isset($producto['disponibilidad']) && $producto['disponibilidad'] == 0 ? 'checked' : '';
        $producto_nombre = htmlspecialchars($producto['producto']);
        $producto_id = $producto['id'];
        
        echo '<div class="producto-wrapper" data-searchable="'.strtolower($producto_nombre).'">';
        echo '<div class="producto-item" data-producto-id="'.$producto_id.'" style="border: 1px solid rgb(0,0,0,.2); border-radius: 10px; margin: 7px 0px; padding: 10px 15px;box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;">';
        echo '<div class="d-flex align-items-center" style="padding: 5px 10px;">
            '. $producto_nombre . '
            <div class="form-check form-switch ms-2">
                <input class="form-check-input switch-item" type="checkbox" role="switch" 
                       data-tipo="producto" 
                       data-id="'.$producto_id.'" 
                       id="switchproducto_'.$producto_id.'" '.$check_p.'>
            </div>    
        </div>';
        
        $datos_variantes = consultar_variantes($producto_id);
        if ($datos_variantes) {
            foreach ($datos_variantes as $variante) {
                $check_v = isset($variante['disponibilidad']) && $variante['disponibilidad'] == 0 ? 'checked' : '';
                $variante_nombre = htmlspecialchars($variante['variante']);
                $variante_id = $variante['id'];
                
                echo '<div class="variante-item" data-searchable="'.strtolower($variante_nombre).'" style="padding: 5px 10px 5px 30px; border-top: 1px solid rgb(0,0,0,0.4); display: flex;">
                        <li>' . $variante_nombre . '</li>
                        <div class="form-check form-switch ms-2">
                            <input class="form-check-input switch-item" type="checkbox" role="switch" 
                                   data-tipo="variante" 
                                   data-id="'.$variante_id.'" 
                                   id="switchvariante_'.$variante_id.'" '.$check_v.'>
                        </div>  
                    </div>';
            }
        }
        echo '</div>'; // Cierre producto-item
        echo '</div>'; // Cierre producto-wrapper
    }
} else {
    echo '<div class="text-center text-muted p-3">No hay productos disponibles</div>';
}
?>
</div>



<div class="bg-success p-2 pt-1 pb-1 fw-bold text-light mt-2" id="title-seccion-extras">Extras</div>
<div id="extras-container">
<?php
$datos_extra = consultar_extras();

if ($datos_extra) {
    foreach ($datos_extra as $extra) {
        $check_e = isset($extra['disponibilidad']) && $extra['disponibilidad'] == 0 ? 'checked' : '';
        $extra_nombre = htmlspecialchars($extra['extra']);
        $extra_id = $extra['id'];
        
        echo '<div class="extra-wrapper" data-searchable="'.strtolower($extra_nombre).'">';
        echo '<div class="extra-item" style="border: 1px solid rgb(0,0,0,.2); border-radius: 10px; margin: 7px 0px; padding: 10px 15px;box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px; background: #ffffff;">';
            echo '<div class="d-flex align-items-center" style="padding: 5px 10px;">
                '. $extra_nombre . '
                <div class="form-check form-switch ms-2">
                    <input class="form-check-input switch-item" type="checkbox" role="switch" 
                           data-tipo="extra" 
                           data-id="'.$extra_id.'" 
                           id="switchextra_'.$extra_id.'" '.$check_e.'>
                </div>    
            </div>
        </div>';
        echo '</div>'; // Cierre extra-wrapper
    }
} else {
    echo '<div class="text-center text-muted p-3">No hay extras disponibles</div>';
}
?>
</div>