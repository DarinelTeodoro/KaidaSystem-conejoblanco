<?php
include('../model/querys.php');
$response = array();
$directorio_destino = "../files/img_users/";

$username = $_POST['user_edituser'];
$name = $_POST['name_edituser'];
$rol = $_POST['rol_edituser'];

if ($_FILES['img_edituser']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['img_edituser'];
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
    $datatable_user = consultar_usuario($username);
    $img = $datatable_user['photo'];
}

if (!empty($_POST['password_edituser'])) {
    $password = $_POST['password_edituser'];
    $passwordHashed = password_hash($password, PASSWORD_DEFAULT);

    $conexion = new Conexion();
    $edit_user = $conexion->prepare('UPDATE usuarios SET name=:nombre, password=:psw, rol=:rol, photo=:foto WHERE username=:usuario');
    $edit_user->bindParam(':nombre', $name);
    $edit_user->bindParam(':psw', $passwordHashed);
    $edit_user->bindParam(':rol', $rol);
    $edit_user->bindParam(':foto', $img);
    $edit_user->bindParam(':usuario', $username);
    $edit_user->execute();
} else {
    $conexion = new Conexion();
    $edit_user = $conexion->prepare('UPDATE usuarios SET name=:nombre, rol=:rol, photo=:foto WHERE username=:usuario');
    $edit_user->bindParam(':nombre', $name);
    $edit_user->bindParam(':rol', $rol);
    $edit_user->bindParam(':foto', $img);
    $edit_user->bindParam(':usuario', $username);
    $edit_user->execute();
}


if ($edit_user) {
    $response['status'] = 'EXITO';
    $response['message'] = 'Datos Actualizados';
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'No se pudo actualizar la información del usuario. Intente de nuevo.';
}

echo json_encode($response);
?>