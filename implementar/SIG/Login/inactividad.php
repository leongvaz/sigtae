<?php
/**
 * Incluir después de comprobarSession en páginas protegidas.
 * Cierra sesión si han pasado >= 30 min (1800s) desde ultimoAcceso.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$timeout = 1800; // 30 min
$now = time();
$ultimo = (int)($_SESSION['ultimoAcceso'] ?? 0);
if ($ultimo > 0 && ($now - $ultimo) >= $timeout) {
    session_destroy();
    if (php_sapi_name() !== 'cli' && (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false)) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Sesión expirada']);
        exit;
    }
    $base = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    if ($base === '/' || $base === '\\') {
        $base = '';
    }
    header('Location: ' . $base . '/Login/login.php?expired=1');
    exit;
}
$_SESSION['ultimoAcceso'] = $now;
