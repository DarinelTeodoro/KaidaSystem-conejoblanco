<?php
include('../model/conexion.php');

$comanda_id = (int) ($_POST['id'] ?? 0);
if (!$comanda_id)
  exit('Comanda inválida');

$cnx = new Conexion();

// Comanda
$stmtC = $cnx->prepare("SELECT * FROM comandas WHERE id = :id");
$stmtC->execute([':id' => $comanda_id]);
$c = $stmtC->fetch(PDO::FETCH_ASSOC);
if (!$c)
  exit('No encontrada');

// Batches
$stmtB = $cnx->prepare("SELECT * FROM comanda_batches WHERE comanda_id = :id ORDER BY seq ASC");
$stmtB->execute([':id' => $comanda_id]);
$batches = $stmtB->fetchAll(PDO::FETCH_ASSOC);

echo "<div class='mb-2'>
  <div class='fw-bold'>Comanda #{$c['id']}</div>
  <div class='text-muted' style='font-size:.9rem;'>
    <div><span class='text-uppercase'>{$c['tipo']}</span> - " . ($c['tipo'] === 'mesa' ? "Mesa {$c['mesa']}" : htmlspecialchars($c['cliente'] ?? '')) . "</div>
    <div>Estado: <b class='text-capitalize'>{$c['estado']}</b></div>
  </div>
</div>";

if ($c['estado'] == 'pendiente') {
  echo "<div class='d-grid mb-3'>
  <a href='form-edit-comanda.php?id_comanda={$c['id']}' class='btn-send-comanda'>Agregar más productos/combos</a>
</div>";
}

if ($c['estado'] == 'cancelado') {
  echo "<div class='d-grid mb-3'>
  <span class='text-muted'>Motivo de cancelación:</span>
  <div class='text-danger'>" . $c['motivo_cancelacion'] . "</div>
</div>";
}

$totalComanda = 0;

