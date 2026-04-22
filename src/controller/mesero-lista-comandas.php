<?php
session_start();
$mesero = $_SESSION['data-useractive'];
include('../model/querys.php');

function list_descuentos($comanda_id)
{
    $cnx = new Conexion();
    $list_descuentos = $cnx->prepare("SELECT * FROM descuentos WHERE id_comanda = :id ORDER BY id ASC");
    $list_descuentos->execute([':id' => $comanda_id]);

    return $list_descuentos->fetchAll(PDO::FETCH_ASSOC);
}

function suma($comanda_id)
{
    $cnx = new Conexion();
    $sum_descuentos = $cnx->prepare("SELECT SUM(descuento) as descuento FROM descuentos WHERE id_comanda = :id");
    $sum_descuentos->execute([':id' => $comanda_id]);

    return $sum_descuentos->fetch(PDO::FETCH_ASSOC);
}

$estado = $_POST['estado'] ?? 'pendiente';

$comandas = data_comandas($estado, $mesero);

$html = "";

if ($comandas) {
    foreach ($comandas as $c) {

        $descuentos = list_descuentos($c['id']);

        if (!$descuentos) {
            $hay_descuentos = false;
            $cantidad_descuento = 0;
        } else {
            $hay_descuentos = true;
            $data_descuento = suma($c['id']);
            $cantidad_descuento = floatval($data_descuento['descuento']);
        }

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

        $html .= "
        <div class='card_comanda p-3' data-id='{$c['id']}'>
            <div class='d-flex justify-content-between'>
                <div>
                    <div class='fw-bold'>Comanda #{$c['id']}</div>
                    <div class='text-muted' style='font-size:.9rem;'>
                        <span class='text-uppercase'>{$c['tipo']}</span> - 
                        " . ($c['tipo'] === 'mesa' ? "<b class='text-danger text-uppercase'>Mesa {$c['mesa']}</b>" : "<b class='text-success text-uppercase'>" . $c['cliente'] . "</b>") . "
                    </div>
                </div>
                <div class='text-end'>
                    <div>".($hay_descuentos == true ? "<span class='text-muted text-decoration-line-through'>$" . number_format($total, 2) . "</span> " : "")."<span class='fw-bold text-primary'>$" . number_format($total - $cantidad_descuento, 2) . "</span></div>
                    <div class='text-muted' style='font-size:.8rem;'>{$c['created_at']}</div>
                </div>
            </div>
            <div class='d-flex align-items-center justify-content-start mt-1'>
                " . $action . "
            </div>
        </div>
        ";
    }
} else {
    $html .= "<div class='p-4 text-muted text-center'>
        <i class='bi bi-inboxes fs-1 d-block mb-3'></i>No hay comandas en esta vista
    </div>";
}

// Devolver SOLO el HTML de las cards (sin contenedor padre)
echo $html;
?>