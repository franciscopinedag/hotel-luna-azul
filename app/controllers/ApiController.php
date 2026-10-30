<?php
/**
 * Endpoints AJAX: responden JSON para las validaciones en vivo del navegador.
 */
class ApiController extends Controller
{
    public function __construct()
    {
        $this->requiereAuth();
    }

    /** GET api/documento?tipo=CC&numero=123&excluir=5  → ¿el documento ya existe? */
    public function documento(): void
    {
        $tipo = (string) ($_GET['tipo'] ?? '');
        $numero = strtoupper(str_replace(['.', ' ', '-'], '', (string) ($_GET['numero'] ?? '')));
        if (!in_array($tipo, Huesped::TIPOS_DOCUMENTO, true) || $numero === '') {
            $this->json(['ok' => false, 'mensaje' => 'Parámetros incompletos'], 422);
        }
        $existe = (new Huesped())->existeDocumento($tipo, $numero, (int) ($_GET['excluir'] ?? 0));
        $this->json(['ok' => true, 'existe' => $existe,
                     'mensaje' => $existe ? 'Ya existe un huésped registrado con este documento.' : 'Documento disponible.']);
    }

    /** GET api/disponibilidad?tipo_id=2&ingreso=...&salida=...&excluir=ID → habitaciones libres */
    public function disponibilidad(): void
    {
        $tipoId = (int) ($_GET['tipo_id'] ?? 0);
        $ingreso = (string) ($_GET['ingreso'] ?? '');
        $salida = (string) ($_GET['salida'] ?? '');
        $m = new Habitacion();
        $tipo = $m->tipo($tipoId);

        $valida = fn(string $f) => ($d = DateTime::createFromFormat('Y-m-d', $f)) && $d->format('Y-m-d') === $f;
        if (!$tipo || !$valida($ingreso) || !$valida($salida) || $salida <= $ingreso) {
            $this->json(['ok' => false, 'mensaje' => 'Revise el tipo de habitación y las fechas.'], 422);
        }

        $n = noches($ingreso, $salida);
        $libres = $m->disponibles($tipoId, $ingreso, $salida, (int) ($_GET['excluir'] ?? 0));
        $this->json([
            'ok'           => true,
            'tipo'         => $tipo['nombre'],
            'capacidad'    => (int) $tipo['capacidad'],
            'precio_noche' => (float) $tipo['precio_noche'],
            'noches'       => $n,
            'total'        => (float) $tipo['precio_noche'] * $n,
            'habitaciones' => $libres,
            'mensaje'      => $libres ? count($libres) . ' habitación(es) disponible(s).' : "No hay habitaciones {$tipo['nombre']} libres en esas fechas.",
        ]);
    }

    /** GET api/huespedes?q=texto → búsqueda en vivo del listado */
    public function huespedes(): void
    {
        $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 60);
        $lista = array_map(fn($h) => [
            'id' => (int) $h['id'], 'nombre_completo' => $h['nombre_completo'],
            'tipo_documento' => $h['tipo_documento'], 'numero_documento' => $h['numero_documento'],
            'celular' => $h['celular'], 'correo' => $h['correo'], 'edad' => (int) $h['edad'],
            'total_reservas' => (int) $h['total_reservas'],
        ], (new Huesped())->listar($q));
        $this->json(['ok' => true, 'total' => count($lista), 'huespedes' => $lista]);
    }
}