foreach ($batches as $b) {
  $batchId = (int) $b['id'];
  $seq = (int) $b['seq'];

  // items del batch
  $stmtI = $cnx->prepare("SELECT * FROM comanda_items WHERE comanda_id = :cid AND batch_id = :bid ORDER BY id ASC");
  $stmtI->execute([':cid' => $comanda_id, ':bid' => $batchId]);
  $items = $stmtI->fetchAll(PDO::FETCH_ASSOC);

  if (!$items)
    continue;

  // Separar productos/variantes de los extras
  $productos = array_filter($items, fn($it) => $it['tipo'] === 'product' || $it['tipo'] === 'variante');
  $extrasSueltos = array_filter($items, fn($it) => $it['tipo'] === 'extra' && ($it['item_id'] == 0 || $it['item_id'] === null));
  
  // Crear un mapa de extras por item_id
  $extrasPorProducto = [];
  foreach ($items as $it) {
    if ($it['tipo'] === 'extra' && $it['item_id'] > 0) {
      if (!isset($extrasPorProducto[$it['item_id']])) {
        $extrasPorProducto[$it['item_id']] = [];
      }
      $extrasPorProducto[$it['item_id']][] = $it;
    }
  }

  echo "<div class='p-2 mb-2' style='background:#fff; border:1px solid #eee; border-radius:10px; background: rgb(111, 66, 193, 0.2);'>
    <div class='d-flex justify-content-between align-items-center'>
      <div class='fw-bold'>{$seq}° Orden</div>
      <div class='text-muted' style='font-size:.85rem;'>{$b['created_at']}</div>
    </div>
    <div class='text-muted' style='font-size:.85rem;'>" . ($seq === 1 ? "Creación de comanda" : "Agregado después") . "</div>
  </div>";

  // Mostrar productos/variantes con sus extras correspondientes
  foreach ($productos as $it) {
    $itemId = (int) $it['id'];
    
    // Obtener los extras que pertenecen a este producto
    $extrasDelProducto = $extrasPorProducto[$itemId] ?? [];

    // componentes del producto (incluidos y opcionales del combo)
    $stmtComp = $cnx->prepare("SELECT * FROM comanda_item_componentes WHERE item_id = :iid ORDER BY id ASC");
    $stmtComp->execute([':iid' => $itemId]);
    $comps = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

    $subtotal = ((float) $it['precio']) * ((int) $it['qty']);

    $detHtml = "";

    // Separar componentes que no son extras (incluidos y opcionales del combo)
    $componentes = array_filter($comps, fn($cp) => $cp['kind'] !== 'extra');

    // Agrupar componentes por grupo_id (para combos)
    $grupos = [];
    foreach ($componentes as $cp) {
      $grupoId = $cp['grupo_id'] ?? 0;
      $grupoNombre = $cp['grupo_nombre'] ?? '';

      if (!isset($grupos[$grupoId])) {
        $grupos[$grupoId] = [
          'nombre' => $grupoNombre,
          'items' => []
        ];
      }
      $grupos[$grupoId]['items'][] = $cp;
    }

    // Generar HTML para componentes agrupados
    foreach ($grupos as $grupo) {
      $detHtml .= "<div class='mb-1'>";
      $detHtml .= "<span class='text-muted fw-bold' style='font-size:.85rem;'>{$grupo['nombre']}:</span>";
      $detHtml .= "<ul class='m-0 ps-3' style='list-style-type: none; padding-left: 0 !important;'>";

      foreach ($grupo['items'] as $item) {
        $detHtml .= "<li style='margin-left: 0; padding-left: 0; font-size:.85rem;'>";
        $detHtml .= "<i class='bi bi-check-circle-fill " . ($item['kind'] === 'incluido' ? 'text-success' : 'text-primary') . "' style='font-size:0.7rem;'></i> ";
        $detHtml .= $item['nombre'] . " x{$item['qty']}";
        $detHtml .= "</li>";
      }

      $detHtml .= "</ul>";
      $detHtml .= "</div>";
    }

    // Mostrar extras de comanda_items que pertenecen a este producto
    $extrasHtml = "";
    foreach ($extrasDelProducto as $extra) {
      $extrasHtml .= "<div class='text-muted' style='font-size:.85rem;'>+ {$extra['nombre']} x{$extra['qty']} ($" . number_format($extra['precio'] * $extra['qty'], 2) . ")</div>";
      $subtotal += ((float) $extra['precio']) * ((int) $extra['qty']);
    }

    $totalComanda += $subtotal;

    echo "<div class='p-2 mb-2' style='background:#fff; border:1px solid rgb(0, 0, 0, 0.3); border-radius:10px;'>
      <div class='d-flex justify-content-between'>
        <div style='flex:1;'>
          <div class='fw-bold d-flex align-items-center'>" . htmlspecialchars($it['nombre']) . " </div>
          {$detHtml}
          {$extrasHtml}
          " . (!empty($it['nota']) ? "<div class='p-1 mt-2'><div><span class='text-muted' style='font-size:.85rem;'>Nota:</span></div><div>" . htmlspecialchars($it['nota']) . "</div></div>" : "") . "
        </div>
        <div class='text-muted'>$" . number_format($it['precio'], 2) . "</div>
      </div>
      <div class='d-flex justify-content-between'>
        <div></div>
        <div><i class='text-muted'>Subtotal = </i> <span class='text-primary fw-bold'>$" . number_format($subtotal, 2) . "</span></div>
      </div>
    </div>";
  }

  // Mostrar extras independientes (que no pertenecen a ningún producto específico)
  foreach ($extrasSueltos as $extra) {
    $subtotalExtra = ((float) $extra['precio']) * ((int) $extra['qty']);
    $totalComanda += $subtotalExtra;

    echo "<div class='p-2 mb-2' style='background:#fff; border:1px solid rgb(0, 0, 0, 0.3); border-radius:10px;'>
      <div class='d-flex justify-content-between'>
        <div style='flex:1;'>
          <div class='fw-bold'>➕ " . htmlspecialchars($extra['nombre']) . " x{$extra['qty']}</div>
          " . (!empty($extra['nota']) ? "<div class='p-1 mt-2'><div><span class='text-muted' style='font-size:.85rem;'>Nota:</span></div><div>" . htmlspecialchars($extra['nota']) . "</div></div>" : "") . "
        </div>
        <div class='text-muted'>$" . number_format($extra['precio'], 2) . "</div>
      </div>
      <div class='d-flex justify-content-between'>
        <div></div>
        <div><i class='text-muted'>Subtotal = </i> <span class='text-primary fw-bold'>$" . number_format($subtotalExtra, 2) . "</span></div>
      </div>
    </div>";
  }
}

echo "<div class='p-2 mt-2' style='background:#fff; border:1px solid #eee; border-radius:10px; background: rgb(0, 0, 0);'>
  <div class='d-flex justify-content-between align-items-center'>
    <div class='fw-bold text-light'>Total comanda</div>
    <div class='fw-bold text-warning'>$" . number_format($totalComanda, 2) . "</div>
  </div>
</div>";
?>