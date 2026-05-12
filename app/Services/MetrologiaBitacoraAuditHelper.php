<?php
namespace App\Services;

/**
 * Diff de campos editables de bitácora (equipo) para auditoría.
 */
final class MetrologiaBitacoraAuditHelper
{
    /** @var array<string, string> clave JSON => etiqueta UI */
    public const CAMPOS_ETIQUETA = [
        'folio' => 'Folio',
        'no_serie' => 'No. serie',
        'marca' => 'Marca',
        'modelo' => 'Modelo',
        'descripcion' => 'Descripción',
        'zona' => 'Zona',
        'area' => 'Área',
        'oficina' => 'Oficina',
        'observaciones' => 'Observaciones',
        'programa_anual' => 'Programa anual de calibración',
        'recibido' => 'Recibido',
        'tecnico' => 'Técnico',
        'fecha_calibracion_baja' => 'Fecha calibración/baja',
        'evaluacion_conformidad' => 'Evaluación de conformidad',
        'fecha_impresion' => 'Fecha de impresión',
        'fecha_entrega_informe_escaneado' => 'Fecha entrega informe escaneado',
        'entregado' => 'Entregado',
        'nombre_a_quien_se_entrega' => 'Nombre a quien se entrega',
        'nomenclatura_gmcs' => 'Nomenclatura GMCS',
        'jefe_area' => 'Jefe de área',
        'rpe_jefe_area' => 'RPE jefe de área',
        'fecha_programada' => 'Fecha programada',
        'tablero_evolutivo' => 'Tablero evolutivo',
    ];

    private static function normValue(string $campo, string $v): string
    {
        $v = trim($v);
        if ($campo === 'no_serie' || $campo === 'rpe_jefe_area') {
            return strtoupper($v);
        }
        return $v;
    }

    /**
     * @param array<string, mixed> $antes
     * @param array<string, mixed> $despues
     * @return list<array{campo: string, etiqueta: string, anterior: string, nuevo: string}>
     */
    public static function diffCampos(array $antes, array $despues): array
    {
        $out = [];
        foreach (array_keys(self::CAMPOS_ETIQUETA) as $campo) {
            $a = self::normValue($campo, (string)($antes[$campo] ?? ''));
            $b = self::normValue($campo, (string)($despues[$campo] ?? ''));
            if ($a === $b) {
                continue;
            }
            $out[] = [
                'campo' => $campo,
                'etiqueta' => self::CAMPOS_ETIQUETA[$campo] ?? $campo,
                'anterior' => (string)($antes[$campo] ?? ''),
                'nuevo' => (string)($despues[$campo] ?? ''),
            ];
        }
        return $out;
    }
}
