<?php
namespace App\Services;

use App\Repositories\MetrologiaBitacoraEquipoRepositoryInterface;

/**
 * Importa registros desde Programa 2026.txt (TSV).
 */
class MetrologiaProgramaImportService
{
    private MetrologiaFolioNormalizer $folioNormalizer;
    private MetrologiaZonaService $zonaService;

    public function __construct(
        MetrologiaFolioNormalizer $folioNormalizer,
        MetrologiaZonaService $zonaService
    ) {
        $this->folioNormalizer = $folioNormalizer;
        $this->zonaService = $zonaService;
    }

    /**
     * @return array{ok:bool,message:string,imported:int,rows:list<array<string,mixed>>}
     */
    public function parseFile(string $programaPath): array
    {
        if (!is_file($programaPath)) {
            return ['ok' => false, 'message' => 'No se encontró Programa 2026.txt', 'imported' => 0, 'rows' => []];
        }
        $raw = (string)@file_get_contents($programaPath);
        if ($raw === '') {
            return ['ok' => false, 'message' => 'Programa 2026.txt está vacío.', 'imported' => 0, 'rows' => []];
        }

        $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
        $header = null;
        $idx = [];
        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            if ($header === null && str_starts_with($line, 'DESCRIPCION')) {
                $header = explode("\t", $line);
                foreach ($header as $i => $h) {
                    $idx[$this->normalizeHeaderKey($h)] = $i;
                }
                continue;
            }
            if ($header === null) continue;

            $cols = explode("\t", $line);
            if (count($cols) < 10) continue;

            $get = function (string $key) use ($idx, $cols): string {
                $k = $this->normalizeHeaderKey($key);
                if (!isset($idx[$k])) return '';
                $i = (int)$idx[$k];
                return isset($cols[$i]) ? trim((string)$cols[$i]) : '';
            };

            $zonaRaw = $get('ZONA');
            $zonaNorm = $this->zonaService->normalize($zonaRaw);
            $zonaDisplay = $zonaNorm['zona_display'] ?? $zonaRaw;

            $row = [
                'recepcion_id' => null,
                'folio_recepcion' => null,
                'folio' => $this->folioNormalizer->normalize($get('FOLIO')),
                'descripcion' => $get('DESCRIPCION'),
                'no_serie' => $get('No. SERIE'),
                'marca' => $get('MARCA'),
                'modelo' => $get('MODELO'),
                'zona' => $zonaDisplay,
                'zona_id' => $zonaNorm['id'] ?? '',
                'zona_prefijo' => $zonaNorm['prefijo'] ?? '',
                'area' => $get('AREA'),
                'oficina' => $get('OFICINA'),
                'recibido' => $this->parseDateDMY($get('RECIBIDO')),
                'estado' => 'programado',
                'observaciones' => $get('OBSERVACIONES'),
                'recibe' => '',
                'entrega' => '',
                'source' => 'Programa 2026.txt',
                'tecnico' => $get('TECNICO'),
                'fecha_calibracion_baja' => $this->parseDateDMY($get('FECHA DE CALIBRACION/BAJA')),
                'evaluacion_conformidad' => $get('EVALUACION DE CONFORMIDAD'),
                'fecha_impresion' => $this->parseDateDMY($get('FECHA DE IMPRESION')),
                'fecha_entrega_informe_escaneado' => $this->parseDateDMY($get('FECHA DE ENTREGA DE INFORME ESCANEADO')),
                'entregado' => $this->parseDateDMY($get('ENTREGADO')),
                'nombre_a_quien_se_entrega' => $get('NOMBRE A QUIEN SE ENTREGA'),
                'fecha_programada' => $get('FECHA PROGRAMADA'),
                'tablero_evolutivo' => $get('TABLERO EVOLUTIVO'),
            ];
            $rows[] = $row;
        }

        return [
            'ok' => true,
            'message' => 'Parseado correctamente.',
            'imported' => count($rows),
            'rows' => $rows,
        ];
    }

    /**
     * Reemplazo total de la bitácora desde archivo TSV.
     *
     * @return array{ok:bool,message:string,imported:int}
     */
    public function reimportToRepository(
        string $programaPath,
        MetrologiaBitacoraEquipoRepositoryInterface $bitRepo,
        bool $clearFirst = true
    ): array {
        $parsed = $this->parseFile($programaPath);
        if (!$parsed['ok']) {
            return ['ok' => false, 'message' => $parsed['message'], 'imported' => 0];
        }

        if ($clearFirst) {
            foreach ($bitRepo->findAll() as $existing) {
                $id = (string)($existing['id'] ?? '');
                if ($id !== '') {
                    $bitRepo->delete($id);
                }
            }
        }

        foreach ($parsed['rows'] as $row) {
            $bitRepo->save($row);
        }

        return [
            'ok' => true,
            'message' => 'Importados ' . count($parsed['rows']) . ' registros desde Programa 2026.txt.',
            'imported' => count($parsed['rows']),
        ];
    }

    private function normalizeHeaderKey(string $s): string
    {
        $s = strtoupper(trim($s));
        $map = ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N'];
        return strtr($s, $map);
    }

    private function parseDateDMY(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        return '';
    }
}
