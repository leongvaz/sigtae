<?php
namespace App\Services;

/**
 * Normaliza nomenclatura de zonas (DM210, 21, ZOCALO → dm21 / zn-21).
 */
class MetrologiaZonaService
{
    /** @var list<array{id:string,prefijo:string,nombre:string,codigo:string,aliases:list<string>}> */
    private array $zonas;

    public function __construct(array $catalogosZonas = [])
    {
        $aliasMap = [
            'zn-21' => ['DM210', 'DM21', '21', 'ZOCALO'],
            'zn-22' => ['DM22', '22', 'BENITO JUAREZ'],
            'zn-23' => ['DM23', '23', 'POLANCO'],
            'zn-24' => ['DM24', '24', 'TACUBA'],
            'zn-25' => ['DM25', '25', 'AEROPUERTO'],
            'zn-26' => ['DM26', '26', 'NEZA', 'NEZAHUALCOYOTL'],
            'zn-27' => ['DM27', '27', 'CHAPINGO'],
            'zn-lab' => ['DM000', 'LABORATORIO', 'LAB'],
        ];

        $built = [];
        $source = !empty($catalogosZonas) ? $catalogosZonas : [
            ['id' => 'zn-21', 'nombre' => '21 / ZOCALO', 'prefijo' => 'dm21'],
            ['id' => 'zn-22', 'nombre' => '22 / BENITO JUAREZ', 'prefijo' => 'dm22'],
            ['id' => 'zn-23', 'nombre' => '23 / POLANCO', 'prefijo' => 'dm23'],
            ['id' => 'zn-24', 'nombre' => '24 / TACUBA', 'prefijo' => 'dm24'],
            ['id' => 'zn-25', 'nombre' => '25 / AEROPUERTO', 'prefijo' => 'dm25'],
            ['id' => 'zn-26', 'nombre' => '26 / NEZA', 'prefijo' => 'dm26'],
            ['id' => 'zn-27', 'nombre' => '27 / CHAPINGO', 'prefijo' => 'dm27'],
        ];

        foreach ($source as $z) {
            if (empty($z['id']) || empty($z['prefijo'])) continue;
            $id = (string)$z['id'];
            $pref = strtolower(trim((string)$z['prefijo']));
            $num = preg_replace('/\D/', '', $pref);
            $codigo = $pref === 'dm000' ? 'DM000' : ('DM' . $num);
            $built[] = [
                'id' => $id,
                'prefijo' => $pref,
                'nombre' => (string)($z['nombre'] ?? $codigo),
                'codigo' => strtoupper($codigo),
                'aliases' => $aliasMap[$id] ?? [],
            ];
        }

        $hasLab = false;
        foreach ($built as $b) {
            if (($b['id'] ?? '') === 'zn-lab') {
                $hasLab = true;
                break;
            }
        }
        if (!$hasLab) {
            $built[] = [
                'id' => 'zn-lab',
                'prefijo' => 'dm000',
                'nombre' => 'Laboratorio',
                'codigo' => 'DM000',
                'aliases' => $aliasMap['zn-lab'],
            ];
        }

        $this->zonas = $built;
    }

    /**
     * @return array{id:string,prefijo:string,nombre:string,codigo:string,zona_display:string}|null
     */
    public function normalize(string $raw): ?array
    {
        $key = $this->normalizeKey($raw);
        if ($key === '') return null;

        foreach ($this->zonas as $z) {
            if ($this->keyMatchesZona($key, $z)) {
                return [
                    'id' => $z['id'],
                    'prefijo' => $z['prefijo'],
                    'nombre' => $z['nombre'],
                    'codigo' => $z['codigo'],
                    'zona_display' => $z['codigo'],
                ];
            }
        }
        return null;
    }

    public function matchesPrefijo(string $rawZona, string $prefijo): bool
    {
        $prefijo = strtolower(trim($prefijo));
        if ($prefijo === '') return true;

        $norm = $this->normalize($rawZona);
        if ($norm !== null) {
            return $norm['prefijo'] === $prefijo;
        }

        $key = $this->normalizeKey($rawZona);
        return $key !== '' && strpos($key, strtoupper($prefijo)) !== false;
    }

    /** @return list<array<string,mixed>> */
    public function listZonas(): array
    {
        $out = [];
        $seen = [];
        foreach ($this->zonas as $z) {
            if (isset($seen[$z['id']])) continue;
            $seen[$z['id']] = true;
            $out[] = $z;
        }
        return $out;
    }

  private function normalizeKey(string $raw): string
    {
        $s = strtoupper(trim($raw));
        if ($s === '') return '';
        $map = ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N'];
        $s = strtr($s, $map);
        $s = preg_replace('/\s+/', '', $s) ?? $s;
        return $s;
    }

    /** @param array{id:string,prefijo:string,nombre:string,codigo:string,aliases:list<string>} $z */
    private function keyMatchesZona(string $key, array $z): bool
    {
        $candidates = array_merge(
            [$z['codigo'], strtoupper($z['prefijo']), $z['id']],
            $z['aliases'] ?? []
        );
        foreach ($candidates as $c) {
            $ck = $this->normalizeKey((string)$c);
            if ($ck === '') continue;
            if ($key === $ck || strpos($key, $ck) === 0 || strpos($ck, $key) === 0) {
                return true;
            }
        }
        // DM210 → dm21 (zócalo): clave empieza con DM21
        if ($z['prefijo'] === 'dm21' && preg_match('/^DM21/', $key)) {
            return true;
        }
        if (preg_match('/^DM(\d{2,3})/', $key, $m)) {
            $num = ltrim($m[1], '0');
            $codigoNum = preg_replace('/^DM/i', '', $z['codigo']);
            if ($num !== '' && $codigoNum === $num) {
                return true;
            }
        }
        return false;
    }
}
