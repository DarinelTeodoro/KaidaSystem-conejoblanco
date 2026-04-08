<?php
include('../model/conexion.php');
date_default_timezone_set('America/Mexico_City');
require_once('../../fpdf/fpdf.php');
$fecha_ventas = date('d-m-Y', strtotime($_GET['day']));

$cnx = new Conexion();
$stmtTotal_comandas = $cnx->prepare("
        SELECT SUM(total_comanda) as total, COUNT(id_pago) as n_pagos
        FROM purchases
        WHERE DATE(fecha_pago) = :fecha
    ");
$stmtTotal_comandas->execute([':fecha' => $_GET['day']]);
$totales_comandas = $stmtTotal_comandas->fetch(PDO::FETCH_ASSOC);


$stmtTotal_ventas = $cnx->prepare("
    SELECT 
        SUM(calc_propina) as propinas, 
        SUM(total) as total,
        
        -- Total efectivo
        SUM(CASE 
            WHEN tipo_pago = 'efectivo' AND recibido < total AND n_cuenta = 0 
                THEN recibido - calc_propina
            WHEN tipo_pago = 'efectivo' 
                THEN total - calc_propina
            ELSE 0 
        END) as total_efectivo,
        
        -- Total tarjeta
        SUM(CASE 
            WHEN tipo_pago = 'tarjeta' AND total = 0 AND n_cuenta = 0 
                THEN recibido - calc_propina
            WHEN tipo_pago = 'tarjeta' AND (total > 0 OR n_cuenta > 0)
                THEN total - calc_propina
            ELSE 0 
        END) as total_tarjeta,
        
        -- Propinas por método
        SUM(CASE 
            WHEN tipo_pago = 'efectivo' THEN calc_propina
            ELSE 0 
        END) as propina_efectivo,
        
        SUM(CASE 
            WHEN tipo_pago = 'tarjeta' THEN calc_propina
            ELSE 0 
        END) as propina_tarjeta,
        
        -- Contadores
        COUNT(CASE WHEN tipo_pago = 'efectivo' THEN 1 END) as count_efectivo,
        COUNT(CASE WHEN tipo_pago = 'tarjeta' THEN 1 END) as count_tarjeta,
        COUNT(CASE WHEN tipo_pago NOT IN ('efectivo', 'tarjeta') THEN 1 END) as count_otros

    FROM ventas
    WHERE DATE(fecha) = :fecha
");
$stmtTotal_ventas->execute([':fecha' => $_GET['day']]);
$totales_ventas = $stmtTotal_ventas->fetch(PDO::FETCH_ASSOC);


// Obtener todos los pagos de la fecha especificada
$stmt = $cnx->prepare("
        SELECT c.*, u.name, p.total_comanda, p.id_pago
        FROM purchases p
        LEFT JOIN comandas c ON p.comanda_id = c.id
        LEFT JOIN usuarios u ON c.user_id = u.username
        WHERE DATE(p.fecha_pago) = :fecha
        ORDER BY c.id ASC
    ");
$stmt->execute([':fecha' => $_GET['day']]);
$detalles_comanda = $stmt->fetchAll(PDO::FETCH_ASSOC);


$tarjeta = $cnx->prepare("SELECT * FROM ventas WHERE tipo_pago = 'tarjeta' AND DATE(fecha) = :fecha ORDER BY fecha ASC");
$tarjeta->execute([':fecha' => $_GET['day']]);
$pagos_tarjeta = $tarjeta->fetchAll(PDO::FETCH_ASSOC);



$efectivo = $cnx->prepare("SELECT * FROM ventas WHERE tipo_pago = 'efectivo' AND DATE(fecha) = :fecha ORDER BY fecha ASC");
$efectivo->execute([':fecha' => $_GET['day']]);
$pagos_efectivo = $efectivo->fetchAll(PDO::FETCH_ASSOC);



$meseros = $cnx->prepare("SELECT * FROM usuarios WHERE rol = 'Mesero'");
$meseros->execute();
$detalles_meseros = $meseros->fetchAll(PDO::FETCH_ASSOC);


class PDF extends FPDF
{
    // Encabezado de página
    function Header()
    {
        // Logo
        $this->Image('../../img/elretono-logo.jpg', 10, 8, 33);

        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);

        // Título
        $this->setXY(45, 8);
        $this->Cell(0, 10, utf8_decode('Reporte de Ventas - Restaurante "El Retoño"'), 0, 1, 'L');
        // Subtítulo o información adicional
        $this->SetFont('Arial', '', 10);
        $this->setXY(45, 16);
        $this->Cell(0, 6, 'Reforma 46, Colonia Centro, Parras de la Fuente, Coahuila, 27980.', 0, 1, 'L');
        $this->setX(45);
        $this->Cell(0, 6, 'Tel: 842 148 4636', 0, 1, 'L');

        // Línea separadora
        $this->Line(10, 30, 205, 30);

        // Salto de línea
        $this->Ln(10);
    }

    // Pie de página
    function Footer()
    {
        // Posición a 1.5 cm del final
        $this->SetY(-15);

        // Línea separadora
        $this->SetDrawColor(0, 0, 0);
        $this->Line(10, $this->GetY(), 205, $this->GetY());

        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);

        // Fecha y hora
        $this->SetY(-15);
        $this->SetX(10);
        $this->Cell(0, 10, '   ' . date('d/m/Y H:i:s'), 0, 0, 'L');

        // Fecha y hora
        $this->SetY(-15);
        $this->SetX(10);
        $this->Cell(0, 10, 'KAIDA SYSTEM', 0, 0, 'C');

        // Numero de Pagina
        $this->SetY(-15);
        $this->SetX(10);
        $this->Cell(0, 10, utf8_decode('Página ' . $this->PageNo() . '/{nb}'), 0, 0, 'R');
    }
}

// Crear instancia del PDF
$pdf = new PDF('P', 'mm', 'Letter'); // 'P' = Portrait, 'Letter' = tamaño carta
$pdf->AliasNbPages(); // Para el número total de páginas
$pdf->AddPage();

// Configurar fuente para el contenido
$pdf->SetFont('helvetica', 'B', 12);

// Contenido del documento
$pdf->Cell(0, 10, 'Ventas del ' . $fecha_ventas, 0, 1, 'C');
$pdf->Ln(5);

// Ejemplo de tabla o contenido 63 width
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(0, 0, 0);
$pdf->SetDrawColor(232, 96, 77);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(30, 10, 'Comandas', 1, 0, 'C', true);
$pdf->Cell(5, 10, '', 0, 0, 'C', false);
$pdf->Cell(50, 10, 'Ingreso Neto', 1, 0, 'C', true);
$pdf->Cell(5, 10, '', 0, 0, 'C', false);
$pdf->Cell(50, 10, 'Propinas', 1, 0, 'C', true);
$pdf->Cell(5, 10, '', 0, 0, 'C', false);
$pdf->Cell(50, 10, 'Ingreso Total', 1, 1, 'C', true);

$pdf->SetFont('courier', 'B', 11);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(30, 8, $totales_comandas['n_pagos'], 1, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(50, 8, '$' . number_format($totales_comandas['total'], 2), 1, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(50, 8, '$ ' . number_format($totales_ventas['propinas'], 2), 1, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(50, 8, '$ ' . number_format($totales_ventas['total'], 2), 1, 1, 'C');

$pdf->Ln(10);
$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, 'Pagos con Tarjeta', 1, 1, 'C', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(30, 8, 'Pagos', 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(50, 8, 'Ingreso Neto', 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(50, 8, 'Propinas', 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(50, 8, 'Ingreso Total', 0, 1, 'C');

$pdf->SetFont('courier', 'B', 11);
$pdf->Cell(30, 8, $totales_ventas['count_tarjeta'], 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(50, 8, '$' . number_format($totales_ventas['total_tarjeta'], 2), 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(50, 8, '$ ' . number_format($totales_ventas['propina_tarjeta'], 2), 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(50, 8, '$ ' . number_format($totales_ventas['total_tarjeta'] + $totales_ventas['propina_tarjeta'], 2), 0, 1, 'C');


$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(0, 8, '- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - Desglose - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(20, 8, '#', 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(16, 8, 'Folio', 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(35, 8, 'Cuenta', 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(35, 8, 'Propina', 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(35, 8, 'Total', 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(29, 8, 'Hora', 0, 1, 'C');

$pdf->SetFont('courier', 'B', 11);
$npt = 1;
if ($pagos_tarjeta) {
    foreach ($pagos_tarjeta as $pt) {
        if ($pt['total'] == 0 && $pt['n_cuenta'] == 0) {
            $cantidad = $pt['recibido'] - $pt['calc_propina'];
        } else if ($pt['total'] > 0 && $pt['n_cuenta'] == 0) {
            $cantidad = $pt['total'] - $pt['calc_propina'];
        } else if ($pt['n_cuenta'] > 0) {
            $cantidad = $pt['total'] - $pt['calc_propina'];
        }

        $pdf->Cell(20, 8, $npt, 0, 0, 'C');
        $pdf->Cell(5, 8, '', 0, 0, 'C', false);
        $pdf->Cell(16, 8, $pt['id_comanda'], 0, 0, 'C');
        $pdf->Cell(5, 8, '', 0, 0, 'C', false);
        $pdf->Cell(35, 8, '$' . number_format($cantidad, 2), 0, 0, 'C');
        $pdf->Cell(5, 8, '', 0, 0, 'C', false);
        $pdf->Cell(35, 8, $pt['calc_propina'] > 0 ? '$' . number_format($pt['calc_propina'], 2) : '$0.00', 0, 0, 'C');
        $pdf->Cell(5, 8, '', 0, 0, 'C', false);
        $pdf->Cell(35, 8, '$ ' . number_format(($cantidad + $pt['calc_propina']), 2), 0, 0, 'C');
        $pdf->Cell(5, 8, '', 0, 0, 'C', false);
        $pdf->Cell(29, 8, date('H:i:s', strtotime($pt['fecha'])), 0, 1, 'C');
        $npt++;
    }
}




$pdf->Ln(10);
$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, 'Pagos en Efectivo', 1, 1, 'C', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(30, 8, 'Pagos', 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(50, 8, 'Ingreso Neto', 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(50, 8, 'Propinas', 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(50, 8, 'Ingreso Total', 0, 1, 'C');

$pdf->SetFont('courier', 'B', 11);
$pdf->Cell(30, 8, $totales_ventas['count_efectivo'], 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(50, 8, '$' . number_format($totales_ventas['total_efectivo'], 2), 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(50, 8, '$ ' . number_format($totales_ventas['propina_efectivo'], 2), 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(50, 8, '$ ' . number_format($totales_ventas['total_efectivo'] + $totales_ventas['propina_efectivo'], 2), 0, 1, 'C');

$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(0, 8, '- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - Desglose - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(15, 8, '#', 0, 0, 'C');
$pdf->Cell(3, 8, '', 0, 0, 'C', false);
$pdf->Cell(16, 8, 'Folio', 0, 0, 'C');
$pdf->Cell(3, 8, '', 0, 0, 'C', false);
$pdf->Cell(30, 8, 'Cuenta', 0, 0, 'C');
$pdf->Cell(3, 8, '', 0, 0, 'C', false);
$pdf->Cell(30, 8, 'Propina', 0, 0, 'C');
$pdf->Cell(3, 8, '', 0, 0, 'C', false);
$pdf->Cell(30, 8, 'Total', 0, 0, 'C');
$pdf->Cell(3, 8, '', 0, 0, 'C', false);
$pdf->Cell(24, 8, 'Recibido', 0, 0, 'C');
$pdf->Cell(3, 8, '', 0, 0, 'C', false);
$pdf->Cell(38, 8, 'Cambio', 0, 1, 'C');



$pdf->SetFont('courier', 'B', 11);
$npe = 1;
if ($pagos_efectivo) {
    foreach ($pagos_efectivo as $pe) {
        if ($pe['recibido'] < $pe['total'] && $pe['n_cuenta'] == 0) {
            $cantidad = $pe['recibido'] - $pe['calc_propina'];
        } else {
            $cantidad = $pe['total'] - $pe['calc_propina'];
        }

        $pdf->Cell(15, 8, $npe, 0, 0, 'C');
        $pdf->Cell(3, 8, '', 0, 0, 'C', false);
        $pdf->Cell(16, 8, $pe['id_comanda'], 0, 0, 'C');
        $pdf->Cell(3, 8, '', 0, 0, 'C', false);
        $pdf->Cell(30, 8, '$' . number_format($cantidad, 2), 0, 0, 'C');
        $pdf->Cell(3, 8, '', 0, 0, 'C', false);
        $pdf->Cell(30, 8, $pe['calc_propina'] > 0 ? '$' . number_format($pe['calc_propina'], 2) : '$0.00', 0, 0, 'C');
        $pdf->Cell(3, 8, '', 0, 0, 'C', false);
        $pdf->Cell(30, 8, '$ ' . number_format(($cantidad + $pe['calc_propina']), 2), 0, 0, 'C');
        $pdf->Cell(3, 8, '', 0, 0, 'C', false);
        $pdf->Cell(24, 8, '$' . number_format($pe['recibido'], 2), 0, 0, 'C');
        $pdf->Cell(3, 8, '', 0, 0, 'C', false);
        $pdf->Cell(38, 8, '$' . number_format($pe['cambio'], 2), 0, 1, 'C');

        $npe++;
    }
}




$pdf->AddPage();

$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, 'Meseros: Ventas y Propinas', 1, 1, 'C', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(55, 8, 'Mesero', 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(30, 8, 'Comandas', 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(30, 8, 'Ingresos', 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(30, 8, 'Propinas', 0, 0, 'C');
$pdf->Cell(5, 8, '', 0, 0, 'C', false);
$pdf->Cell(30, 8, 'Total', 0, 1, 'C');

$pdf->SetFont('courier', 'B', 11);
$contador_meseros_con_ventas = 0;
if (count($detalles_meseros) > 0) {
    foreach ($detalles_meseros as $mesero) {
        $ventasmeseros = $cnx->prepare("
                                            SELECT SUM(v.total_comanda) AS ingresos, SUM(v.calc_propina) propinas, COUNT(v.id) AS comandas
                                            FROM ventas v
                                            LEFT JOIN comandas c ON v.id_comanda = c.id
                                            WHERE c.user_id = :user AND DATE(v.fecha) = :fecha

                                        ");
        $ventasmeseros->execute([':user' => $mesero['username'], ':fecha' => $_GET['day']]);
        $meserosganancias = $ventasmeseros->fetch(PDO::FETCH_ASSOC);

        $cm = $cnx->prepare("SELECT COUNT(p.id_pago) AS atendidos FROM purchases p LEFT JOIN comandas c ON p.comanda_id = c.id WHERE c.user_id = :user AND DATE(p.fecha_pago) = :fecha");
        $cm->execute([':user' => $mesero['username'], ':fecha' => $_GET['day']]);
        $arraycm = $cm->fetch(PDO::FETCH_ASSOC);

        if ($meserosganancias && $meserosganancias['comandas'] > 0) {
            $contador_meseros_con_ventas++;

            $pdf->Cell(55, 8, $mesero['name'], 0, 0, 'C');
            $pdf->Cell(5, 8, '', 0, 0, 'C', false);
            $pdf->Cell(30, 8, $arraycm['atendidos'], 0, 0, 'C');
            $pdf->Cell(5, 8, '', 0, 0, 'C', false);
            $pdf->Cell(30, 8, '$' . number_format($meserosganancias['ingresos'], 2), 0, 0, 'C');
            $pdf->Cell(5, 8, '', 0, 0, 'C', false);
            $pdf->Cell(30, 8, '$' . number_format($meserosganancias['propinas'], 2), 0, 0, 'C');
            $pdf->Cell(5, 8, '', 0, 0, 'C', false);
            $pdf->Cell(30, 8, '$' . number_format(($meserosganancias['propinas'] + $meserosganancias['ingresos']), 2), 0, 1, 'C');
        }
    }

    if ($contador_meseros_con_ventas == 0) {
        $pdf->Cell(0, 8, 'Sin Registros', 0, 1, 'C');
    }
} else {
    $pdf->Cell(0, 8, 'Sin Registros', 0, 1, 'C');
}




$pdf->Ln(10);
$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, 'Detalles de Comandas Pagadas', 1, 1, 'C', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(15, 8, 'Folio', 0, 0, 'C');
$pdf->Cell(3, 8, '', 0, 0, 'C', false);
$pdf->Cell(21, 8, 'Entrega a', 0, 0, 'C');
$pdf->Cell(3, 8, '', 0, 0, 'C', false);
$pdf->Cell(35, 8, 'Mesero', 0, 0, 'C');
$pdf->Cell(3, 8, '', 0, 0, 'C', false);
$pdf->Cell(20, 8, 'Cuenta', 0, 0, 'C');
$pdf->Cell(3, 8, '', 0, 0, 'C', false);
$pdf->Cell(20, 8, 'Propina', 0, 0, 'C');
$pdf->Cell(3, 8, '', 0, 0, 'C', false);
$pdf->Cell(24, 8, 'Total', 0, 0, 'C');
$pdf->Cell(3, 8, '', 0, 0, 'C', false);
$pdf->Cell(48, 8, 'Detalles', 0, 1, 'C');

$pdf->SetFont('courier', 'B', 11);
if (count($detalles_comanda) > 0) {
    foreach ($detalles_comanda as $detalle) {
        $tablepagos = $cnx->prepare("SELECT SUM(calc_propina) AS propinas FROM ventas WHERE id_pago = :idpago");
        $tablepagos->execute([':idpago' => $detalle['id_pago']]);
        $resultpagos = $tablepagos->fetch(PDO::FETCH_ASSOC);

        $show = $cnx->prepare("SELECT * FROM ventas WHERE id_pago = :idpago");
        $show->execute([':idpago' => $detalle['id_pago']]);
        $resultshow = $show->fetchAll(PDO::FETCH_ASSOC);

        $pdf->Ln(2);
        $pdf->Cell(0, 2, '', 'T', 1, 'C');
        $pdf->Cell(15, 8, $detalle['id'], '0', 0, 'C');
        $pdf->Cell(3, 8, '', '0', 0, 'C', false);
        $pdf->Cell(21, 8, ($detalle['tipo'] == 'mesa' ? 'Mesa ' . $detalle['mesa'] : $detalle['cliente']), '0', 0, 'C');
        $pdf->Cell(3, 8, '', '0', 0, 'C', false);
        $pdf->Cell(35, 8, $detalle['name'], '0', 0, 'C');
        $pdf->Cell(3, 8, '', '0', 0, 'C', false);
        $pdf->Cell(20, 8, '$'. number_format($detalle['total_comanda'], 2), '0', 0, 'C');
        $pdf->Cell(3, 8, '', '0', 0, 'C', false);
        $pdf->Cell(20, 8, '$'. number_format($resultpagos['propinas'], 2), '0', 0, 'C');
        $pdf->Cell(3, 8, '', '0', 0, 'C', false);
        $pdf->Cell(24, 8, '$'. number_format(($detalle['total_comanda'] + $resultpagos['propinas']), 2), '0', 0, 'C');
        $pdf->Cell(3, 8, '', '0', 0, 'C', false);

        $n = 1;
        if (count($resultshow) > 0) {
            foreach ($resultshow as $show) {
                if ($show['tipo_pago'] == 'efectivo') {
                    if ($show['recibido'] < $show['total'] && $show['n_cuenta'] == 0) {
                        $cantidad = $show['recibido'] - $show['calc_propina'];
                    } else {
                        $cantidad = $show['total'] - $show['calc_propina'];
                    }
                } else if ($show['tipo_pago'] == 'tarjeta') {
                    if ($show['total'] == 0 && $show['n_cuenta'] == 0) {
                        $cantidad = $show['recibido'] - $show['calc_propina'];
                    } else if ($show['total'] > 0 && $show['n_cuenta'] == 0) {
                        $cantidad = $show['total'] - $show['calc_propina'];
                    } else if ($show['n_cuenta'] > 0) {
                        $cantidad = $show['total'] - $show['calc_propina'];
                    }
                }
                $color = $show['tipo_pago'] == 'efectivo' ? 'text-success' : 'text-danger';
                $pdf->MultiCell(0, 8, ucfirst($show['tipo_pago']) . ' $' .number_format(($cantidad + $show['calc_propina']), 2) ,0, 'R');
                $n++;
            }
        }
    }
} else {
    $pdf->Cell(0, 8, 'No hay comandas pagadas', 0, 1, 'C');
}

// Salida del PDF
$pdf->Output('I', 'El retono - Ventas del ' . $fecha_ventas . '.pdf');
?>