<?php
session_start();
$mesero = $_SESSION['data-useractive'];
include('../model/querys.php');

$estado = $_POST['estado'] ?? null;

$comandas = data_comandas_caja($estado);

if (!$comandas) {
    echo '<div class="p-4 text-muted text-center">
        <i class="bi bi-inboxes fs-1 d-block mb-3"></i>No hay comandas en esta vista
        </div>';
    exit;
}

foreach ($comandas as $c) {
    $mesero = consultar_usuario($c['user_id']);
    $detalle = detalle_comanda($c['id']);
    $total = 0;

    foreach ($detalle['items'] as $item) {
        $subtotal = $item['precio'] * $item['qty'];

        foreach ($item['componentes'] as $comp) {
            if ($comp['kind'] === 'extra') {
                $subtotal += $comp['precio'] * $comp['qty'];
            }
        }

        $total += $subtotal;
    }

    if ($c['estado'] == 'cancelado') {
        $onclick = 'onclick="show_alert(\'Comanda Cancelada\', \'Motivo: ' . $c['motivo_cancelacion'] . '\')"';
    } else {
        if (($c['cocina'] == 2 && $c['barra'] == 0) || ($c['barra'] == 2 && $c['cocina'] == 0) || ($c['cocina'] == 2 && $c['barra'] == 2)) {
            $onclick = 'onclick="cobrar_comanda(' . $c['id'] . ')"';
        } else {
            $onclick = 'onclick="cobrar_denegado(' . $c['id'] . ')"';
        }
    }

    if ($c['estado'] == 'cancelado') {
        $action = "<div class='bg-danger fw-bold pe-2 ps-2 rounded' style='font-size: 0.8rem; color:#ffffff;'><i class='bi bi-clipboard-x me-1'></i>Cancelado</div>";
    } else if ($c['estado'] == 'finalizado') {
        $action = "<div class='bg-secondary fw-bold pe-2 ps-2 rounded' style='font-size: 0.8rem; color:#ffffff;'><i class='bi bi-cash-coin me-1'></i>Cobrado</div>";
    } else if ($c['estado'] == 'pendiente') {
        $action =
            ($c['cocina'] == 1 ? "<div class='bg-info fw-bold pe-2 ps-2 me-2 rounded' style='font-size: 0.8rem;'><i class='bi bi-egg-fried me-1'></i>Cocina</div>" :
                ($c['cocina'] == 2 ? "<div class='bg-info fw-bold pe-2 ps-2 rounded' style='font-size: 0.8rem;'><i class='bi bi-egg-fried me-1'></i>Cocina</div><div><i class='bi bi-clipboard-check-fill me-2' style='color: green; margin-left: 2px;'></i></div>" : "")) .
            ($c['barra'] == 1 ? "<div class='bg-warning fw-bold pe-2 ps-2 rounded' style='font-size: 0.8rem;'><i class='bi bi-cup-hot me-1'></i>Barra</div>" :
                ($c['barra'] == 2 ? "<div class='bg-warning fw-bold pe-2 ps-2 rounded' style='font-size: 0.8rem;'><i class='bi bi-cup-hot me-1'></i>Barra</div><div><i class='bi bi-clipboard-check-fill' style='color: green; margin-left: 2px;'></i></div>" : ""));
    }

    echo "
    <div class='card_comanda p-3' " . $onclick . ">
        <div class='d-flex justify-content-between'>
            <div>
                <div class='fw-bold'>Comanda #{$c['id']}</div>
                <div class='text-muted' style='font-size:.9rem;'>
                    <span class='text-uppercase'>{$c['tipo']}</span> - 
                    " . ($c['tipo'] === 'mesa' ? "<b class='text-danger text-uppercase'>Mesa {$c['mesa']}</b>" : "<b class='text-success text-uppercase'>" . $c['cliente']) . "</b>
                </div>
            </div>
            <div class='text-end'>
                <div class='fw-bold text-primary'>$" . number_format($total, 2) . "</div>
                <div class='text-muted' style='font-size:.8rem;'>{$c['created_at']}</div>
                <div class='text-muted' style='font-size:.8rem;'>{$mesero['name']}</div>
            </div>
        </div>
        <div class='d-flex align-items-center justify-content-start mt-1'>{$action}</div>
    </div>
    ";
}
?>