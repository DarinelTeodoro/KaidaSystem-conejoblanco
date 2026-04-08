<?php
include('../model/querys.php');
$response = array();
$directorio_destino = "../files/img_products/";

$id_producto = $_POST['id_editproducto'];
$producto = $_POST['name_editproducto'];
$descripcion = $_POST['descripcion_editproducto'];
$categoria = $_POST['categoria_editproducto'];
$precio = $_POST['precio_editproducto'];

if ($_FILES['img_editproducto']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['img_editproducto'];
    $nombre_original = $archivo['name'];
    $tipo_temporal = $archivo['tmp_name'];
    $tamano = $archivo['size'];
    $error = $archivo['error'];

    // Extensión y validacion de formato
    $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
    // Generar nombre único para evitar sobreescrituras
    $img_name = uniqid('img_', true) . '.' . $extension;
    $path = $directorio_destino . $img_name;
    // Crear directorio si no existe
    if (!is_dir($directorio_destino)) {
        mkdir($directorio_destino, 0755, true);
    }

    // Mover el archivo temporal a su ubicación final
    if (move_uploaded_file($tipo_temporal, $path)) {
        $img = $img_name;
    }
} else {
    $datatable_producto = consultar_producto($id_producto);
    $img = $datatable_producto['photo'];
}

    $conexion = new Conexion();
    $edit_producto = $conexion->prepare('UPDATE menu_productos SET producto=:producto, descripcion=:descripcion, photo=:foto, precio=:precio, id_categoria=:idcat WHERE id=:idprod');
    $edit_producto->bindParam(':producto', $producto);
    $edit_producto->bindParam(':descripcion', $descripcion);
    $edit_producto->bindParam(':foto', $img);
    $edit_producto->bindParam(':precio', $precio);
    $edit_producto->bindParam(':idcat', $categoria);
    $edit_producto->bindParam(':idprod', $id_producto);
    $ok = $edit_producto->execute();


if ($ok && $edit_producto->rowCount() >= 0) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Datos Actualizados';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo actualizar la información del usuario. Intente de nuevo.';
}

echo json_encode($response);
?>