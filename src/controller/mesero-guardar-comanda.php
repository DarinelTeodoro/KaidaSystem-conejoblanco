<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include('../model/conexion.php');

$fecha = date('Y-m-d H:i:s');
$dia = date('Y-m-d');

if (!isset($_POST['payload'])) {
    http_response_code(400);
    exit('Missing payload');
}

$data = json_decode($_POST['payload'], true);
if (!$data || !isset($data['items'])) {
    http_response_code(400);
    exit('Invalid payload');
}

if (empty($data['items']) || count($data['items']) === 0) {
    http_response_code(400);
    exit('La comanda no puede ir vacía.');
}

$tipo = $data['tipo'] ?? 'mesa';
$mesa = $data['mesa'] ?? null;
$cliente = $data['cliente'] ?? null;

$user_id = $_SESSION['data-useractive'] ?? null;

$cnx = new Conexion();

function consultar_disponibilidad($cnx, $mesa, $dia)
{
    $ocupado = $cnx->prepare("SELECT COUNT(id) as ocupado FROM comandas WHERE mesa = :mesa AND DATE(created_at) = :dia AND estado = 'pendiente'");
    $ocupado->execute([':mesa' => $mesa, ':dia' => $dia]);
    return $ocupado->fetch(PDO::FETCH_ASSOC);
}

