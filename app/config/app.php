<?php
/**
 * Configuración general de la aplicación
 * SIGTAE — Sistema de Gestión de Tareas (Laboratorio de Metrología)
 */
return [
    'name' => 'SIGTAE',
    'env' => 'development',
    'timezone' => 'America/Mexico_City',
    'storage_path' => dirname(__DIR__, 2) . '/storage/json',
    'upload_path' => dirname(__DIR__, 2) . '/storage/uploads',
    'session_name' => 'sigtae_session',
];
