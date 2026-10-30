<?php
/**
 * Modelo Huésped: CRUD y validación de datos del huésped.
 */
class Huesped extends Model
{
    public const TIPOS_DOCUMENTO = ['CC', 'CE', 'PA', 'PPT'];
    public const PARENTESCOS = ['Padre', 'Madre', 'Cónyuge', 'Hijo(a)', 'Hermano(a)', 'Abuelo(a)', 'Tío(a)', 'Primo(a)', 'Amigo(a)', 'Otro'];

    private const CAMPOS = ['nombre_completo', 'tipo_documento', 'numero_documento', 'direccion', 'telefono',
                            'celular', 'edad', 'correo', 'contacto_emergencia', 'parentesco_emergencia'];

    public function listar(string $busqueda = ''): array
    {
        $sql = 'SELECT h.*,
                       (SELECT COUNT(*) FROM reservas r WHERE r.huesped_id = h.id) AS total_reservas
                FROM huespedes h';
        $params = [];
        if ($busqueda !== '') {
            $sql .= ' WHERE h.nombre_completo LIKE ? OR h.numero_documento LIKE ? OR h.correo LIKE ? OR h.celular LIKE ? OR h.direccion LIKE ?';
            $like = '%' . $busqueda . '%';
            $params = [$like, $like, $like, $like, $like];
        }
        $sql .= ' ORDER BY h.nombre_completo';
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $st = $this->db->prepare('SELECT * FROM huespedes WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function existeDocumento(string $tipo, string $numero, int $excluirId = 0): bool
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM huespedes WHERE tipo_documento = ? AND numero_documento = ? AND id <> ?');
        $st->execute([$tipo, $numero, $excluirId]);
        return (int) $st->fetchColumn() > 0;
    }

    public function crear(array $d): int
    {
        $cols = implode(', ', self::CAMPOS);
        $marcas = implode(', ', array_fill(0, count(self::CAMPOS), '?'));
        $st = $this->db->prepare("INSERT INTO huespedes ($cols) VALUES ($marcas)");
        $st->execute(array_map(fn($c) => $d[$c], self::CAMPOS));
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $d): void
    {
        $sets = implode(', ', array_map(fn($c) => "$c = ?", self::CAMPOS));
        $valores = array_map(fn($c) => $d[$c], self::CAMPOS);
        $valores[] = $id;
        $this->db->prepare("UPDATE huespedes SET $sets WHERE id = ?")->execute($valores);
    }

    public function eliminar(int $id): void
    {
        $this->db->prepare('DELETE FROM huespedes WHERE id = ?')->execute([$id]);
    }

    public function contarReservas(int $id): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM reservas WHERE huesped_id = ?');
        $st->execute([$id]);
        return (int) $st->fetchColumn();
    }

    public function contar(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM huespedes')->fetchColumn();
    }

    public function opcionesSelect(): array
    {
        return $this->db->query('SELECT id, nombre_completo, tipo_documento, numero_documento FROM huespedes ORDER BY nombre_completo')->fetchAll();
    }

    /**
     * Limpia y valida los datos recibidos del formulario.
     * Las mismas reglas se aplican en JavaScript (cliente) y aquí (servidor).
     * @return array{0: array, 1: array} [datos normalizados, errores]
     */
    public function validar(array $entrada, int $excluirId = 0): array
    {
        $d = [];
        foreach (self::CAMPOS as $c) {
            $d[$c] = trim(preg_replace('/\s+/u', ' ', (string) ($entrada[$c] ?? '')));
        }
        $d['correo'] = mb_strtolower($d['correo']);
        $d['numero_documento'] = strtoupper(str_replace(['.', ' ', '-'], '', $d['numero_documento']));
        $d['telefono'] = preg_replace('/\D/', '', $d['telefono']);
        $d['celular'] = preg_replace('/\D/', '', $d['celular']);

        $e = [];
        $soloLetras = '/^[\p{L}\s\'.-]+$/u';

        if (mb_strlen($d['nombre_completo']) < 5 || mb_strlen($d['nombre_completo']) > 120 || !preg_match($soloLetras, $d['nombre_completo'])) {
            $e['nombre_completo'] = 'Escriba nombres y apellidos (solo letras, entre 5 y 120 caracteres).';
        } elseif (count(explode(' ', $d['nombre_completo'])) < 2) {
            $e['nombre_completo'] = 'Incluya al menos un nombre y un apellido.';
        }

        if (!in_array($d['tipo_documento'], self::TIPOS_DOCUMENTO, true)) {
            $e['tipo_documento'] = 'Seleccione un tipo de documento.';
        }

        $reglasDoc = [
            'CC'  => ['/^\d{6,10}$/', 'La cédula debe tener entre 6 y 10 dígitos.'],
            'CE'  => ['/^\d{6,10}$/', 'La cédula de extranjería debe tener entre 6 y 10 dígitos.'],
            'PA'  => ['/^[A-Z0-9]{5,12}$/', 'El pasaporte debe tener entre 5 y 12 letras o números.'],
            'PPT' => ['/^\d{6,12}$/', 'El PPT debe tener entre 6 y 12 dígitos.'],
        ];
        if (isset($reglasDoc[$d['tipo_documento']]) && !preg_match($reglasDoc[$d['tipo_documento']][0], $d['numero_documento'])) {
            $e['numero_documento'] = $reglasDoc[$d['tipo_documento']][1];
        } elseif (!isset($e['tipo_documento']) && $this->existeDocumento($d['tipo_documento'], $d['numero_documento'], $excluirId)) {
            $e['numero_documento'] = 'Ya existe un huésped registrado con este documento.';
        }

        if (mb_strlen($d['direccion']) < 5 || mb_strlen($d['direccion']) > 150) {
            $e['direccion'] = 'Escriba una dirección válida (entre 5 y 150 caracteres).';
        }
        if (!preg_match('/^\d{7,10}$/', $d['telefono'])) {
            $e['telefono'] = 'El teléfono debe tener entre 7 y 10 dígitos.';
        }
        if (!preg_match('/^3\d{9}$/', $d['celular'])) {
            $e['celular'] = 'El celular debe tener 10 dígitos y empezar por 3.';
        }

        $edad = filter_var($d['edad'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 18, 'max_range' => 110]]);
        if ($edad === false) {
            $e['edad'] = 'El titular de la reserva debe ser mayor de edad (18 a 110 años).';
        } else {
            $d['edad'] = $edad;
        }

        if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL) || mb_strlen($d['correo']) > 120) {
            $e['correo'] = 'Escriba un correo válido, por ejemplo nombre@dominio.com.';
        }
        if (mb_strlen($d['contacto_emergencia']) < 3 || !preg_match($soloLetras, $d['contacto_emergencia'])) {
            $e['contacto_emergencia'] = 'Escriba el nombre de la persona de contacto (solo letras).';
        }
        if (!in_array($d['parentesco_emergencia'], self::PARENTESCOS, true)) {
            $e['parentesco_emergencia'] = 'Seleccione el parentesco.';
        }

        return [$d, $e];
    }
}
