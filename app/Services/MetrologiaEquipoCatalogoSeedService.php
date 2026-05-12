<?php
namespace App\Services;

use App\Core\JsonStorage;
use App\Repositories\MetrologiaEquipoCatalogoRepositoryInterface;
use App\Repositories\MetrologiaRepositoryUtils;

/**
 * Construye la base maestra de equipos a partir de:
 * - metrologia_bitacora_equipos.json
 * - metrologia_programa_2026.json
 *
 * La base maestra NO representa el estado del proceso; sólo datos del equipo.
 */
class MetrologiaEquipoCatalogoSeedService
{
    private JsonStorage $bitacoraStorage;
    private JsonStorage $programaStorage;
    private MetrologiaEquipoCatalogoRepositoryInterface $repo;

    public function __construct(
        JsonStorage $bitacoraStorage,
        JsonStorage $programaStorage,
        MetrologiaEquipoCatalogoRepositoryInterface $repo
    ) {
        $this->bitacoraStorage = $bitacoraStorage;
        $this->programaStorage = $programaStorage;
        $this->repo = $repo;
    }

    public function ensureSeeded(): void
    {
        if (!empty($this->repo->findAll())) {
            return;
        }
        $all = [];
        $bit = $this->bitacoraStorage->read([]);
        $prog = $this->programaStorage->read([]);
        if (is_array($bit)) $all = array_merge($all, $bit);
        if (is_array($prog)) $all = array_merge($all, $prog);

        $seen = [];
        foreach ($all as $row) {
            if (!is_array($row)) continue;
            $noSerie = strtoupper(trim((string)($row['no_serie'] ?? '')));
            $folio = trim((string)($row['folio'] ?? ''));
            $marca = trim((string)($row['marca'] ?? ''));
            $modelo = trim((string)($row['modelo'] ?? ''));
            $desc = trim((string)($row['descripcion'] ?? ''));
            $zona = trim((string)($row['zona'] ?? ($row['zona_id'] ?? '')));
            $area = trim((string)($row['area'] ?? ($row['area_id'] ?? '')));
            $oficina = trim((string)($row['oficina'] ?? ''));

            if ($noSerie !== '') $key = 'S:' . $noSerie;
            elseif ($folio !== '') $key = 'F:' . $folio;
            else $key = 'H:' . md5(strtoupper($marca . '|' . $modelo . '|' . $desc . '|' . $zona . '|' . $area . '|' . $oficina));

            if (isset($seen[$key])) {
                // merge preferir valores no vacíos
                $id = $seen[$key];
                $prev = $this->repo->find($id);
                if (!$prev) continue;
                $merged = $prev;
                foreach ([
                    'folio' => $folio,
                    'no_serie' => $noSerie,
                    'marca' => $marca,
                    'modelo' => $modelo,
                    'descripcion' => $desc,
                    'zona' => $zona,
                    'area' => $area,
                    'oficina' => $oficina,
                ] as $k => $v) {
                    if ($v !== '' && (string)($merged[$k] ?? '') === '') $merged[$k] = $v;
                }
                $this->repo->save($merged);
                continue;
            }

            $entity = [
                'id' => MetrologiaRepositoryUtils::newId('meq'),
                'folio' => $folio,
                'no_serie' => $noSerie,
                'marca' => $marca,
                'modelo' => $modelo,
                'descripcion' => $desc,
                'zona' => $zona,
                'area' => $area,
                'oficina' => $oficina,
                'created_at' => MetrologiaRepositoryUtils::nowIso(),
                'updated_at' => MetrologiaRepositoryUtils::nowIso(),
            ];
            $saved = $this->repo->save($entity);
            $seen[$key] = (string)($saved['id'] ?? $entity['id']);
        }
    }
}

