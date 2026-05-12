<?php
namespace App\Services;

/**
 * Conexión sqlsrv a KCENTINEL (SIGAMI / telepnuevomedidor).
 * Misma instancia que usa el módulo AMI de actualización.
 */
class AmiKcentinelConnection
{
    public const SERVER = '10.4.59.8';

    /**
     * @return resource|false
     */
    public static function connect()
    {
        if (!function_exists('sqlsrv_connect')) {
            return false;
        }
        $connectionOptions = [
            'Database' => 'master',
            'Uid' => 'usrSINAMED',
            'PWD' => 'U$rS1N4M3D2025',
            'CharacterSet' => 'UTF-8',
        ];
        return @call_user_func('sqlsrv_connect', self::SERVER, $connectionOptions);
    }
}
