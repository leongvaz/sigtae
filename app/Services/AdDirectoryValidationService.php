<?php
namespace App\Services;

/**
 * Validación contra el mismo endpoint usado en Login/login.class.php (Directorio Activo CFE).
 */
class AdDirectoryValidationService
{
    private array $config;

    public function __construct(array $appConfig)
    {
        $this->config = $appConfig;
    }

    public function isEnabled(): bool
    {
        $ad = $this->config['ad_validation'] ?? [];
        return !empty($ad['enabled']) && trim((string)($ad['url'] ?? '')) !== '';
    }

    /**
     * @return array{outcome: 'ok'}|array{outcome: 'invalid'}|array{outcome: 'error', message: string}
     */
    public function validate(string $rpe, string $password): array
    {
        if (!$this->isEnabled()) {
            return ['outcome' => 'error', 'message' => 'Validación AD deshabilitada.'];
        }

        if (!function_exists('curl_init')) {
            return ['outcome' => 'error', 'message' => 'Extensión cURL no disponible en el servidor.'];
        }

        $ad = $this->config['ad_validation'];
        $url = trim((string)$ad['url']);
        $timeout = max(5, (int)($ad['timeout_seconds'] ?? 15));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'rpe' => $rpe,
            'psw' => $password,
        ]));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $errstr = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $errno !== 0) {
            return [
                'outcome' => 'error',
                'message' => $errstr !== '' ? $errstr : 'Error de conexión con el servicio de autenticación.',
            ];
        }

        $decoded = json_decode($raw);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return ['outcome' => 'error', 'message' => 'Respuesta inválida del servicio de autenticación.'];
        }

        $success = false;
        if (is_object($decoded) && isset($decoded->success)) {
            $success = $decoded->success === true || $decoded->success === 'true' || $decoded->success === 1;
        } elseif (is_array($decoded) && array_key_exists('success', $decoded)) {
            $v = $decoded['success'];
            $success = $v === true || $v === 'true' || $v === 1;
        }

        return $success ? ['outcome' => 'ok'] : ['outcome' => 'invalid'];
    }
}
