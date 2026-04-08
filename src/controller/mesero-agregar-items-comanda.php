<?php
date_default_timezone_set('America/Mexico_City');
include('../model/conexion.php');
$fecha = date('Y-m-d H:i:s');

if (!isset($_POST['comanda_id'], $_POST['payload'])) {
  http_response_code(400);
  exit('Missing data');
}

$comanda_id = (int)$_POST['comanda_id'];
$data = json_decode($_POST['payload'], true);

if (!$data || empty($data['items']) || !is_array($data['items'])) {
  http_response_code(400);
  exit('La comanda no puede ir vacía.');
}

$cnx = new Conexion();

// Función para obtener el destino de un producto desde su categoría
function obtenerDestinoProducto($cnx, $producto_id) {
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
function obtenerDestinoExtra($cnx, $extra_id) {
    $query = $cnx->prepare("SELECT destino FROM menu_extras WHERE id = :id");
    $query->execute([':id' => $extra_id]);
    return $query->fetchColumn();
}

// Verificar estado de la comanda
$stmtestado = $cnx->prepare("SELECT estado FROM comandas WHERE id = :id");
$stmtestado->execute([':id' => $comanda_id]);
$result_estado = $stmtestado->fetch(PDO::FETCH_ASSOC);

// PARA DEPURACIÓN - Guardar en log de error
error_log("Estado recuperado: " . print_r($result_estado, true));
error_log("Comanda ID: " . $comanda_id);

if ($result_estado && $result_estado['estado'] == 'pendiente') {
  
  $cnx->beginTransaction();

  // Obtener los flags actuales de la comanda
  $stmtFlags = $cnx->prepare("SELECT cocina, barra FROM comandas WHERE id = :id");
  $stmtFlags->execute([':id' => $comanda_id]);
  $flagsActuales = $stmtFlags->fetch(PDO::FETCH_ASSOC);
  
  $cocina_actual = (int)($flagsActuales['cocina'] ?? 0);
  $barra_actual = (int)($flagsActuales['barra'] ?? 0);
  
  // Inicializar flags para los NUEVOS items (cada uno independiente)
  $cocina_nuevos = 0;
  $barra_nuevos = 0;

  // 1) Obtener siguiente seq (lote)
  $stmtSeq = $cnx->prepare("SELECT COALESCE(MAX(seq),0) AS mx FROM comanda_batches WHERE comanda_id = :id");
  $stmtSeq->execute([':id' => $comanda_id]);
  $mx = (int)($stmtSeq->fetch(PDO::FETCH_ASSOC)['mx'] ?? 0);
  $nextSeq = $mx + 1;

  // 2) Crear nuevo batch
  $stmtBatch = $cnx->prepare("INSERT INTO comanda_batches (comanda_id, seq, created_at) VALUES (:id, :seq, :fecha)");
  $stmtBatch->execute([':id' => $comanda_id, ':seq' => $nextSeq, ':fecha' => $fecha]);
  $batch_id = (int)$cnx->lastInsertId();

  // 3) Prepare items
  $stmtItem = $cnx->prepare("
    INSERT INTO comanda_items
      (comanda_id, batch_id, uid, tipo, combo_id, producto_id, variante_id, nombre, qty, precio, nota, created_at, destino)
    VALUES
      (:comanda_id, :batch_id, :uid, :tipo, :combo_id, :producto_id, :variante_id, :nombre, :qty, :precio, :nota, :fecha, :destino)
  ");

  // 4) Prepare componentes (incluye extra_id)
  $stmtComp = $cnx->prepare("
    INSERT INTO comanda_item_componentes
      (item_id, kind, grupo_id, grupo_nombre, producto_id, extra_id, variante_id, nombre, qty, precio, destino)
    VALUES
      (:item_id, :kind, :grupo_id, :grupo_nombre, :producto_id, :extra_id, :variante_id, :nombre, :qty, :precio, :destino)
  ");

  foreach ($data['items'] as $it) {
    $uid = $it['uid'] ?? '';
    $tipoItem = $it['type'] ?? 'product';
    $qty = (int)($it['qty'] ?? 1);
    $note = $it['note'] ?? null;

    $combo_id = null; $producto_id = null; $variante_id = null; $nombre = ''; $precio = 0;

    if ($tipoItem === 'combo') {
      $combo_id = $it['base']['combo_id'] ?? null;
      $nombre = $it['base']['nombre'] ?? 'Combo';
      $precio = (float)($it['base']['precio'] ?? 0);
      $destino_db = 'Ambos';
      
      // Para combos, revisamos sus componentes
      if (!empty($it['components']) && is_array($it['components'])) {
          foreach ($it['components'] as $c) {
              $prod_id = $c['producto_id'] ?? ($c['product_id'] ?? null);
              if ($prod_id) {
                  $destino = obtenerDestinoProducto($cnx, $prod_id);
                  // Cada destino se maneja INDEPENDIENTEMENTE
                  if ($destino === 'Cocina') $cocina_nuevos = 1;
                  if ($destino === 'Barra') $barra_nuevos = 1;
              }
          }
      }
    } else {
      $producto_id = $it['base']['product_id'] ?? null;
      $variante_id = $it['base']['variante_id'] ?? null;
      $nombre = $it['base']['nombre'] ?? 'Producto';
      $precio = (float)($it['base']['precio'] ?? 0);
      $destino_db = obtenerDestinoProducto($cnx, $producto_id);
      if (!empty($it['base']['variante_nombre'])) $nombre .= " (" . $it['base']['variante_nombre'] . ")";
      
      // Verificar destino del producto principal
      if ($producto_id) {
          $destino = obtenerDestinoProducto($cnx, $producto_id);
          // Cada destino se maneja INDEPENDIENTEMENTE
          if ($destino === 'Cocina') $cocina_nuevos = 1;
          if ($destino === 'Barra') $barra_nuevos = 1;
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

    $item_id = (int)$cnx->lastInsertId();

    // components (para productos normales con variantes)
    if (!empty($it['components']) && is_array($it['components'])) {
      foreach ($it['components'] as $c) {

          // Si es una variante, verificar su destino
          if (isset($c['kind']) && $c['kind'] === 'variante' && isset($c['product_id'])) {
              $destino = obtenerDestinoProducto($cnx, $c['product_id']);
              // Cada destino se maneja INDEPENDIENTEMENTE
              if ($destino === 'Cocina') $cocina_nuevos = 1;
              if ($destino === 'Barra') $barra_nuevos = 1;
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
            ':qty' => (int)($c['qty'] ?? 1),
            ':precio' => (float)($c['precio'] ?? 0),
            ':destino' => obtenerDestinoProducto($cnx, $c['producto_id'] ?? ($c['product_id'] ?? null)),
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
            // Cada destino se maneja INDEPENDIENTEMENTE
            if ($destino === 'Cocina') $cocina_nuevos = 1;
            if ($destino === 'Barra') $barra_nuevos = 1;
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
          ':qty' => (int)($ex['qty'] ?? 1),
          ':precio' => (float)($ex['precio'] ?? 0),
          ':destino' => $destino,
        ]);
      }
    }
  }

  // --- LÓGICA INDEPENDIENTE PARA CADA DESTINO ---
  
  // Determinar valor final para COCINA (independiente)
  if ($cocina_nuevos > 0) {
      // Si llegaron productos nuevos de cocina
      if ($cocina_actual == 2) {
          // Estaba completado, ahora pasa a pendiente
          $cocina_final = 1;
      } else if ($cocina_actual == 0) {
          // No había nada, ahora tiene productos
          $cocina_final = 1;
      } else {
          // Ya estaba en 1, se mantiene
          $cocina_final = $cocina_actual;
      }
  } else {
      // No llegaron productos nuevos de cocina, se mantiene el valor actual
      $cocina_final = $cocina_actual;
  }
  
  // Determinar valor final para BARRA (independiente)
  if ($barra_nuevos > 0) {
      // Si llegaron productos nuevos de barra
      if ($barra_actual == 2) {
          // Estaba completado, ahora pasa a pendiente
          $barra_final = 1;
      } else if ($barra_actual == 0) {
          // No había nada, ahora tiene productos
          $barra_final = 1;
      } else {
          // Ya estaba en 1, se mantiene
          $barra_final = $barra_actual;
      }
  } else {
      // No llegaron productos nuevos de barra, se mantiene el valor actual
      $barra_final = $barra_actual;
  }

  // Actualizar solo si hubo cambios (en cualquiera de los dos)
  if ($cocina_final != $cocina_actual || $barra_final != $barra_actual) {
      $stmtUpdate = $cnx->prepare("
          UPDATE comandas 
          SET cocina = :cocina, barra = :barra 
          WHERE id = :id
      ");
      $stmtUpdate->execute([
          ':cocina' => $cocina_final,
          ':barra' => $barra_final,
          ':id' => $comanda_id
      ]);
  }

  $cnx->commit();
  echo 'OK';
  
} else {
  // CORRECCIÓN: No hacer rollback porque no hay transacción activa
  // $cnx->rollBack(); ← ELIMINADO
  
  http_response_code(400); // Bad request en lugar de 500
  
  $mensaje_error = 'No se pueden agregar items. ';
  if (!$result_estado) {
      $mensaje_error .= 'La comanda no existe.';
  } else {
      $mensaje_error .= 'Estado actual: ' . $result_estado['estado'];
  }
  
  echo $mensaje_error;
  
  // PARA DEPURACIÓN - Mostrar también en el log
  error_log("Error al agregar items: " . $mensaje_error . " - Comanda ID: " . $comanda_id);
}

// PARA DEPURACIÓN - Esto se ejecutará siempre
error_log("Fin del script - Comanda ID: " . $comanda_id . " - Estado: " . ($result_estado['estado'] ?? 'null'));
?>