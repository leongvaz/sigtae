<?php
/**
 * Carga sesión y verifica inactividad. Incluir al inicio de cada endpoint.
 */
require_once dirname(__DIR__) . '/Login/comprobarSession.php';
require_once dirname(__DIR__) . '/Login/inactividad.php';
