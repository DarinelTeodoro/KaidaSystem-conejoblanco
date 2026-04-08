<?php
include('../model/querys.php');

$producto_id = (int)$_POST['id'];
$producto = consultar_producto($producto_id);
$variantes = variantes_producto($producto_id);

$prodName = $producto ? $producto['producto'] : 'Producto';
$prodPrecio = $producto ? (float)$producto['precio'] : 0;

if ($variantes) {
  foreach ($variantes as $variante) {
    $varId = (int)$variante['id'];
    $varName = $variante['variante'];
    $inc = (float)$variante['incremento'];
    $precioFinal = $prodPrecio + $inc;
    $df = strtolower($variante['destino']);

    if ($variante['disponibilidad'] == 0) {
      $action_var = 'onclick="addvarto_pedido('.$producto_id.','.$varId.',\''. htmlspecialchars($prodName, ENT_QUOTES).'\',\''.htmlspecialchars($varName, ENT_QUOTES).'\','.$prodPrecio.','.$inc.',\''.$df.'\')"';
      $agotado_var = '';
      $style = '';
    } else if ($variante['disponibilidad'] == 1) {
      $action_var = '';
      $agotado_var = '<span class="p-1 fw-bold rounded shadow bg-secondary text-light" style="font-size: 0.8rem;">Agotado</span>' ;
      $style = 'style="text-decoration: line-through; color: rgb(0,0,0,0.6);"';
    }
?>
<div class="d-grid">
  <button type="button" class="btn-select-variante" <?= $action_var ?>>
    <span <?= $style ?>><?= htmlspecialchars($varName) ?> (+$<?= number_format($inc, 2) ?>)</span> <?= $agotado_var ?>
  </button>
</div>
<?php
  }
}
?>
