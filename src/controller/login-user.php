<?php
session_start();
include('../model/querys.php');

function actualizar($columna) {
    $conexion = new Conexion();
    $sql = $conexion->prepare('UPDATE servicio SET visto_'.$columna.' = 0 WHERE id = 1');
    $sql->execute();
}

$response = array(); // Inicia array para response

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!empty($_POST['log-user']) && !empty($_POST['log-password'])) {
        $usuario = $_POST['log-user'];
        $Contrasena = $_POST["log-password"];

        $loguear = consultar_usuario($usuario);

        if ($loguear) { // Si existe el usuario
            $Contrasena_BD = $loguear['password']; // Trae la contraseña registrada

            if (password_verify($Contrasena, $Contrasena_BD)) { // Si las contraseñas son iguales
                $_SESSION['data-useractive'] = $loguear['username'];
                $_SESSION['type-user'] = $loguear['rol'];

                $response['status'] = 'ACCGRANTED01';
                $response['message'] = 'Acceso Autorizado';

                $rol = $loguear['rol'];

                switch ($rol) {
                    case 'Administrador':
                        actualizar('admin');
                        $response['path'] = 'src/users/administrador/home.php';
                        break;
                    case 'Mesero':
                        $response['path'] = 'src/users/mesero/home.php';
                        break;
                    case 'Cocina':
                        actualizar('cocina');
                        $response['path'] = 'src/users/cocina/home.php';
                        break;
                    case 'Barra':
                        actualizar('barra');
                        $response['path'] = 'src/users/barra/home.php';
                        break;
                    case 'Caja':
                        actualizar('caja');
                        $response['path'] = 'src/users/caja/home.php';
                        break;
                    default:
                        session_destroy();
                        $response['path'] = 'index.php';
                }

            } else {
                $response['status'] = 'ERROR';
                $response['message'] = 'Correo/Contraseña Incorrecta';
            }
        } else {
            $response['status'] = 'ERROR';
            $response['message'] = 'Correo/Contraseña Incorrecta';
        }
    } else {
        $response['status'] = 'WARNING';
        $response['message'] = 'No deje Campo(s) Vacio(s)';
    }
} else {
    $response['status'] = 'ERROR';
    $response['message'] = 'Error de Operación';
}

// Conversion de array a JSON para enviarlo al cliente
echo json_encode($response);
?>