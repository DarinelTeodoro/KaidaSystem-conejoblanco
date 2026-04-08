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

  echo "<div class='p-2 mb-2' style='background:#fff; border:1px solid #eee; border-radius:10px; background: rgb(111, 66, 193, 0.2);'>
    <div class='d-flex justify-content-between align-items-center'>
      <div class='fw-bold'>{$seq}° Orden</div>
      <div class='text-muted' style='font-size:.85rem;'>{$b['created_at']}</div>
    </div>
    <div class='text-muted' style='font-size:.85rem;'>" . ($seq === 1 ? "Creación de comanda" : "Agregado después") . "</div>
  </div>";

  foreach ($items as $it) {
    $itemId = (int) $it['id'];

    if ($it['producto_id'] > 0) {
      $categoria = $cnx->prepare('SELECT mc.categoria FROM menu_categorias mc LEFT JOIN menu_productos mp ON mc.id = mp.id_categoria WHERE mp.id = :id');
      $categoria->execute([':id' => $it['producto_id']]);
      $resultCategoria = $categoria->fetch(PDO::FETCH_ASSOC);
    } else {
      $resultCategoria['categoria'] = 'NOTING';
    }

    // componentes
    $stmtComp = $cnx->prepare("SELECT * FROM comanda_item_componentes WHERE item_id = :iid ORDER BY id ASC");
    $stmtComp->execute([':iid' => $itemId]);
    $comps = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

    $subtotal = ((float) $it['precio']) * ((int) $it['qty']);

    $extrasHtml = "";
    $detHtml = "";

    // Separar extras del resto de componentes
    $extras = array_filter($comps, fn($cp) => $cp['kind'] === 'extra');
    $otros = array_filter($comps, fn($cp) => $cp['kind'] !== 'extra');

    // Agrupar componentes no-extra por grupo_id (para combos)
    $grupos = [];
    foreach ($otros as $cp) {
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
      if (empty($grupo['nombre'])) {
        // Si no hay nombre de grupo, mostrar items individualmente
        foreach ($grupo['items'] as $cp) {
          if ($cp['kind'] == 'variante') {
            $detHtml .= '';
          } else {
            $detHtml .= "<div class='text-muted' style='font-size:.85rem;'><span class='badge bg-secondary' style='font-size:0.6rem;'>{$cp['kind']}</span> {$cp['nombre']}</div>";
          }
        }
      } else {
        // Mostrar grupo con sus items
        $detHtml .= "<div class='mb-1'>";
        $detHtml .= "<span class='text-muted fw-bold' style='font-size:.85rem;'>{$grupo['nombre']}:</span>";
        $detHtml .= "<ul class='m-0 ps-3' style='list-style-type: none; padding-left: 0 !important;'>";

        foreach ($grupo['items'] as $item) {
          $detHtml .= "<li style='margin-left: 0; padding-left: 0; font-size:.85rem;'>";
          $detHtml .= "<i class='bi bi-check-circle-fill " . ($item['kind'] === 'incluido' ? 'text-success' : 'text-primary') . "' style='font-size:0.7rem;'></i> ";
          $detHtml .= $item['nombre'];
          $detHtml .= "</li>";
        }

        $detHtml .= "</ul>";
        $detHtml .= "</div>";
      }
    }

    // Procesar extras
    foreach ($extras as $cp) {
      $extrasHtml .= "<div class='text-muted' style='font-size:.85rem;'>+ {$cp['nombre']} x{$cp['qty']} ($" . number_format($cp['precio'] * $cp['qty'], 2) . ")</div>";
      $subtotal += ((float) $cp['precio']) * ((int) $cp['qty']);
    }

    $totalComanda += $subtotal;

    echo "<div class='p-2 mb-2' style='background:#fff; border:1px solid rgb(0, 0, 0, 0.3); border-radius:10px;'>
      <div class='d-flex justify-content-between'>
        <div style='flex:1;'>
          <div class='fw-bold d-flex align-items-center'>" . htmlspecialchars($it['nombre']) . ($resultCategoria['categoria'] == 'Café Caliente' ? '<span class="bg-warning p-1 pt-0 pb-0 ms-2 rounded shadow d-flex align-items-center"><i class="bi bi-thermometer-sun"></i> <span style="font-size: 0.8rem;">Caliente</span></span>' : ($resultCategoria['categoria'] == 'A las Rocas' ? '<span class="bg-info p-1 pt-0 pb-0 ms-2 rounded shadow d-flex align-items-center"><i class="bi bi-thermometer-snow"></i> <span style="font-size: 0.8rem;">Frio</span></span>' : '')) . "</div>
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
}

echo "<div class='p-2 mt-2' style='background:#fff; border:1px solid #eee; border-radius:10px; background: rgb(0, 0, 0);'>
  <div class='d-flex justify-content-between align-items-center'>
    <div class='fw-bold text-light'>Total comanda</div>
    <div class='fw-bold text-warning'>$" . number_format($totalComanda, 2) . "</div>
  </div>
</div>";
?>