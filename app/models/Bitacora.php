<?php
/**
 * Bitácora de auditoría NO RELACIONAL.
 * Cada acción se guarda como un documento JSON independiente (formato JSON Lines),
 * igual que lo haría una base documental tipo MongoDB: sin esquema fijo,
 * un documento por evento, campos variables según la acción.
 */
class Bitacora
{
    private string $archivo;

    public function __construct()
    {
        $this->archivo = STORAGE_PATH . '/bitacora.jsonl';
    }

    public function registrar(string $accion, string $entidad, int|string|null $entidadId = null, array $detalle = []): void
    {
        $u = usuario_actual();
        $documento = [
            '_id'       => bin2hex(random_bytes(8)),
            'fecha'     => date('c'),
            'usuario'   => $u['usuario'] ?? 'sistema',
            'accion'    => $accion,
            'entidad'   => $entidad,
            'entidad_id'=> $entidadId,
            'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'cli',
            'detalle'   => $detalle ?: new stdClass(),
        ];
        @file_put_contents($this->archivo, json_encode($documento, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /** Devuelve los documentos más recientes primero, con filtro opcional por entidad. */
    public function ultimos(int $limite = 100, string $entidad = ''): array
    {
        if (!is_file($this->archivo)) {
            return [];
        }
        $lineas = array_reverse(file($this->archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $docs = [];
        foreach ($lineas as $linea) {
            $doc = json_decode($linea, true);
            if (!is_array($doc) || ($entidad !== '' && ($doc['entidad'] ?? '') !== $entidad)) {
                continue;
            }
            $docs[] = $doc;
            if (count($docs) >= $limite) {
                break;
            }
        }
        return $docs;
    }
}
