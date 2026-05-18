<?php
/**
 * Incluir al inicio de toda página/endpoint protegido.
 * Redirige a login si no hay sesión válida.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user'])) {
    $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
    if ($isApi || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'No autenticado']);
        exit;
    }
    $base = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    if ($base === '/' || $base === '\\') {
        $base = '';
    }
    header('Location: ' . $base . '/Login/login.php');
    exit;
}
