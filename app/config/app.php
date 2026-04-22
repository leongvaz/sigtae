<?php
/**
 * Configuración general de la aplicación
 * SIGTAE — Sistema de Gestión de Tareas (Laboratorio de Medición)
 */
return [
    'name' => 'SIGTAE',
    'env' => 'development',
    'timezone' => 'America/Mexico_City',
    'storage_path' => dirname(__DIR__, 2) . '/storage/json',
    'upload_path' => dirname(__DIR__, 2) . '/storage/uploads',
    'session_name' => 'sigtae_session',
    // Misma API que Login/login.class.php (Directorio Activo).
    'ad_validation' => [
        'enabled' => true,
        'url' => 'http://api.dvmc.cfemex.com/ad/validacion',
        'timeout_seconds' => 15,
    ],
    // Si la API AD no responde (red, caída), permite login con password_hash local (útil en desarrollo).
    // En producción con AD estable, establecer en false.
    'auth_local_password_fallback' => true,
];
