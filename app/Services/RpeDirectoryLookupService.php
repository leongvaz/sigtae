<?php
namespace App\Services;

/**
 * Consulta de perfil por RPE vía servicio corporativo (proxy server-side).
 */
class RpeDirectoryLookupService
{
    private string $apiBaseUrl;
    private int $timeoutSeconds;

    public function __construct(?string $apiBaseUrl = null, int $timeoutSeconds = 4)
    {
        $base = rtrim($apiBaseUrl ?? 'http://10.4.157.20/api/consulta', '/');
        $this->apiBaseUrl = $base;
        $this->timeoutSeconds = max(2, $timeoutSeconds);
    }

    /**
     * @return array{ok:bool,message?:string,item?:array<string,mixed>,raw?:array<string,mixed>,http_status?:int}
     */
    public function lookup(string $rpe): array
    {
        $rpe = strtoupper(trim($rpe));
        if ($rpe === '' || !preg_match('/^[A-Z0-9]{1,8}$/', $rpe)) {
            return ['ok' => false, 'message' => 'RPE inválido.', 'http_status' => 400];
        }

        $url = $this->apiBaseUrl . '/' . rawurlencode($rpe);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeoutSeconds,
                'header' => "Accept: application/json\r\n",
            ],
        ]);

        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false || trim($raw) === '') {
            return ['ok' => false, 'message' => 'No se pudo consultar el servicio.', 'http_status' => 502];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'Respuesta inválida del servicio.', 'http_status' => 502];
        }

        $codigo = (string)($data['CodigoMensaje'] ?? '');
        if ($codigo !== '' && $codigo !== '0') {
            $msg = (string)($data['Mensaje'] ?? 'Consulta no válida.');
            return ['ok' => false, 'message' => $msg, 'http_status' => 404];
        }

        $nomina = is_array($data['Nomina'] ?? null) ? $data['Nomina'] : [];
        $email = (string)($nomina['EMail'] ?? ($data['Email'] ?? ''));
        $puesto = (string)($nomina['Puesto'] ?? '');
        $nombreCompleto = (string)($nomina['Nombre'] ?? ($data['NombreCompleto'] ?? ''));
        $nombre = trim($nombreCompleto) !== ''
            ? $nombreCompleto
            : trim(((string)($data['Nombre'] ?? '')) . ' ' . ((string)($data['Apellidos'] ?? '')));

        $zona = trim((string)(
            $nomina['Zona']
            ?? $nomina['Division']
            ?? $data['Zona']
            ?? $data['Division']
            ?? ''
        ));
        $area = trim((string)(
            $nomina['Area']
            ?? $nomina['Departamento']
            ?? $nomina['CentroTrabajo']
            ?? $data['Area']
            ?? $data['Departamento']
            ?? ''
        ));

        return [
            'ok' => true,
            'item' => [
                'rpe' => $rpe,
                'nombre' => trim($nombre),
                'email' => trim($email),
                'cargo' => trim($puesto),
                'zona' => $zona,
                'area' => $area,
                'habilitado' => (string)($data['Habilitado'] ?? ''),
                'bloqueado' => (string)($data['Bloqueado'] ?? ''),
            ],
            'raw' => $data,
        ];
    }
}
