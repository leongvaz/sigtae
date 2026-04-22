<?php
namespace App\Services;

use App\Repositories\TaskRepositoryInterface;

/**
 * Cálculo de desempeño individual: promedio simple sobre tareas asignadas.
 * Atendida en tiempo = 100%, fuera de tiempo = 50%, no atendida = 0%.
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
            if (!empty($st['cancelada'])) {
                continue;
            }
            $withState[] = $st;
        }
        $total = count($withState);
        $atendidasTiempo = 0;
        $atendidasFuera = 0;
        $incumplidas = 0;
        $activas = 0;
        $sum = 0;
        foreach ($withState as $t) {
            if (!empty($t['pendiente_mejora']) && ($t['evaluacion'] ?? null) === null) {
                $activas++;
                continue;
            }
            $estado = $t['estado'] ?? '';
            if (in_array($estado, ['atendida', 'vencida', 'incumplimiento'], true)) {
                $p = $this->stateService->porcentajeCumplimiento($t);
                if (array_key_exists('evaluacion', $t) && $t['evaluacion'] !== null && $t['evaluacion'] !== '') {
                    $p = (float) $t['evaluacion'];
                }
                $sum += $p;
                if ($estado === 'atendida') {
                    $atendidasTiempo++;
                } elseif ($estado === 'vencida') {
                    $atendidasFuera++;
                } else {
                    $incumplidas++;
                }
            } else {
                $activas++;
            }
        }
        $evaluadas = $total - $activas;
        $porcentaje = $evaluadas > 0 ? round($sum / $evaluadas, 1) : 0.0;

        return [
            'responsable_id' => $responsableId,
            'total_tareas' => $total,
            'tareas_atendidas_tiempo' => $atendidasTiempo,
            'tareas_atendidas_fuera_tiempo' => $atendidasFuera,
            'tareas_incumplidas' => $incumplidas,
            'tareas_activas' => $activas,
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
        usort($out, fn($a, $b) => ($b['porcentaje_desempeno'] ?? 0) <=> ($a['porcentaje_desempeno'] ?? 0));
        return $out;
    }
}
