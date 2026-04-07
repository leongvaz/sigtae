<?php
namespace App\Services;

use App\Repositories\UserRepositoryInterface;
use App\Repositories\DelegationRepositoryInterface;

/**
 * Lógica de permisos jerárquicos.
 * No usar condicionales por nombre de cargo; usar nivel_jerarquico, alcance_asignacion, ámbito.
 */
class PermissionService
{
    private UserRepositoryInterface $userRepo;
    private DelegationRepositoryInterface $delegationRepo;

    public function __construct(UserRepositoryInterface $userRepo, DelegationRepositoryInterface $delegationRepo)
    {
        $this->userRepo = $userRepo;
        $this->delegationRepo = $delegationRepo;
    }

    /**
     * Obtiene el nivel numérico para comparación (1 = más alto).
     */
    public function getNivelNumerico(?array $user): int
    {
        if (!$user) {
            return 999;
        }
        return (int) ($user['nivel_jerarquico'] ?? 999);
    }

    /**
     * Verifica si el usuario tiene actualmente permisos de asignación (propios o por delegación).
     */
    public function canAssign(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        if (!empty($user['puede_asignar'])) {
            return true;
        }
        $delegation = $this->delegationRepo->findActiveForUser($user['id'] ?? '');
        return $delegation !== null && !empty($delegation['alcance_permiso']);
    }

    /**
     * Verifica si $asignador puede asignar tareas a $responsable.
     * Reglas: no asignar a superior, no asignar a pares salvo delegación, solo dentro del ámbito.
     */
    public function canAssignTo(?array $asignador, ?array $responsable): array
    {
        $result = ['allowed' => false, 'reason' => ''];

        if (!$asignador || !$responsable) {
            $result['reason'] = 'Usuario no encontrado.';
            return $result;
        }

        $idAsignador = $asignador['id'] ?? '';
        $idResponsable = $responsable['id'] ?? '';
        if ($idAsignador === $idResponsable) {
            $result['reason'] = 'No puede asignar tareas a sí mismo.';
            return $result;
        }

        $nivelAsignador = $this->getNivelNumerico($asignador);
        $nivelResponsable = $this->getNivelNumerico($responsable);

        if ($nivelResponsable < $nivelAsignador) {
            $result['reason'] = 'No puede asignar tareas a un superior jerárquico.';
            return $result;
        }

        if ($nivelResponsable === $nivelAsignador) {
            $delegation = $this->delegationRepo->findActiveForUser($idAsignador);
            if (!$delegation || empty($delegation['alcance_permiso'])) {
                $result['reason'] = 'No puede asignar a pares del mismo nivel sin delegación temporal.';
                return $result;
            }
        }

        if (!$this->isWithinScope($asignador, $responsable)) {
            $result['reason'] = 'El responsable no está dentro de su ámbito organizacional.';
            return $result;
        }

        if (!$this->canAssign($asignador)) {
            $result['reason'] = 'No tiene permiso para asignar tareas.';
            return $result;
        }

        $alcance = $this->getEffectiveAssignmentScope($asignador);
        $nivelPermitido = $this->nivelToAlcance($nivelResponsable);
        if (!in_array($nivelPermitido, $alcance, true)) {
            $result['reason'] = 'No puede asignar a ese nivel jerárquico.';
            return $result;
        }

        $result['allowed'] = true;
        return $result;
    }

    /**
     * Ámbito: mismo departamento y, para jefes de oficina, el responsable debe ser subordinado directo o de su oficina según estructura.
     */
    public function isWithinScope(?array $asignador, ?array $responsable): bool
    {
        if (!$asignador || !$responsable) {
            return false;
        }
        $deptAsignador = $asignador['departamento_id'] ?? '';
        $deptResponsable = $responsable['departamento_id'] ?? '';
        if ($deptAsignador !== $deptResponsable) {
            return false;
        }
        $nivelAsignador = $this->getNivelNumerico($asignador);
        if ($nivelAsignador === 1) {
            return true;
        }
        if ($nivelAsignador === 2) {
            $supervisorResp = $responsable['supervisor_id'] ?? null;
            $oficinaResp = $responsable['oficina_id'] ?? '';
            $oficinaAsignador = $asignador['oficina_id'] ?? '';
            if ($supervisorResp === $asignador['id']) {
                return true;
            }
            if ($oficinaResp === $oficinaAsignador) {
                $subs = $this->userRepo->findSubordinates($asignador['id']);
                $subIds = array_column($subs, 'id');
                return in_array($responsable['id'], $subIds, true);
            }
            return false;
        }
        return false;
    }

    private function getEffectiveAssignmentScope(array $user): array
    {
        $delegation = $this->delegationRepo->findActiveForUser($user['id'] ?? '');
        if ($delegation && !empty($delegation['alcance_permiso'])) {
            return is_array($delegation['alcance_permiso']) ? $delegation['alcance_permiso'] : [$delegation['alcance_permiso']];
        }
        return $user['alcance_asignacion'] ?? [];
    }

    private function nivelToAlcance(int $nivel): string
    {
        return 'nivel_' . $nivel;
    }

    /**
     * Puede evaluar: superior del responsable o quien tenga permiso de evaluación.
     */
    public function canEvaluate(?array $evaluador, ?array $task, ?array $responsable): bool
    {
        if (!$evaluador || !$task) {
            return false;
        }
        if (!empty($evaluador['puede_evaluar'])) {
            $respId = $task['responsable_id'] ?? '';
            if (!$respId) {
                return true;
            }
            $resp = $responsable ?? $this->userRepo->find($respId);
            if (!$resp) {
                return true;
            }
            $nivelEval = $this->getNivelNumerico($evaluador);
            $nivelResp = $this->getNivelNumerico($resp);
            if ($nivelEval <= $nivelResp && $this->isWithinScope($evaluador, $resp)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Lista de usuarios a los que $user puede asignar tareas.
     */
    public function getAssignableUsers(?array $user): array
    {
        if (!$user) {
            return [];
        }
        $all = $this->userRepo->findByDepartment($user['departamento_id'] ?? '');
        $assignable = [];
        foreach ($all as $candidate) {
            if (($candidate['id'] ?? '') === ($user['id'] ?? '')) {
                continue;
            }
            $check = $this->canAssignTo($user, $candidate);
            if ($check['allowed']) {
                $assignable[] = $candidate;
            }
        }
        return $assignable;
    }
}
