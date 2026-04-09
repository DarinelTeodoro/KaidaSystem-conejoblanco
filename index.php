<?php
session_start();
include('cdn.html');

if (!empty($_SESSION['data-useractive'])) {
    echo '<script>window.location.href = "src/users/administrador/home.php";</script>';
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <title>Conejo Blanco</title>
    <link rel="manifest" href="manifest.webmanifest">
    <link href="style.css" rel="stylesheet">

    <style>
        /* contenedor principal (only this view)*/
        body {
            background: rgb(0, 0, 0, 0.6) url('img/background-tequilas.webp') center center / cover no-repeat;
            background-blend-mode: darken;
        }

        .container_main_login {
            height: 100%;
            width: 100%;
            
            display: flex;
            align-items: center;
            justify-content: center;

        }

        .container_login {
            height: 500px;
            width: 400px;
            padding: 0px 20px;

            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;

            background: rgb(255, 255, 255);
            box-shadow: rgba(50, 50, 93, 0.25) 0px 2px 5px -1px, rgba(0, 0, 0, 0.3) 0px 1px 3px -1px;
            border-radius: 10px;
            transition: 0.2s;
        }

        .img_logo_login {
            height: 160px;
            /*filter: drop-shadow(0px 0px 1px #000000);*/
        }


        /*Responsive*/
        @media (height < 600px) {
            .container_login {
                height: 100%;
                border-radius: 0;
                box-shadow: none;
            }
        }

        @media (height < 437.4px) {
            .container_main_login {
                height: auto;
            }

            .container_login {
                padding: 20px;
                border-radius: 0;
            }
        }

        @media (width < 500px) {
            .container_login {
                width: 100%;
                border-radius: 0;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="container_main_login">
        <div class="container_login">
            <div class="img_logo_login d-flex align-items-center justify-content-center" align="center">
                <img src="img/shortlogo-nbg.png" class="img_logo_login">
            </div>
            <div align="center"><strong class="title-login">Iniciar Sesión</strong></div>
            <form method="post" action="" class="container-fluid mb-0" id="login_form">
                <div class="d-grid mb-2">
                    <label for="log-user">Usuario</label>
                    <input type="text" name="log-user" id="log-user" required autofocus>
                </div>
                <div class="d-grid mb-3">
                    <label for="log-password">Contraseña</label>
                    <input type="password" name="log-password" id="log-password" required>
                </div>

                <div class="d-grid">
                    <button class="btn_execute">Entrar</button>
                </div>
            </form>
        </div>
    </div>

    <!--Contenedor Screen Alert-->
    <div class="container_main_alert fixed-top" id="container_main_alert">
        <div class="container_alert">
            <div class="head_alert">
                <span id="text_title_alert">¡ Alerta !</span>
            </div>
            <div class="body_alert">
                <div class="mb-3"><span id="text_message_alert">Mensaje Alerta</span></div>
                <div id="container-btn-acept"><button type="button" class="btn_accept_alert" id="acept_alert"
                        onclick="hide_alert()">Aceptar</button></div>
            </div>
        </div>
    </div>
</body>

</html>

<script src="script.js"></script>
<script>
    $('#login_form').submit(function (event) {
        event.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            type: 'POST',
            url: 'src/controller/login-user.php',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'ACCGRANTED01') {
                    $('#log-user').val('');
                    $('#log-password').val('');
                    window.location.href = response.path;
                } else {
                    show_alert(response.status,response.message);
                    $('#log-user').val('');
                    $('#log-password').val('');
                }
            }
        });
    });
</script>