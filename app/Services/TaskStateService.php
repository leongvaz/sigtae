<?php
namespace App\Services;

/**
 * Cálculo automático del estado de la tarea según reglas de negocio.
 * - Asignada: recién creada
 * - En proceso: dentro del plazo, sin evidencia
 * - Incumplimiento: venció y no hay evidencia
 * - Vencida: evidencia subida después del vencimiento
 * - Atendida: evidencia subida dentro del tiempo
 */
class TaskStateService
{
    private string $today;

    public function __construct(?string $today = null)
    {
        $this->today = $today ?? (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('Y-m-d');
    }

    /**
     * Calcula el estado actual de la tarea y días restantes.
     */
    public function computeState(array $task): array
    {
        $fechaLimite = $task['fecha_limite'] ?? null;
        $evidencias = $task['evidencias'] ?? [];
        $hasEvidence = count($evidencias) > 0;
        $diasRestantes = $this->diasRestantes($fechaLimite);

        $estado = 'asignada';
        if ($fechaLimite) {
            $vencido = $this->today > $fechaLimite;
            if ($hasEvidence) {
                $primeraEvidencia = $this->fechaPrimeraEvidencia($evidencias);
                if ($primeraEvidencia && $primeraEvidencia <= $fechaLimite) {
                    $estado = 'atendida';
                } else {
                    $estado = 'vencida';
                }
            } else {
                if ($vencido) {
                    $estado = 'incumplimiento';
                } else {
                    $estado = 'en_proceso';
                }
            }
        } else {
            if ($hasEvidence) {
                $estado = 'atendida';
            } else {
                $estado = 'en_proceso';
            }
        }

        $task['estado'] = $estado;
        $task['dias_restantes'] = $diasRestantes;
        return $task;
    }

    /**
     * Porcentaje de cumplimiento por tarea:
     * Atendida en tiempo = 100%, Atendida fuera de tiempo = 50%, No atendida = 0%
     */
    public function porcentajeCumplimiento(array $task): float
    {
        $estado = $task['estado'] ?? '';
        $fechaLimite = $task['fecha_limite'] ?? null;
        $evidencias = $task['evidencias'] ?? [];

        if (count($evidencias) === 0) {
            return 0.0;
        }
        $primeraEvidencia = $this->fechaPrimeraEvidencia($evidencias);
        if (!$primeraEvidencia || !$fechaLimite) {
            return 100.0;
        }
        return $primeraEvidencia <= $fechaLimite ? 100.0 : 50.0;
    }

    public function diasRestantes(?string $fechaLimite): ?int
    {
        if (!$fechaLimite) {
            return null;
        }
        $limite = \DateTimeImmutable::createFromFormat('Y-m-d', $fechaLimite);
        $hoy = \DateTimeImmutable::createFromFormat('Y-m-d', $this->today);
        if (!$limite || !$hoy) {
            return null;
        }
        $diff = $hoy->diff($limite);
        return (int) $diff->format('%r%a');
    }

    private function fechaPrimeraEvidencia(array $evidencias): ?string
    {
        $fechas = [];
        foreach ($evidencias as $e) {
            $f = $e['fecha_subida'] ?? null;
            if ($f) {
                $fechas[] = substr($f, 0, 10);
            }
        }
        sort($fechas);
        return $fechas[0] ?? null;
    }
}
