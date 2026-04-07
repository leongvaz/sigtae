<?php
namespace App\Core;

/**
 * Capa centralizada de lectura/escritura JSON.
 * Evita file_get_contents/file_put_contents regados por el código.
 * Maneja bloqueo básico y validación para reducir corrupción.
 */
class JsonStorage
{
    private string $basePath;
    private string $file;

    public function __construct(string $basePath, string $filename)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->file = $filename;
    }

    private function path(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $this->file;
    }

    /**
     * Lee y decodifica el JSON. Retorna array o el valor por defecto.
     */
    public function read($default = []): array
    {
        $path = $this->path();
        if (!is_file($path)) {
            return is_array($default) ? $default : [];
        }
        $content = @file_get_contents($path);
        if ($content === false) {
            return is_array($default) ? $default : [];
        }
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : (is_array($default) ? $default : []);
    }

    /**
     * Escribe el contenido con bloqueo exclusivo.
     */
    public function write(array $data): bool
    {
        $path = $this->path();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }
        $fp = @fopen($path, 'cb');
        if ($fp === false) {
            return false;
        }
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return false;
        }
        ftruncate($fp, 0);
        rewind($fp);
        $written = fwrite($fp, $json);
        flock($fp, LOCK_UN);
        fclose($fp);
        return $written !== false;
    }

    /**
     * Asegura que el archivo exista con el contenido por defecto.
     */
    public function ensureExists(array $default = []): void
    {
        $path = $this->path();
        if (!is_file($path)) {
            $this->write($default);
        }
    }
}