// Función para obtener el destino de un producto (cocina/barra)
function obtenerDestinoProducto($cnx, $producto_id)
{
    $query = $cnx->prepare("
        SELECT c.destino 
        FROM menu_productos p
        LEFT JOIN menu_categorias c ON p.id_categoria = c.id
        WHERE p.id = :id
    ");
    $query->execute([':id' => $producto_id]);
    return $query->fetchColumn();
}

// Función para obtener el destino de un extra
function obtenerDestinoExtra($cnx, $extra_id)
{
    $query = $cnx->prepare("SELECT destino FROM menu_extras WHERE id = :id");
    $query->execute([':id' => $extra_id]);
    return $query->fetchColumn();
}

try {
    $cnx->beginTransaction();

    // Inicializar flags para cocina y barra
    $cocina = 0;
    $barra = 0;


    $result = consultar_disponibilidad($cnx, $mesa, $dia);

    if ($result['ocupado'] > 0) {
        http_response_code(400);
        exit('La mesa ya tiene una comanda pendiente');
    }

    // 1) comanda
    $stmt = $cnx->prepare("INSERT INTO comandas (user_id, tipo, mesa, cliente, created_at) VALUES (:user_id, :tipo, :mesa, :cliente, :fecha)");
    $stmt->execute([
        ':user_id' => $user_id,
        ':tipo' => $tipo,
        ':mesa' => $mesa,
        ':cliente' => $cliente,
        ':fecha' => $fecha
    ]);
    $comanda_id = (int) $cnx->lastInsertId();

    $stmtBatch = $cnx->prepare("
    INSERT INTO comanda_batches (comanda_id, seq, created_at)
    VALUES (:comanda_id, 1, :fecha)
    ");
    $stmtBatch->execute([':comanda_id' => $comanda_id, ':fecha' => $fecha]);
    $batch_id = (int) $cnx->lastInsertId();

    // 2) items
    $stmtItem = $cnx->prepare("
    INSERT INTO comanda_items
        (comanda_id, batch_id, uid, tipo, combo_id, producto_id, variante_id, nombre, qty, precio, nota, created_at, destino)
    VALUES
        (:comanda_id, :batch_id, :uid, :tipo, :combo_id, :producto_id, :variante_id, :nombre, :qty, :precio, :nota, :fecha, :destino)
    ");

    $stmtComp = $cnx->prepare("
    INSERT INTO comanda_item_componentes (item_id, kind, grupo_id, grupo_nombre, producto_id, extra_id, variante_id, nombre, qty, precio, destino)
    VALUES (:item_id, :kind, :grupo_id, :grupo_nombre, :producto_id, :extra_id, :variante_id, :nombre, :qty, :precio, :destino)
  ");

    foreach ($data['items'] as $it) {
        $uid = $it['uid'] ?? '';
        $tipoItem = $it['type'] ?? 'product';
        $qty = (int) ($it['qty'] ?? 1);
        $note = $it['note'] ?? null;

        $combo_id = null;
        $producto_id = null;
        $variante_id = null;
        $nombre = '';
        $precio = 0;

        if ($tipoItem === 'combo') {
            $combo_id = $it['base']['combo_id'] ?? null;
            $nombre = $it['base']['nombre'] ?? 'Combo';
            $precio = (float) ($it['base']['precio'] ?? 0);
            $destino_db = 'Ambos';

            // Para combos, revisamos sus componentes
            if (!empty($it['components']) && is_array($it['components'])) {
                foreach ($it['components'] as $c) {
                    $prod_id = $c['producto_id'] ?? ($c['product_id'] ?? null);
                    if ($prod_id) {
                        $destino = obtenerDestinoProducto($cnx, $prod_id);
                        if ($destino === 'Cocina')
                            $cocina = 1;
                        if ($destino === 'Barra')
                            $barra = 1;
                    }
                }
            }
        } else {
            $producto_id = $it['base']['product_id'] ?? null;
            $variante_id = $it['base']['variante_id'] ?? null;
            $nombre = $it['base']['nombre'] ?? 'Producto';
            $destino_db = obtenerDestinoProducto($cnx, $producto_id);

            $precio = (float) ($it['base']['precio'] ?? 0);
            if (!empty($it['base']['variante_nombre'])) {
                $nombre .= " (" . $it['base']['variante_nombre'] . ")";
            }

            // Verificar destino del producto principal
            if ($producto_id) {
                $destino = obtenerDestinoProducto($cnx, $producto_id);
                if ($destino === 'Cocina')
                    $cocina = 1;
                if ($destino === 'Barra')
                    $barra = 1;
            }
        }

        $stmtItem->execute([
            ':comanda_id' => $comanda_id,
            ':batch_id' => $batch_id,
            ':uid' => $uid,
            ':tipo' => $tipoItem,
            ':combo_id' => $combo_id,
            ':producto_id' => $producto_id,
            ':variante_id' => $variante_id,
            ':nombre' => $nombre,
            ':qty' => $qty,
            ':precio' => $precio,
            ':nota' => $note,
            ':fecha' => $fecha,
            ':destino' => $destino_db,
        ]);

        $item_id = (int) $cnx->lastInsertId();

        // components (para productos normales con variantes)
        if (!empty($it['components']) && is_array($it['components'])) {
            foreach ($it['components'] as $c) {
                // Si es una variante, verificar su destino
                if (isset($c['kind']) && $c['kind'] === 'variante' && isset($c['product_id'])) {
                    $destino = obtenerDestinoProducto($cnx, $c['product_id']);
                    if ($destino === 'Cocina')
                        $cocina = 1;
                    if ($destino === 'Barra')
                        $barra = 1;
                }

                $stmtComp->execute([
                    ':item_id' => $item_id,
                    ':kind' => $c['kind'] ?? 'seleccion',
                    ':grupo_id' => $c['grupo_id'] ?? null,
                    ':grupo_nombre' => $c['grupo_nombre'] ?? null,
                    ':producto_id' => $c['producto_id'] ?? ($c['product_id'] ?? null),
                    ':extra_id' => null,
                    ':variante_id' => $c['variante_id'] ?? null,
                    ':nombre' => $c['producto_nombre'] ?? ($c['nombre'] ?? 'Detalle'),
                    ':qty' => (int) ($c['qty'] ?? 1),
                    ':precio' => (float) ($c['precio'] ?? 0),
                    ':destino' => obtenerDestinoProducto($cnx, $c['producto_id']),
                ]);
            }
        }

        // extras
        if (!empty($it['extras']) && is_array($it['extras'])) {
            foreach ($it['extras'] as $ex) {
                $extra_id = $ex['extra_id'] ?? null;

                // Verificar destino del extra
                if ($extra_id) {
                    $destino = obtenerDestinoExtra($cnx, $extra_id);
                    if ($destino === 'Cocina')
                        $cocina = 1;
                    if ($destino === 'Barra')
                        $barra = 1;
                }

                $stmtComp->execute([
                    ':item_id' => $item_id,
                    ':kind' => 'extra',
                    ':grupo_id' => null,
                    ':grupo_nombre' => null,
                    ':producto_id' => null,
                    ':extra_id' => $extra_id,
                    ':variante_id' => null,
                    ':nombre' => $ex['nombre'] ?? 'Extra',
                    ':qty' => (int) ($ex['qty'] ?? 1),
                    ':precio' => (float) ($ex['precio'] ?? 0),
                    ':destino' => obtenerDestinoExtra($cnx, $extra_id),
                ]);
            }
        }
    }

    // Actualizar la comanda con los flags de cocina y barra
    $stmtUpdate = $cnx->prepare("
        UPDATE comandas 
        SET cocina = :cocina, barra = :barra 
        WHERE id = :id
    ");
    $stmtUpdate->execute([
        ':cocina' => $cocina,
        ':barra' => $barra,
        ':id' => $comanda_id
    ]);

    $cnx->commit();
    echo 'OK';
} catch (Exception $e) {
    $cnx->rollBack();
    http_response_code(500);
    echo $e->getMessage();
}
?>