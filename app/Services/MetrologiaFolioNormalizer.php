<?php
namespace App\Services;

/**
 * Normalización de folios sin padding forzado (2026-351 se conserva).
 */
class MetrologiaFolioNormalizer
{
    public function normalize(string $folio): string
    {
        $f = trim($folio);
        if ($f === '') return '';

        $f = preg_replace('/\s+/', '-', $f) ?? $f;
        $f = preg_replace('/-+/', '-', $f) ?? $f;
        $f = strtoupper($f);

        // 26-0565 => 2026-0565
        if (preg_match('/^(\d{2})-(\d{4})$/', $f, $m)) {
            return '20' . $m[1] . '-' . $m[2];
        }
        // 2026--5 => 2026-5 (sin ceros a la izquierda)
        if (preg_match('/^(\d{4})--(\d{1,4})$/', $f, $m)) {
            return $m[1] . '-' . ltrim($m[2], '0') ?: '0';
        }
        // 2026-5 => 2026-5 (sin padding)
        if (preg_match('/^(\d{4})-(\d{1,4})$/', $f, $m)) {
            $seq = ltrim($m[2], '0');
            return $m[1] . '-' . ($seq !== '' ? $seq : '0');
        }

        return $f;
    }

    /** Comparación flexible (exacto + variante con padding legacy). */
    public function folioMatches(string $stored, string $query): bool
    {
        $a = $this->normalize($stored);
        $b = $this->normalize($query);
        if ($a === '' || $b === '') {
            return strcasecmp(trim($stored), trim($query)) === 0;
        }
        if ($a === $b) return true;

        foreach ($this->variants($a) as $va) {
            foreach ($this->variants($b) as $vb) {
                if ($va === $vb) return true;
            }
        }
        return false;
    }

    /** @return list<string> */
    private function variants(string $folio): array
    {
        $out = [$folio];
        if (preg_match('/^(\d{4})-(\d+)$/', $folio, $m)) {
            $out[] = $m[1] . '-' . str_pad($m[2], 4, '0', STR_PAD_LEFT);
            $out[] = $m[1] . '-' . ltrim($m[2], '0');
        }
        return array_values(array_unique($out));
    }
}
