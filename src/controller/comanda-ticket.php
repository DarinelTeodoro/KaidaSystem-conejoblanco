<?php
include('../model/conexion.php');
$comanda_id = $_GET['comanda_id'];
date_default_timezone_set('America/Mexico_City');
?>

<style>
    .main-container {
        width: 70mm;
        background: #ffffff;
        margin: 0px auto 10px auto;
        padding: 0px 10px 10px 10px;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }

    .cont-logo {
        display: flex;
        justify-content: center;
    }

    .info-restaurante {
        display: flex;
        justify-content: center;
        text-align: center;
        flex-direction: column;
        font-size: 13px;
        line-height: 1.35;
    }

    .hr {
        border: 0;
        border-top: 1px dashed;
        margin: 8px 0;
    }

    .lote {
        border: 0;
        border-top: 1px dotted;
        margin: 5px 0px;
    }

    .despedida {
        font-size: 13px;
        font-weight: bold;
        text-align: center;
    }

    .datos-comanda {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 13px;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }

    .dato-total {
        margin-top: 5px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        font-weight: 800;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }

    .main-product {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 14px;
        font-weight: 800;
        font-family: monospace;
    }

    .data-extra {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        font-weight: 200;
        font-family: monospace;
    }

    .data-subtotal {
        display: flex;
        align-items: center;
        justify-content: end;
        font-size: 13px;
        font-weight: 700;
        font-family: monospace;
    }
</style>

<div class="main-container">
    <div class="cont-logo">
        <img src="../../img/fulllogo-nbg.png" style="height: 150px;">
    </div>
    <div class="info-restaurante">
        <span>C. Francisco I. Madero 514</span>
        <span>Zona Centro, 27980</span>
        <span>Parras de la Fuente, Coah.</span>
        <span>Tel: 842 148 5513</span>
    </div>
    <div class="hr"></div>


    <?php
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

    echo '<div class="datos-comanda">
        <div>Folio: <b>' . $c['id'] . '</b></div>
        <div>' . ($c['tipo'] === 'mesa' ? 'Mesa: ' : 'Entrega: ') . '<b>' . ($c['tipo'] === 'mesa' ? $c['mesa'] : $c['cliente']) . '</b></div>
    </div>';
    echo '<div class="datos-comanda">
        <div>' . date('d-m-Y H:i') . '</b></div>
        <div>Mesero: <b>' . $c['nombre'] . '</b></div>
    </div>';
    echo '<div class="hr"></div>';

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

        if ($seq > 1) {
            echo "<div class='lote'></div>";
        }

        foreach ($items as $it) {
            $itemId = (int) $it['id'];

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

            $tieneExtras = !empty($extras);

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

            // Procesar extras
            foreach ($extras as $cp) {
                $extrasHtml .= "<div class='data-extra'><div>+ {$cp['nombre']} x{$cp['qty']}</div><div>$" . number_format($cp['precio'] * $cp['qty'], 2) . "</div></div>";
                $subtotal += ((float) $cp['precio']) * ((int) $cp['qty']);
            }

            $totalComanda += $subtotal;

            echo '<div style="border-bottom: 0.3px solid rgb(0,0,0,0.2); border-top: 0.3px solid rgb(0,0,0,0.2); margin-bottom: 1px; padding: 2px 0px;">
                <div class="main-product">
                    <div>' . htmlspecialchars($it['nombre']) . '</div>
                    <div style="font-weight: 400;">$' . number_format($it['precio'], 2) . '</div>
                </div>
                <div>' . $extrasHtml . '</div>
                ';

            if ($tieneExtras) {
                echo '<div class="data-subtotal"><div style="margin-right: 10px;">Subtotal =</div><div>$' . number_format($subtotal, 2) . '</div></div>';
            }

            echo '</div>';
        }
    }

    echo "<div class='dato-total'>
        <div>TOTAL</div>
        <div class='data-money'>$" . number_format($totalComanda, 2) . "</div>
    </div>";
    ?>


    <div class="hr"></div>
    <div class="despedida"><span>¡Gracias por su preferencia!</span></div>
    <div align="center"><i style="font-size: 10px; margin-top: 10px; font-weight: bold;">Kaida System</i></div>
</div>