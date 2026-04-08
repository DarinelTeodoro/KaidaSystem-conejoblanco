<?php
include('../model/querys.php');

header('Content-Type: application/json');

$array_categorias = data_categorias_aux();
$array_productos   = data_productos();
$array_combos      = data_combos();

// ── Combos ──────────────────────────────────────────────────────────────────
$combos = [];
if ($array_combos) {
    foreach ($array_combos as $combo) {
        $combos[] = [
            'id'             => (int) $combo['id'],
            'nombre'         => $combo['combo'],
            'precio'         => (float) $combo['precio'],
            'disponibilidad' => (int) $combo['disponibilidad'], // 0=disponible, 1=agotado
        ];
    }
}

// ── Productos agrupados por categoría ────────────────────────────────────────
$grupoProductos = [];
if ($array_productos) {
    foreach ($array_productos as $p) {
        $grupoProductos[$p['id_categoria']][] = $p;
    }
}

$categorias = [];
if ($array_categorias) {
    foreach ($array_categorias as $categoria) {
        $catId    = $categoria['id'];
        $productos = $grupoProductos[$catId] ?? [];

        if (!$productos) continue;

        $items = [];
        foreach ($productos as $producto) {
            $variantes   = variantes_producto($producto['id']);
            $n_variantes = $variantes ? count($variantes) : 0;

            $items[] = [
                'id'             => (int) $producto['id'],
                'nombre'         => $producto['producto'],
                'precio'         => (float) $producto['precio'],
                'photo'          => $producto['photo'],
                'disponibilidad' => (int) $producto['disponibilidad'], // 0=disponible, 1=agotado
                'tiene_variantes'=> $n_variantes > 0,
                'destino'        => strtolower($categoria['destino']),
            ];
        }

        $categorias[] = [
            'id'       => $catId,
            'nombre'   => $categoria['categoria'],
            'destino'  => strtolower($categoria['destino']),
            'productos'=> $items,
        ];
    }
}

// ── Hash para detección de cambios ──────────────────────────────────────────
// Se calcula sobre los datos para que cambie solo si hay diferencias reales
$payload = ['combos' => $combos, 'categorias' => $categorias];
$hash    = md5(json_encode($payload));

echo json_encode([
    'hash'       => $hash,
    'combos'     => $combos,
    'categorias' => $categorias,
]);
?>