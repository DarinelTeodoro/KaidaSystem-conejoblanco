<?php
include('conexion.php');
date_default_timezone_set('America/Mexico_City');

//Consultar datos de servicio
function data_servicio()
{
    $conexion = new Conexion();
    $query_servicio = $conexion->prepare("SELECT * FROM servicio WHERE id = 1");
    $query_servicio->execute();
    $result_servicio = $query_servicio->rowCount();

    if ($result_servicio > 0) {
        return $query_servicio->fetch(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}


//Consultar datos de usuario
function consultar_usuario($user)
{
    $conexion = new Conexion();
    $query_user = $conexion->prepare("SELECT * FROM usuarios WHERE username = :user");
    $query_user->bindParam(':user', $user);
    $query_user->execute();
    $count_user = $query_user->rowCount();

    if ($count_user == 1) {
        return $query_user->fetch(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

//Consultar datos todos los usuarios
function all_users()
{
    $conexion = new Conexion();
    $query_usuarios = $conexion->prepare("SELECT * FROM usuarios ORDER BY rol ASC, name ASC");
    $query_usuarios->execute();
    $result_usuarios = $query_usuarios->rowCount();

    if ($result_usuarios > 0) {
        return $query_usuarios->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

//Consultar categorias
function data_categorias()
{
    $conexion = new Conexion();
    $query_categorias = $conexion->prepare("SELECT * FROM menu_categorias ORDER BY categoria ASC");
    $query_categorias->execute();
    $result_categorias = $query_categorias->rowCount();

    if ($result_categorias > 0) {
        return $query_categorias->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

//Consultar categorias
function data_categorias_aux()
{
    $conexion = new Conexion();
    $query_categorias = $conexion->prepare("SELECT * FROM menu_categorias ORDER BY destino ASC");
    $query_categorias->execute();
    $result_categorias = $query_categorias->rowCount();

    if ($result_categorias > 0) {
        return $query_categorias->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

//Consultar datos de categoria
function consultar_categoria($categoria)
{
    $conexion = new Conexion();
    $query_categoria = $conexion->prepare("SELECT * FROM menu_categorias WHERE id = :idcat");
    $query_categoria->bindParam(':idcat', $categoria);
    $query_categoria->execute();
    $count_categoria = $query_categoria->rowCount();

    if ($count_categoria == 1) {
        return $query_categoria->fetch(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

//Consultar combos
function data_combos()
{
    $conexion = new Conexion();
    $query_combo = $conexion->prepare("SELECT * FROM menu_combos ORDER BY combo ASC");
    $query_combo->execute();
    $result_combo = $query_combo->rowCount();

    if ($result_combo > 0) {
        return $query_combo->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

//Consultar datos de combo
function consultar_combo($combo)
{
    $conexion = new Conexion();
    $query_combo = $conexion->prepare("SELECT * FROM menu_combos WHERE id = :idcom");
    $query_combo->bindParam(':idcom', $combo);
    $query_combo->execute();
    $count_combo = $query_combo->rowCount();

    if ($count_combo == 1) {
        return $query_combo->fetch(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}


//Consultar combos
function data_grupos($id_combo)
{
    $conexion = new Conexion();
    $query_grupos = $conexion->prepare("SELECT * FROM menu_combos_grupos WHERE id_combo = :id_combo ORDER BY id ASC");
    $query_grupos->bindParam(':id_combo', $id_combo);
    $query_grupos->execute();
    $result_grupos = $query_grupos->rowCount();

    if ($result_grupos > 0) {
        return $query_grupos->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}


//Consultar productos de menu
function data_productos()
{
    $conexion = new Conexion();
    $query_productos = $conexion->prepare("SELECT * FROM menu_productos ORDER BY producto ASC");
    $query_productos->execute();
    $result_productos = $query_productos->rowCount();

    if ($result_productos > 0) {
        return $query_productos->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

//Consultar datos del producto
function consultar_producto($producto)
{
    $conexion = new Conexion();
    $query_producto = $conexion->prepare("SELECT * FROM menu_productos WHERE id = :idprod");
    $query_producto->bindParam(':idprod', $producto);
    $query_producto->execute();
    $count_producto = $query_producto->rowCount();

    if ($count_producto == 1) {
        return $query_producto->fetch(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

//Consultar extras
function data_extras()
{
    $conexion = new Conexion();
    $query_extras = $conexion->prepare("SELECT * FROM menu_extras ORDER BY extra ASC");
    $query_extras->execute();
    $result_extras = $query_extras->rowCount();

    if ($result_extras > 0) {
        return $query_extras->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

//Consultar datos de extra
function consultar_extra($extra)
{
    $conexion = new Conexion();
    $query_extra = $conexion->prepare("SELECT * FROM menu_extras WHERE id = :idextra");
    $query_extra->bindParam(':idextra', $extra);
    $query_extra->execute();
    $count_extra = $query_extra->rowCount();

    if ($count_extra == 1) {
        return $query_extra->fetch(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}


//Consultar variantes
function data_variantes()
{
    $conexion = new Conexion();
    $query_variantes = $conexion->prepare("SELECT * FROM menu_variantes ORDER BY id ASC");
    $query_variantes->execute();
    $result_variantes = $query_variantes->rowCount();

    if ($result_variantes > 0) {
        return $query_variantes->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

//Consultar variantesde producto
function variantes_producto($idprod)
{
    $conexion = new Conexion();

    $query = $conexion->prepare("
        SELECT mv.*, mc.destino
        FROM menu_variantes mv
        LEFT JOIN menu_productos mp ON mv.id_producto = mp.id
        LEFT JOIN menu_categorias mc ON mp.id_categoria = mc.id
        WHERE mv.id_producto = :idprod 
        ORDER BY mv.id ASC
    ");
    $query->bindParam(':idprod', $idprod, PDO::PARAM_INT);
    $query->execute();

    $data = $query->fetchAll(PDO::FETCH_ASSOC);

    return !empty($data) ? $data : false;
}

//Consultar datos de variante
function consultar_variante($variante)
{
    $conexion = new Conexion();
    $query_variante = $conexion->prepare("SELECT * FROM menu_variantes WHERE id = :idvariante");
    $query_variante->bindParam(':idvariante', $variante);
    $query_variante->execute();
    $count_variante = $query_variante->rowCount();

    if ($count_variante == 1) {
        return $query_variante->fetch(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}

//Consultar numero de mesas
function n_mesas()
{
    $keyword = 'mesas_max';
    $conexion = new Conexion();
    $query_nmesas = $conexion->prepare("SELECT * FROM mesas WHERE keyword = :user");
    $query_nmesas->bindParam(':user', $keyword);
    $query_nmesas->execute();
    $count_nmesas = $query_nmesas->rowCount();

    if ($count_nmesas == 1) {
        return $query_nmesas->fetch(PDO::FETCH_ASSOC);
    } else {
        return false;
    }
}


//estado de las comandas
/*function data_comandas($estado = null)
{
    $conexion = new Conexion();

    if ($estado && in_array($estado, ['pendiente', 'finalizado'])) {
        $query = $conexion->prepare("SELECT * FROM comandas WHERE estado = :estado ORDER BY id DESC");
        $query->bindParam(':estado', $estado);
    } else {
        $query = $conexion->prepare("SELECT * FROM comandas ORDER BY id DESC");
    }

    $query->execute();
    $data = $query->fetchAll(PDO::FETCH_ASSOC);

    return !empty($data) ? $data : false;
}*/

function data_comandas($estado = null, $mesero)
{
    $conexion = new Conexion();
    $fecha = date('Y-m-d');

    if ($estado && in_array($estado, ['pendiente', 'finalizado'])) {
        $query = $conexion->prepare("
            SELECT c.*, MAX(cb.created_at) as primera_creacion
            FROM comandas c
            LEFT JOIN comanda_batches cb ON c.id = cb.comanda_id
            WHERE c.estado = :estado AND c.user_id = :mesero AND DATE(c.created_at) = :date
            GROUP BY c.id
            ORDER BY primera_creacion DESC
        ");
        $query->bindParam(':estado', $estado);
        $query->bindParam(':mesero', $mesero);
        $query->bindParam(':date', $fecha);
    } else {
        $query = $conexion->prepare("
            SELECT c.*, MIN(cb.created_at) as primera_creacion
            FROM comandas c
            LEFT JOIN comanda_batches cb ON c.id = cb.comanda_id
            WHERE c.user_id = :mesero AND DATE(c.created_at) = :date
            GROUP BY c.id
            ORDER BY primera_creacion DESC
        ");
        $query->bindParam(':mesero', $mesero);
        $query->bindParam(':date', $fecha);
    }

    $query->execute();
    $data = $query->fetchAll(PDO::FETCH_ASSOC);

    return !empty($data) ? $data : false;
}

//detalle de la comanda
function detalle_comanda($comanda_id)
{
    $conexion = new Conexion();

    // 1) Datos principales
    $stmt = $conexion->prepare("SELECT * FROM comandas WHERE id = :id");
    $stmt->bindParam(':id', $comanda_id, PDO::PARAM_INT);
    $stmt->execute();
    $comanda = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$comanda)
        return false;

    // 2) Items
    $stmtItems = $conexion->prepare("SELECT * FROM comanda_items WHERE comanda_id = :id");
    $stmtItems->bindParam(':id', $comanda_id, PDO::PARAM_INT);
    $stmtItems->execute();
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {

        // 3) Componentes
        $stmtComp = $conexion->prepare("
            SELECT * FROM comanda_item_componentes
            WHERE item_id = :item_id
        ");
        $stmtComp->bindParam(':item_id', $item['id'], PDO::PARAM_INT);
        $stmtComp->execute();
        $componentes = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

        $item['componentes'] = $componentes;
    }

    $comanda['items'] = $items;

    return $comanda;
}



function data_comandas_caja($estado = null)
{
    $conexion = new Conexion();
    $fecha = date('Y-m-d');

    if ($estado && in_array($estado, ['pendiente', 'finalizado'])) {
        $query = $conexion->prepare("
            SELECT c.*, MAX(cb.created_at) as primera_creacion
            FROM comandas c
            LEFT JOIN comanda_batches cb ON c.id = cb.comanda_id
            WHERE c.estado = :estado
            AND (c.cocina != 1 AND c.barra != 1)
            AND DATE(c.created_at) = :fecha
            GROUP BY c.id
            ORDER BY primera_creacion DESC
        ");
        $query->bindParam(':estado', $estado);
        $query->bindParam('fecha', $fecha);
    } else {
        $query = $conexion->prepare("
            SELECT c.*, MIN(cb.created_at) as primera_creacion
            FROM comandas c
            LEFT JOIN comanda_batches cb ON c.id = cb.comanda_id
            WHERE DATE(c.created_at) = :fecha
            GROUP BY c.id
            ORDER BY primera_creacion DESC
        ");
        $query->bindParam('fecha', $fecha);
    }

    $query->execute();
    $data = $query->fetchAll(PDO::FETCH_ASSOC);

    return !empty($data) ? $data : false;
}
?>