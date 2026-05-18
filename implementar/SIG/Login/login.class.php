<?php
/**
 * Validación contra API AD + whitelist en data.json
 * Solo proceso=distribucion y estado=1
 */
class Login {
    private const AD_URL = 'http://api.dvmc.cfemex.com/ad/validacion';
    private const LOCAL_TEST_USER = '54456';
    private string $dataPath;

    public function __construct() {
        $this->dataPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'data.json';
    }

    /**
     * @return array{ok: bool, error?: string, user?: array}
     */
    public function validar(string $rpe, string $psw): array {
        $rpe = trim($rpe);
        if ($rpe === '' || $psw === '') {
            return ['ok' => false, 'error' => 'Usuario y contraseña requeridos'];
        }

        // Bypass solo para pruebas locales rapidas.
        if ($this->esUsuarioPruebasLocal($rpe, $psw)) {
            return [
                'ok'   => true,
                'user' => [
                    'user'    => self::LOCAL_TEST_USER,
                    'nombre'  => 'Usuario de Pruebas Local',
                    'zona'    => 'Local',
                    'admin'   => '1',
                    'proceso' => 'distribucion',
                ],
            ];
        }

        // 1) Validar contra AD
        $adOk = $this->validarAD($rpe, $psw);
        if (!$adOk) {
            return ['ok' => false, 'error' => 'Usuario o Password incorrecto'];
        }

        // 2) Whitelist
        $whitelistUser = $this->buscarEnWhitelist(strtoupper($rpe));
        if (!$whitelistUser) {
            return ['ok' => false, 'error' => 'El usuario no tiene permitido el acceso'];
        }
        if ((int)($whitelistUser['estado'] ?? 0) !== 1 || ($whitelistUser['proceso'] ?? '') !== 'distribucion') {
            return ['ok' => false, 'error' => 'El usuario no tiene permitido el acceso'];
        }

        return [
            'ok'   => true,
            'user' => [
                'user'     => $whitelistUser['user'],
                'nombre'   => $whitelistUser['nombre'] ?? '',
                'zona'     => $whitelistUser['zona'] ?? '',
                'admin'    => $whitelistUser['admin'] ?? '0',
                'proceso'  => $whitelistUser['proceso'] ?? 'distribucion',
            ],
        ];
    }

    private function esUsuarioPruebasLocal(string $rpe, string $psw): bool {
        if ($rpe !== self::LOCAL_TEST_USER || $psw !== self::LOCAL_TEST_USER) {
            return false;
        }

        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        $serverAddr = (string)($_SERVER['SERVER_ADDR'] ?? '');
        $remoteAddr = (string)($_SERVER['REMOTE_ADDR'] ?? '');

        if (
            $host !== '' &&
            (strpos($host, 'localhost') !== false || strpos($host, '.test') !== false)
        ) {
            return true;
        }

        $localIps = ['127.0.0.1', '::1'];
        return in_array($serverAddr, $localIps, true) || in_array($remoteAddr, $localIps, true);
    }

    private function validarAD(string $rpe, string $psw): bool {
        $post = http_build_query(['rpe' => $rpe, 'psw' => $psw]);
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $post,
                'timeout' => 10,
            ],
        ]);
        $response = @file_get_contents(self::AD_URL, false, $ctx);
        if ($response === false) {
            return false;
        }
        $data = json_decode($response, true);
        return isset($data['success']) && $data['success'] === true;
    }

    /**
     * @return array|null
     */
    private function buscarEnWhitelist(string $rpeUpper): ?array {
        if (!is_file($this->dataPath)) {
            return null;
        }
        $json = file_get_contents($this->dataPath);
        $data = json_decode($json, true);
        $users = $data['users'] ?? (is_array($data) ? $data : []);
        if (!is_array($users)) {
            $users = [];
        }
        foreach ($users as $u) {
            if (strtoupper((string)($u['user'] ?? '')) === $rpeUpper) {
                return $u;
            }
        }
        return null;
    }
}
