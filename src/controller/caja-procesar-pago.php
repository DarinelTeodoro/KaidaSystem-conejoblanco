<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include('../model/conexion.php');
$fecha = date('Y-m-d H:i:s');

if (!isset($_POST['pago_data'])) {
    http_response_code(400);
    exit('Datos de pago no recibidos');
}

$pago_data = json_decode($_POST['pago_data'], true);

if (!$pago_data || !isset($pago_data['comanda_id']) || !isset($pago_data['tipo_pago'])) {
    http_response_code(400);
    exit('Datos de pago inválidos');
}

$user_id = $_SESSION['data-useractive'] ?? 0;
$comanda_id = (int) $pago_data['comanda_id'];
$tipo_pago = $pago_data['tipo_pago']; // 'simple', 'mixto', 'cuentas'
$total_comanda = (float) $pago_data['total_comanda'];

$cnx = new Conexion();

function insert_venta($cnx, $idcomanda, $idpago, $cuenta, $subtotal, $metodo, $tipopropina, $valorpropina, $propina, $total, $recibido, $cambio)
{
    $fecha = date('Y-m-d H:i:s');
    
    $ventas = $cnx->prepare("
            INSERT INTO ventas 
            (id_comanda, id_pago, n_cuenta, total_comanda, tipo_pago, tipo_propina, valor_propina,calc_propina, total, recibido, cambio, fecha)
            VALUES (:comanda, :pago, :cuenta, :subtotal, :metodo, :tipopropina, :valorpropina, :propina, :total, :recibido, :cambio, :fecha)
        ");
    $ventas->execute([
        ':comanda' => $idcomanda ?? 0,
        ':pago' => $idpago ?? 0,
        ':cuenta' => $cuenta ?? 0,
        ':subtotal' => $subtotal ?? 0,
        ':metodo' => $metodo ?? null,
        ':tipopropina' => $tipopropina ?? null,
        ':valorpropina' => $valorpropina ?? 0,
        ':propina' => $propina ?? 0,
        ':total' => $total ?? 0,
        ':recibido' => $recibido ?? 0,
        ':cambio' => $cambio ?? 0,
        ':fecha' => $fecha ?? null
    ]);
}

try {
    $cnx->beginTransaction();

    // 1. Insertar pago principal
    $stmtPago = $cnx->prepare("
        INSERT INTO purchases (comanda_id, user_id, total_comanda, fecha_pago, estado)
        VALUES (:comanda_id, :user_id, :total, :fecha, 'completado')
    ");
    $stmtPago->execute([
        ':comanda_id' => $comanda_id,
        ':user_id' => $user_id,
        ':total' => $total_comanda,
        ':fecha' => $fecha
    ]);
    $pago_id = (int) $cnx->lastInsertId();

    // 2. Procesar según tipo de pago
    switch ($tipo_pago) {
        case 'simple':
            guardarPagoSimple($cnx, $pago_id, $pago_data, $comanda_id);
            break;
        case 'mixto':
            guardarPagoMixto($cnx, $pago_id, $pago_data, $comanda_id);
            break;
        case 'cuentas':
            guardarCuentasSeparadas($cnx, $pago_id, $pago_data, $comanda_id);
            break;
    }

    // 3. Actualizar estado de la comanda
    $stmtUpdate = $cnx->prepare("UPDATE comandas SET estado = 'finalizado' WHERE id = :id");
    $stmtUpdate->execute([':id' => $comanda_id]);

    // 4. Registrar en historial
    $stmtHistorial = $cnx->prepare("
        INSERT INTO pago_historial (pago_id, accion, user_id, fecha, datos_previos)
        VALUES (:pago_id, 'creado', :user_id, :fecha, NULL)
    ");
    $stmtHistorial->execute([
        ':pago_id' => $pago_id,
        ':user_id' => $user_id,
        ':fecha' => $fecha
    ]);

    $cnx->commit();
    echo json_encode(['success' => true, 'pago_id' => $pago_id]);

} catch (Exception $e) {
    $cnx->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Funciones auxiliares
function guardarPagoSimple($cnx, $pago_id, $data, $comanda_id)
{
    $metodo = $data['metodo'];

    if ($metodo === 'efectivo') {
        $stmt = $cnx->prepare("
            INSERT INTO pago_detalles 
            (pago_id, metodo_pago, monto, propina_tipo, propina_valor, propina_calculada, monto_recibido, cambio)
            VALUES (:pago_id, 'efectivo', :monto, :propina_tipo, :propina_valor, :propina_calculada, :recibido, :cambio)
        ");
        $stmt->execute([
            ':pago_id' => $pago_id,
            ':monto' => $data['monto'],
            ':propina_tipo' => $data['propina_tipo'],
            ':propina_valor' => $data['propina_valor'],
            ':propina_calculada' => $data['propina_calculada'],
            ':recibido' => $data['monto_recibido'],
            ':cambio' => $data['cambio']
        ]);

        $subtotal = $data['monto'] - $data['propina_calculada'];
        insert_venta($cnx, $comanda_id, $pago_id, null, $subtotal, 'efectivo', $data['propina_tipo'], $data['propina_valor'], $data['propina_calculada'], $data['monto'], $data['monto_recibido'], $data['cambio']);
    } else {
        $stmt = $cnx->prepare("
            INSERT INTO pago_detalles 
            (pago_id, metodo_pago, monto, propina_tipo, propina_valor, propina_calculada, referencia_tarjeta)
            VALUES (:pago_id, 'tarjeta', :monto, :propina_tipo, :propina_valor, :propina_calculada, :referencia)
        ");
        $stmt->execute([
            ':pago_id' => $pago_id,
            ':monto' => $data['monto'],
            ':propina_tipo' => $data['propina_tipo'],
            ':propina_valor' => $data['propina_valor'],
            ':propina_calculada' => $data['propina_calculada'],
            ':referencia' => $data['referencia_tarjeta'] ?? null
        ]);

        $subtotal = $data['monto'] - $data['propina_calculada'];
        insert_venta($cnx, $comanda_id, $pago_id, null, $subtotal, 'tarjeta', $data['propina_tipo'], $data['propina_valor'], $data['propina_calculada'], $data['monto'], null, null);
    }
}

function guardarPagoMixto($cnx, $pago_id, $data, $comanda_id)
{
    // Pago en efectivo
    if ($data['monto_efectivo'] > 0) {
        $stmtEfectivo = $cnx->prepare("
            INSERT INTO pago_detalles 
            (pago_id, metodo_pago, monto, propina_tipo, propina_valor, propina_calculada, monto_recibido, cambio)
            VALUES (:pago_id, 'efectivo', :monto, :propina_tipo, :propina_valor, :propina_calculada, :recibido, :cambio)
        ");
        $stmtEfectivo->execute([
            ':pago_id' => $pago_id,
            ':monto' => $data['monto_efectivo'],
            ':propina_tipo' => $data['propina_tipo'],
            ':propina_valor' => $data['propina_valor'],
            ':propina_calculada' => $data['propina_calculada'],
            ':recibido' => $data['monto_recibido_efectivo'] ?? null,
            ':cambio' => $data['cambio_efectivo'] ?? null
        ]);
    }

    // Pago con tarjeta
    if ($data['monto_tarjeta'] > 0) {
        $stmtTarjeta = $cnx->prepare("
            INSERT INTO pago_detalles 
            (pago_id, metodo_pago, monto, propina_tipo, propina_valor, propina_calculada, referencia_tarjeta)
            VALUES (:pago_id, 'tarjeta', :monto, :propina_tipo, :propina_valor, :propina_calculada, :referencia)
        ");
        $stmtTarjeta->execute([
            ':pago_id' => $pago_id,
            ':monto' => $data['monto_tarjeta'],
            ':propina_tipo' => $data['propina_tipo'],
            ':propina_valor' => $data['propina_valor'],
            ':propina_calculada' => $data['propina_calculada'],
            ':referencia' => $data['referencia_tarjeta'] ?? null
        ]);
    }

    $subtotal = ($data['monto_tarjeta'] + $data['monto_efectivo']) - $data['propina_calculada'];
    $total = $data['monto_efectivo'] + $data['monto_tarjeta'];
    insert_venta($cnx, $comanda_id, $pago_id, 0, $subtotal, 'efectivo', $data['propina_tipo'], $data['propina_valor'], $data['propina_calculada'], $total, $data['monto_efectivo'], 0);
    insert_venta($cnx, $comanda_id, $pago_id, 0, 0, 'tarjeta', 0, 0, 0, 0, $data['monto_tarjeta'], 0);
}

function guardarCuentasSeparadas($cnx, $pago_id, $data, $comanda_id)
{
    foreach ($data['cuentas'] as $index => $cuenta) {
        // Insertar cuenta
        $stmtCuenta = $cnx->prepare("
            INSERT INTO pago_cuentas 
            (pago_id, numero_cuenta, metodo_pago, subtotal, propina_tipo, propina_valor, propina_calculada, 
             total_cuenta, monto_recibido, cambio, referencia_tarjeta)
            VALUES (:pago_id, :numero, :metodo, :subtotal, :propina_tipo, :propina_valor, :propina_calculada,
                    :total, :recibido, :cambio, :referencia)
        ");
        $stmtCuenta->execute([
            ':pago_id' => $pago_id,
            ':numero' => $index + 1,
            ':metodo' => $cuenta['metodo_pago'],
            ':subtotal' => $cuenta['subtotal'],
            ':propina_tipo' => $cuenta['propina_tipo'],
            ':propina_valor' => $cuenta['propina_valor'],
            ':propina_calculada' => $cuenta['propina_calculada'],
            ':total' => $cuenta['total'],
            ':recibido' => $cuenta['monto_recibido'] ?? null,
            ':cambio' => $cuenta['cambio'] ?? null,
            ':referencia' => $cuenta['referencia_tarjeta'] ?? null
        ]);

        $id_cuenta = (int) $cnx->lastInsertId();

        // Relacionar productos con la cuenta
        foreach ($cuenta['items'] as $item_id) {
            $stmtRelacion = $cnx->prepare("
                INSERT INTO pago_cuenta_productos (id_cuenta, item_id)
                VALUES (:id_cuenta, :item_id)
            ");
            $stmtRelacion->execute([
                ':id_cuenta' => $id_cuenta,
                ':item_id' => $item_id
            ]);
        }

        insert_venta($cnx, $comanda_id, $pago_id, $index+1, $cuenta['subtotal'], $cuenta['metodo_pago'], $cuenta['propina_tipo'], $cuenta['propina_valor'], $cuenta['propina_calculada'], $cuenta['total'], $cuenta['monto_recibido'], $cuenta['cambio']);
    }
}
?>