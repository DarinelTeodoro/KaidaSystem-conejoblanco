<?php
include('../model/conexion.php');

$comanda_id = (int) ($_POST['comanda_gestionar'] ?? 0);
if (!$comanda_id)
  exit('Comanda inválida');

$cnx = new Conexion();

// Comanda
$stmtC = $cnx->prepare("SELECT c.*, u.name AS nombre FROM comandas c LEFT JOIN usuarios u ON c.user_id = u.username WHERE c.id = :id");
$stmtC->execute([':id' => $comanda_id]);
$c = $stmtC->fetch(PDO::FETCH_ASSOC);
if (!$c)
  exit('No encontrada');

// Batches
$stmtB = $cnx->prepare("SELECT * FROM comanda_batches WHERE comanda_id = :id ORDER BY seq ASC");
$stmtB->execute([':id' => $comanda_id]);
$batches = $stmtB->fetchAll(PDO::FETCH_ASSOC);

if ($c['estado'] == 'pendiente') {
  $bg = 'bg-warning text-dark';
  $accion = '
  <form method="post" action="" id="form-cancelacion">
  <input type="hidden" name="id-comanda" id="id-comanda" value="' . $comanda_id . '">
  <div class="text-muted">
    <span>Al cancelar la comanda:</span>
    <ul>
      <li>No se contará como pendiente en cocina/barra.</li>
      <li>No aparecerá como pendiente de cobro.</li>
      <li>El motivo quedará registrado en la bitácora de cancelaciones.</li>
    </ul>
  </div>
  <div class="d-grid">
    <label for="motivo-cancelacion">Motivo de la cancelación</label>
    <textarea name="motivo-cancelacion" id="motivo-cancelacion" rows="3" placeholder="Ej. Cliente cambió de opinión, error en la comanda, etc." required></textarea>
    <button type="submit" class="btn-delete mt-2" style="padding: 4px;">Cancelar Comanda</button>
  </div>
  </form>
  ';
} else if ($c['estado'] == 'finalizado') {
  $bg = 'bg-success text-light';
  $accion = '<div>
    <span class="text-success">Comanda completada!</span>
    <div class="text-muted">
    <ul>
      <li>La comanda fue cobrada.</li>
      <li>Los productos fueron entregados.</li>
    </ul>
  </div>
  </div>';
} else if ($c['estado'] == 'cancelado') {
  $bg = 'bg-danger text-light';
  $accion = '<div>
    <span class="text-danger">Comanda cancelada!</span>
    <div class="mt-2"><span class="text-muted" style="font-size: 0.8rem;">Motivo:</span></div>
    <div>' . $c['motivo_cancelacion'] . '</div>
  </div>';
}

$cocina = ($c['cocina'] == 1 ? '<div class="p-1 rounded border border-dark"><i class="bi bi-egg-fried"></i></div>' : ($c['cocina'] == 2 ? '<div class="p-1 rounded border border-dark"><i class="bi bi-egg-fried"></i><i class="bi bi-check2 text-success"></i></div>' : ''));
$barra = ($c['barra'] == 1 ? '<div class="p-1 rounded border border-dark ms-1"><i class="bi bi-cup-hot"></i></div>' : ($c['barra'] == 2 ? '<div class="p-1 rounded border border-dark ms-1"><i class="bi bi-cup-hot"></i><i class="bi bi-check2 text-success"></i></div>' : ''));

echo "
<div style='box-shadow: rgba(0, 0, 0, 0.02) 0px 1px 3px 0px, rgba(27, 31, 35, 0.15) 0px 0px 0px 1px; border-radius: 10px;'>
  <div class='d-flex align-items justify-content-between' style='border-radius: 10px 10px 0px 0px; padding: 10px 10px 0px 10px; background: #ededed;'>
    <div><span class='fs-5 fw-bold'>Comanda #{$c['id']}</span></div>
    <div class='d-flex align-items-center'><span class='text-capitalize {$bg} p-1 pe-2 ps-2 rounded'>{$c['estado']}</span></div>
  </div>
  <div class='d-flex align-items justify-content-between' style='padding: 0px 10px 0px 10px; background: #ededed;'>
    <div><span class='text-muted'>" . ($c['tipo'] === 'mesa' ? "Mesa {$c['mesa']}" : "Entrega a: <span class='text-capitalize'>" . htmlspecialchars($c['cliente'] ?? '') . "</span>") . "</span></div>
    <div class='d-flex pt-1 pb-1'>" . $cocina . $barra . "</div>
  </div>
  <div class='d-flex align-items justify-content-end' style='padding: 0px 10px 10px 10px; background: #ededed;'>
    <span class='text-muted'>Atiende: " . $c['nombre'] . "</span>
  </div>
  <div class='p-2 row m-0' style='background: #ffffff;'>
    <div class='col-12 col-md-7 col-xl-8 pt-2'>
