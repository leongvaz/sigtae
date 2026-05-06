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
    private float $graciaPorcentaje;

    public function __construct(?string $today = null, float $graciaPorcentaje = 0.10)
    {
        $this->today = $today ?? (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('Y-m-d');
        $this->graciaPorcentaje = max(0.0, $graciaPorcentaje);
    }

    /**
     * Calcula el estado actual de la tarea y días restantes.
     */
    public function computeState(array $task): array
    {
        if (!empty($task['cancelada'])) {
            $task['estado'] = 'cancelada';
            $task['dias_restantes'] = null;
            return $task;
        }

        $fechaLimite = $task['fecha_limite'] ?? null;
        $fechaLimiteEfectiva = $this->fechaLimiteEfectiva($task);
        $evidencias = $task['evidencias'] ?? [];
        $hasEvidence = count($evidencias) > 0;
        $diasRestantes = $this->diasRestantes($fechaLimiteEfectiva);

        $estado = 'asignada';
        if ($fechaLimiteEfectiva) {
            $vencido = $this->today > $fechaLimiteEfectiva;
            if ($hasEvidence) {
                $primeraEvidencia = $this->primeraFechaEvidencia($evidencias);
                if ($fechaLimite && $primeraEvidencia && $primeraEvidencia <= $fechaLimite) {
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
        $task['fecha_limite_efectiva'] = $fechaLimiteEfectiva;
        return $task;
    }

    /**
     * Porcentaje de cumplimiento por tarea:
     * Atendida en tiempo = 100%, Atendida fuera de tiempo = 50%, No atendida = 0%
     */
    public function porcentajeCumplimiento(array $task): float
    {
        if (!empty($task['cancelada'])) {
            return 0.0;
        }
        $estado = $task['estado'] ?? '';
        $fechaLimite = (string)($task['fecha_limite'] ?? '');
        $evidencias = $task['evidencias'] ?? [];

        if (count($evidencias) === 0) {
            return 0.0;
        }
        $primeraEvidencia = $this->primeraFechaEvidencia($evidencias);
        if (!$primeraEvidencia || $fechaLimite === '') {
            return 100.0;
        }
        return $primeraEvidencia <= $fechaLimite ? 100.0 : 50.0;
    }

    /**
     * Determina la fecha límite efectiva (límite + gracia porcentual).
     */
    public function fechaLimiteEfectiva(array $task): ?string
    {
        // Si fue rechazada, la re-presentación tiene su propia fecha límite.
        $dict = strtolower((string)($task['dictamen'] ?? ''));
        $rechLim = (string)($task['rechazo_fecha_limite'] ?? '');
        if ($dict === 'rechazada' && $rechLim !== '') {
            return $rechLim;
        }

        $fechaLimite = (string)($task['fecha_limite'] ?? '');
        if ($fechaLimite === '') return null;

        $limite = \DateTimeImmutable::createFromFormat('Y-m-d', $fechaLimite);
        if (!$limite) return null;

        $diasGracia = $this->diasGracia($task);
        if ($diasGracia <= 0) return $fechaLimite;

        return $limite->modify('+' . $diasGracia . ' day')->format('Y-m-d');
    }

    /**
     * Si la ventana de presentación (incluyendo gracia) cerró.
     */
    public function isSubmissionWindowClosed(array $task): bool
    {
        $fechaLimiteEfectiva = $this->fechaLimiteEfectiva($task);
        if (!$fechaLimiteEfectiva) return false;
        return $this->today > $fechaLimiteEfectiva;
    }

    /**
     * Días de gracia calculados (10% del plazo original, mínimo 1).
     * Se usa para el límite efectivo y también para re-presentación por rechazo.
     */
    public function diasGraciaForTask(array $task): int
    {
        if ($this->graciaPorcentaje <= 0) return 0;

        $fechaLimite = (string)($task['fecha_limite'] ?? '');
        if ($fechaLimite === '') return 0;

        $inicioStr = (string)($task['fecha_inicio'] ?? '');
        if ($inicioStr === '') {
            $inicioStr = substr((string)($task['created_at'] ?? ''), 0, 10);
        }
        if ($inicioStr === '') return 0;

        $inicio = \DateTimeImmutable::createFromFormat('Y-m-d', $inicioStr);
        $limite = \DateTimeImmutable::createFromFormat('Y-m-d', $fechaLimite);
        if (!$inicio || !$limite) return 0;

        $diff = (int)$inicio->diff($limite)->format('%r%a');
        if ($diff < 1) return 1;
        return max(1, (int)ceil($diff * $this->graciaPorcentaje));
    }

    private function diasGracia(array $task): int
    {
        return $this->diasGraciaForTask($task);
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

    /**
     * Fecha (Y-m-d) de la primera evidencia registrada, o null.
     */
    public function primeraFechaEvidencia(array $evidencias): ?string
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
