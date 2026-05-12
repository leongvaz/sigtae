<?php
namespace App\Services;

use App\Core\JsonStorage;

class ProgramaCalendarioService
{
    private JsonStorage $storage;

    public function __construct(JsonStorage $storage)
    {
        $this->storage = $storage;
        $this->storage->ensureExists($this->defaultCalendario((int)date('Y')));
    }

    public function getCalendario(): array
    {
        $year = (int)date('Y');
        $cal = $this->storage->read($this->defaultCalendario($year));
        if (!is_array($cal)) {
            $cal = $this->defaultCalendario($year);
        }
        return $cal;
    }

    /**
     * Feriados que bloquean días hábiles por defecto (solo tipo 0 = ley).
     *
     * @return string[] fechas Y-m-d
     */
    public function getFeriadosLey(): array
    {
        $cal = $this->getCalendario();
        // Compatibilidad con versión anterior: `feriados`
        if (!empty($cal['feriados']) && is_array($cal['feriados'])) {
            return array_values(array_filter($cal['feriados'], function ($x) {
                return is_string($x) && trim($x) !== '';
            }));
        }
        $out = [];
        foreach ((array)($cal['descansos'] ?? []) as $d) {
            $tipo = (int)($d['tipo'] ?? 0);
            $fecha = (string)($d['fecha'] ?? '');
            if ($tipo === 0 && $fecha !== '') {
                $out[] = $fecha;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * @return string[] fechas Y-m-d
     */
    public function generatePeriodColumns(
        string $fechaInicio,
        string $fechaFin,
        string $frecuencia = 'diario'
    ): array {
        $inicio = \DateTimeImmutable::createFromFormat('Y-m-d', $fechaInicio);
        $fin = \DateTimeImmutable::createFromFormat('Y-m-d', $fechaFin);
        if (!$inicio || !$fin || $inicio > $fin) return [];

        $freq = strtolower(trim($frecuencia));
        $stepDays = 1;
        if ($freq === 'semanal') {
            $stepDays = 7;
        } elseif ($freq === 'catorcenal') {
            $stepDays = 14;
        } elseif ($freq === 'mensual') {
            // aproximación para primera versión sin librerías extra
            $stepDays = 30;
        }

        $feriados = $this->getFeriadosLey();
        $feriadosMap = array_fill_keys($feriados, true);

        $out = [];
        $cursor = $inicio;
        $iter = 0;
        while ($cursor <= $fin && $iter < 2000) {
            $f = $cursor->format('Y-m-d');
            if ($this->isLaborable($f, $feriadosMap)) {
                $out[] = $f;
                $cursor = $cursor->modify('+' . $stepDays . ' day');
            } else {
                $cursor = $cursor->modify('+1 day');
            }
            $iter++;
        }

        // Asegura que mensual/semanal no salten demasiado si caen en no laborable.
        if ($stepDays > 1) {
            $normalized = [];
            foreach ($out as $f) {
                $nf = $this->nextLaborable($f, $feriadosMap, $fin->format('Y-m-d'));
                if ($nf !== '' && !in_array($nf, $normalized, true)) $normalized[] = $nf;
            }
            $out = $normalized;
        }

        sort($out);
        return $out;
    }

    /**
     * Columnas diarias: cada día hábil dentro del rango (L-V, excluyendo ley tipo 0).
     *
     * @return string[] fechas Y-m-d
     */
    public function generateDailyColumns(string $fechaInicio, string $fechaFin): array
    {
        $inicio = \DateTimeImmutable::createFromFormat('Y-m-d', $fechaInicio);
        $fin = \DateTimeImmutable::createFromFormat('Y-m-d', $fechaFin);
        if (!$inicio || !$fin || $inicio > $fin) return [];

        $feriados = $this->getFeriadosLey();
        $feriadosMap = array_fill_keys($feriados, true);

        $out = [];
        $cursor = $inicio;
        $iter = 0;
        while ($cursor <= $fin && $iter < 4000) {
            $f = $cursor->format('Y-m-d');
            if ($this->isLaborable($f, $feriadosMap)) {
                $out[] = $f;
            }
            $cursor = $cursor->modify('+1 day');
            $iter++;
        }
        return $out;
    }

    private function isLaborable(string $fecha, array $feriadosMap): bool
    {
        if (isset($feriadosMap[$fecha])) return false;
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $fecha);
        if (!$dt) return false;
        $dow = (int)$dt->format('N'); // 1..7
        return $dow >= 1 && $dow <= 5;
    }

    private function nextLaborable(string $fecha, array $feriadosMap, string $maxFecha): string
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $fecha);
        $max = \DateTimeImmutable::createFromFormat('Y-m-d', $maxFecha);
        if (!$dt || !$max) return '';
        $iter = 0;
        while ($dt <= $max && $iter < 60) {
            $f = $dt->format('Y-m-d');
            if ($this->isLaborable($f, $feriadosMap)) return $f;
            $dt = $dt->modify('+1 day');
            $iter++;
        }
        return '';
    }

    private function defaultCalendario(int $year): array
    {
        return [
            'timezone' => 'America/Mexico_City',
            'description' => 'Calendario laboral: descansos de ley (tipo 0) y contractuales (tipo 1). Solo tipo 0 bloquea días hábiles por defecto.',
            'descansos' => [
                ['id' => 1, 'fecha' => $year . '-01-01', 'tipo' => 0],
                ['id' => 2, 'fecha' => $year . '-05-01', 'tipo' => 0],
                ['id' => 3, 'fecha' => $year . '-09-16', 'tipo' => 0],
                ['id' => 4, 'fecha' => $year . '-12-25', 'tipo' => 0],
            ],
        ];
    }
}