";

$totalComanda = 0;

foreach ($batches as $b) {
  $batchId = (int) $b['id'];
  $seq = (int) $b['seq'];

  // items del batch
  $stmtI = $cnx->prepare("SELECT * FROM comanda_items WHERE comanda_id = :cid AND batch_id = :bid AND tipo != 'extra' ORDER BY id ASC");
  $stmtI->execute([':cid' => $comanda_id, ':bid' => $batchId]);
  $items = $stmtI->fetchAll(PDO::FETCH_ASSOC);

  if (!$items)
    continue;

  echo "<div class='p-2 mb-2'>
    <div class='d-flex justify-content-between align-items-center'>
      <div class='text-muted' style='font-size:.85rem;'>" . ($seq === 1 ? "Creación de comanda" : "Agregado después") . "</div>
      <div class='text-muted text-end' style='font-size:.85rem;'>{$b['created_at']}</div>
    </div>
  </div>";

  foreach ($items as $it) {
    $itemId = (int) $it['id'];

    // componentes
    $stmtComp = $cnx->prepare("SELECT * FROM comanda_item_componentes WHERE item_id = :iid ORDER BY id ASC");
    $stmtComp->execute([':iid' => $itemId]);
    $comps = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

    $subtotal = ((float) $it['precio']) * ((int) $it['qty']);

    $extrasHtml = "";
    $detHtml = "";

    $stmtE = $cnx->prepare("SELECT * FROM comanda_items WHERE item_id = :id");
    $stmtE->execute([':id' => $itemId]);
    $extras = $stmtE->fetchAll(PDO::FETCH_ASSOC);

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

    if ($c['estado'] == 'finalizado' || $c['estado'] == 'cancelado') {
      $eliminar = '';
    } else if ($c['estado'] == 'pendiente') {
      $completado = $it['destino'] === 'Barra' ? $it['ready_barra'] : ($it['destino'] === 'Ambos' ? $it['ready_cocina'] + $it['ready_barra'] : $it['ready_cocina']);
      $eliminar = $completado > 0 ? 'Completado <i class="bi bi-check2 text-success"></i>' : '<div class="p-1 pt-0 pb-0 rounded shadow bg-danger text-light" style="font-size: 0.9rem; padding: 3px 5px; margin-top: 5px; cursor: pointer;" onclick="eliminar_item(\'' . $it['destino'] . '\',' . $c['id'] . ', ' . $it['id'] . ')">Eliminar</div>';
    }

    echo "<div class='p-2 mb-2' style='background:#fff; border:1px solid rgb(0, 0, 0, 0.3); border-radius:10px;'>
      <div class='d-flex justify-content-between'>
        <div style='flex:1;'>
          <div class='fw-bold'>" . htmlspecialchars($it['nombre']) . "</div>
          {$detHtml}
          {$extrasHtml}
          " . (!empty($it['nota']) ? "<div class='p-1 mt-2'><div><span class='text-muted' style='font-size:.85rem;'>Nota:</span></div><div>" . htmlspecialchars($it['nota']) . "</div></div>" : "") . "
        </div>
        <div class='text-muted'>$" . number_format($it['precio'], 2) . "</div>
      </div>
      <div class='d-flex justify-content-between'>
        <div><i class='text-muted' style='font-size: 0.8rem;'>Total = </i> <span class='text-primary fw-bold'>$" . number_format($subtotal, 2) . "</span></div>
        <div>" . $eliminar . "</div>
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

echo "</div>
  <div class='col-12 col-md-5 col-xl-4 pt-2'>
    <div class='border text-center' style='padding: 10px; background: #ededed;'>Observaciones</div>
    <div class='border p-2 pe-3 ps-3'>" . $accion . "</div>
  </div>
</div></div>";
?>


<script>
  $('#form-cancelacion').submit(function (event) {
    event.preventDefault();
    var formData = new FormData(this);

    Swal.fire({
      title: `¿Estás seguro de cancelar esta comanda? Esta acción no se puede deshacer.`,
      showDenyButton: false,
      showCancelButton: true,
      confirmButtonText: "Confirmar"
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          type: "post",
          url: "../../controller/comanda-cancelar.php",
          data: formData,
          processData: false,
          contentType: false,
          dataType: 'html',
          success: function (response) {
            auxiliaRecargar(response);
          }
        });
      }
    });
  });
</script>