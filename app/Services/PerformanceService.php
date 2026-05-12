<?php
namespace App\Services;

use App\Repositories\TaskRepositoryInterface;

/**
 * Cálculo de desempeño individual.
 * Regla vigente: porcentaje de tareas APROBADAS
 * sobre tareas EVALUADAS (Aprobada/Rechazada).
 */
class PerformanceService
{
    private TaskRepositoryInterface $taskRepo;
    private TaskStateService $stateService;

    public function __construct(TaskRepositoryInterface $taskRepo, TaskStateService $stateService)
    {
        $this->taskRepo = $taskRepo;
        $this->stateService = $stateService;
    }

    /**
     * Desempeño de un responsable: porcentaje y desglose.
     */
    public function getPerformance(string $responsableId): array
    {
        $tasks = $this->taskRepo->findByResponsable($responsableId);
        $withState = [];
        foreach ($tasks as $t) {
            $st = $this->stateService->computeState($t);
            if (!empty($st['cancelada'])) continue;
            $withState[] = $st;
        }
        $total = count($withState);

        $evaluadas = 0;
        $aprobadas = 0;
        $rechazadas = 0;
        $pendientes = 0; // evidencia presentada, aún sin dictamen (o esperando nuevo intento)

        foreach ($withState as $t) {
            $dict = strtolower((string)($t['dictamen'] ?? ''));
            $eval = $t['evaluacion'] ?? null;
            $evCount = count((array)($t['evidencias'] ?? []));
            $evalVer = (int)($t['evaluacion_version'] ?? 0);
            $hayNuevoIntento = ($dict === 'rechazada' && $evCount > $evalVer);

            // Compatibilidad con valores históricos:
            // - dictamen: satisfactoria / satisfactoria_fuera_tiempo => aprobada
            // - dictamen: insatisfactoria => rechazada
            // - evaluacion numérica (100/50) => aprobada; 0 => rechazada (si existiera)
            $isAprob = in_array($dict, ['aprobada', 'satisfactoria', 'satisfactoria_fuera_tiempo'], true)
                || ((string)$eval === 'aprobada')
                || ((is_numeric($eval) || is_int($eval) || is_float($eval)) && (float)$eval >= 50);
            $isRech = in_array($dict, ['rechazada', 'insatisfactoria'], true)
                || ((string)$eval === 'rechazada')
                || ((is_numeric($eval) || is_int($eval) || is_float($eval)) && (float)$eval === 0.0);
            $isEval = $isAprob || $isRech;

            if ($isEval && !$hayNuevoIntento) {
                $evaluadas++;
                if ($isAprob) $aprobadas++;
                else $rechazadas++;
                continue;
            }

            // Pendiente de evaluación: hay evidencia y aún no hay dictamen (o hay nuevo intento)
            if ($evCount > 0) {
                $pendientes++;
            }
        }

        $porcentaje = $evaluadas > 0 ? round(($aprobadas / $evaluadas) * 100, 1) : 0.0;

        return [
            'responsable_id' => $responsableId,
            'total_tareas' => $total,
            'tareas_evaluadas' => $evaluadas,
            'tareas_aprobadas' => $aprobadas,
            'tareas_rechazadas' => $rechazadas,
            'tareas_pendientes_evaluacion' => $pendientes,
            'porcentaje_desempeno' => $porcentaje,
        ];
    }

    /**
     * Ranking de todos los colaboradores con tareas asignadas.
     */
    public function getRanking(): array
    {
        $tasks = $this->taskRepo->findAll();
        $responsableIds = array_unique(array_column($tasks, 'responsable_id'));
        $responsableIds = array_filter($responsableIds);
        $out = [];
        foreach ($responsableIds as $rid) {
            $out[] = $this->getPerformance($rid);
        }
        usort($out, function ($a, $b) {
            return ($b['porcentaje_desempeno'] ?? 0) <=> ($a['porcentaje_desempeno'] ?? 0);
        });
        return $out;
    }
}
