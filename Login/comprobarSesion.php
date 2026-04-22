<?php
    session_start();
    if (!isset($_SESSION['user'])) {
        // Guardar la página actual para regresar aquí después del login
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if ($uri === false || $uri === null) {
            $uri = $_SERVER['REQUEST_URI'];
        }
        $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        if ($base !== '' && $base !== '/' && strpos($uri, $base) === 0) {
            $rel = substr($uri, strlen($base));
            $rel = ltrim($rel, '/');
        } else {
            $rel = ltrim($uri, '/');
        }
        if ($rel === '' || $rel === false) {
            $rel = 'index.php';
        }
        if ($query !== false && $query !== null) {
            $rel .= '?' . $query;
        }
        $_SESSION['redirect_after_login'] = $rel;

        if(file_exists('login.php')){
            header('location: login.php');
        }else{
            header('location: Login/login.php');
        }
        exit();
    }
?>
