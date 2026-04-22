<?php
session_start();
// Si llegaron al login con ?pagina=X (p. ej. desde JS en captura), guardar destino para después del login
$paginas_permitidas = ['index', 'captura', 'comparativo'];
if (isset($_GET['pagina']) && in_array($_GET['pagina'], $paginas_permitidas, true)) {
    $_SESSION['redirect_after_login'] = $_GET['pagina'] . '.php';
}

require("login.class.php");

if (isset($_POST['submit'])) {
    $user = new LoginUser($_POST['username'], $_POST['password']);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="refresh" content="7200" />

    <title>Excelencia De La Media Tensión :: Inicio de sesión</title>
    <link rel="icon" type="image/ico" href="../imagen/cfe.ico">
    <link rel="stylesheet" href="../assets/login.css" type="text/css" />

    <meta name="robots" content="noindex" />
    <meta http-equiv="cache-control" content="no-cache" />
    <meta http-equiv="pragma" content="no-cache" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">

    <!--font awesome con CDN-->
    <link rel="stylesheet" type="text/css" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css" integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
</head>

<body id="loginBody">
    <div class="container-sm " style="height:100%">
        <div class="row justify-content-center text-center">
            <div class="col-auto">
                <div id="loginBox">
                    <h1 id="logo"><a>
                            <span class="valign-helper"></span>
                            <img src="../assets/img/logo_2021.png">
                        </a></h1>

                    <div id="tituloSMEI">Tablero de Excelencia MT</div>

                    <div id="autreq">
                        <p><?php if (@$user->error) {
                                echo @$user->error;
                            } else {
                                echo "Autenticación requerida";
                            } ?></p>
                    </div>

                    <div class="banner">
                        <small></small>
                        Ingresar con las credenciales del Directorio Activo <b>CFE.MX</b>
                    </div>

                    <form action="" method="post" enctype="multipart/form-data" autocomplete="off">
                        <fieldset>
                            <input type="text" name="username" id="name" value="" placeholder="RPE/RTT del usuario"
                                autocorrect="off" autocapitalize="sentences" pattern="[A-Za-z0-9]{5}" maxlength="5" />
                            <input type="password" name="password" id="pass" placeholder="Contraseña" autocorrect="off" autocapitalize="off">

                            <input class="submit" type="submit" name="submit" value="Iniciar sesión">
                        </fieldset>
                    </form>

                    <div class="banner">
                        <small>Si no puedes ingresar, contacta al Administrador</small>
                    </div>


                </div>
            </div>
        </div>
    </div>




    <!-- jQuery, Popper.js, Bootstrap JS 
    <script src="assets/jquery/jquery-3.3.1.min.js"></script>
    <script src="assets/popper/popper.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>-->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $("input:not(.dp):visible:enabled:first").focus();
        });
    </script>

</body>

</html>