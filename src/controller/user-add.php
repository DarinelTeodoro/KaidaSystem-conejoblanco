<?php
include('../model/querys.php');
$response = array();
$directorio_destino = "../files/img_users/";

$query_user = consultar_usuario($_POST['username_newuser']);

if ($query_user) {
    $response['status'] = 'ERROR';
    $response['message'] = 'El usuario ya esta dado de alta';
} else {
    if ($_FILES['img_newuser']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['img_newuser'];
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

    $username_user = $_POST['username_newuser'];
    $password = $_POST['password_newuser'];
    $passwordHashed = password_hash($password, PASSWORD_DEFAULT);
    $name_user = $_POST['name_newuser'];
    $rol_user = $_POST['rol_newuser'];


    $conexion = new Conexion();
    $insert_user = $conexion->prepare('INSERT INTO usuarios(name, username, password, rol, photo) VALUES (:nombre, :usuario, :psw, :rol, :foto)');
    $insert_user->bindParam(':nombre', $name_user);
    $insert_user->bindParam(':usuario', $username_user);
    $insert_user->bindParam(':psw', $passwordHashed);
    $insert_user->bindParam(':rol', $rol_user);
    $insert_user->bindParam(':foto', $insert_name_img);
    $insert_user->execute();

    if ($insert_user) {
        $response['status'] = 'EXITO';
        $response['message'] = 'Usuario Creado';
    } else {
        $response['status'] = 'ERROR';
        $response['message'] = 'No se pudo cargar la información del usuario. Intente de nuevo.';
    }
}

echo json_encode($response);
?>