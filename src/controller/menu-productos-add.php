<?php
include('../model/conexion.php');
$response = array();
$directorio_destino = "../files/img_products/";

if ($_FILES['img_addproducto']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['img_addproducto'];
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
        $insert_name_img = $img_name;
    }
} else {
    $insert_name_img = 'default.webp';
}

$producto = $_POST['name_addproducto'];
$descripcion = $_POST['descripcion_addproducto'];
$precio = $_POST['precio_addproducto'];
$categoria = $_POST['categoria_addproducto'];


$conexion = new Conexion();
$insert_producto = $conexion->prepare('INSERT INTO menu_productos(producto, descripcion, photo, precio, id_categoria) VALUES (:producto, :descripcion, :foto, :precio, :categoria)');
$insert_producto->bindParam(':producto', $producto);
$insert_producto->bindParam(':descripcion', $descripcion);
$insert_producto->bindParam(':foto', $insert_name_img);
$insert_producto->bindParam(':precio', $precio);
$insert_producto->bindParam(':categoria', $categoria);
$insert_producto->execute();

if ($insert_producto) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Producto Agregado';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo agregar el producto. Intente de nuevo.';
}

echo json_encode($response);
?>