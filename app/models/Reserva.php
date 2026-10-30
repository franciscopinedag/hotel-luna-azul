<?php
/**
 * Modelo Reserva: registro, consulta, modificación y cancelación,
 * con control transaccional para impedir la doble asignación.
 */
class Reserva extends Model
{
    public const ESTADOS = ['confirmada', 'cancelada', 'finalizada'];

    private const SELECT_BASE = "SELECT r.*, h.nombre_completo, h.tipo_documento, h.numero_documento,
                                        h.celular, h.correo,
                                        hab.numero AS habitacion, hab.piso, t.id AS tipo_id, t.nombre AS tipo,
                                        t.capacidad, DATEDIFF(r.fecha_salida, r.fecha_ingreso) AS noches,
                                        u.nombre AS registrada_por
                                 FROM reservas r
                                 JOIN huespedes h ON h.id = r.huesped_id
                                 JOIN habitaciones hab ON hab.id = r.habitacion_id
                                 JOIN tipos_habitacion t ON t.id = hab.tipo_id
                                 LEFT JOIN usuarios u ON u.id = r.usuario_id";

    public function listar(array $f = []): array
    {
        $where = [];
        $p = [];
        if (!empty($f['estado']) && in_array($f['estado'], self::ESTADOS, true)) {
            $where[] = 'r.estado = ?';
            $p[] = $f['estado'];
        }
        if (!empty($f['q'])) {
            $where[] = '(h.nombre_completo LIKE ? OR h.numero_documento LIKE ? OR r.codigo LIKE ? OR hab.numero = ?)';
            $like = '%' . $f['q'] . '%';
            array_push($p, $like, $like, $like, $f['q']);
        }
        if (!empty($f['fecha']) && $this->fechaValida($f['fecha'])) {
            // Reservas que ocupan la habitación en esa noche
            $where[] = 'r.fecha_ingreso <= ? AND r.fecha_salida > ?';
            array_push($p, $f['fecha'], $f['fecha']);
        }
        $sql = self::SELECT_BASE . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
             . " ORDER BY FIELD(r.estado,'confirmada','finalizada','cancelada'), r.fecha_ingreso DESC";
        $st = $this->db->prepare($sql);
        $st->execute($p);
        return $st->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $st = $this->db->prepare(self::SELECT_BASE . ' WHERE r.id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function porHuesped(int $huespedId): array
    {
        $st = $this->db->prepare(self::SELECT_BASE . ' WHERE r.huesped_id = ? ORDER BY r.fecha_ingreso DESC');
        $st->execute([$huespedId]);
        return $st->fetchAll();
    }

    /**
     * Crea la reserva dentro de una transacción.
     * SELECT ... FOR UPDATE bloquea la fila de la habitación: si dos recepcionistas
     * reservan la misma habitación al mismo tiempo, el segundo espera y luego
     * encuentra el cruce de fechas, así se evita la doble asignación.
     */
    public function crear(array $d, int $usuarioId): array
    {
        $this->db->beginTransaction();
        try {
            $hab = $this->bloquearHabitacion($d['habitacion_id']);
            $this->verificarCruce($d['habitacion_id'], $d['fecha_ingreso'], $d['fecha_salida']);

            $noches = noches($d['fecha_ingreso'], $d['fecha_salida']);
            $st = $this->db->prepare('INSERT INTO reservas (huesped_id, habitacion_id, fecha_ingreso, fecha_salida,
                                        num_huespedes, precio_noche, valor_total, observaciones, usuario_id)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $st->execute([$d['huesped_id'], $d['habitacion_id'], $d['fecha_ingreso'], $d['fecha_salida'],
                          $d['num_huespedes'], $hab['precio_noche'], $hab['precio_noche'] * $noches,
                          $d['observaciones'] ?: null, $usuarioId]);
            $id = (int) $this->db->lastInsertId();
            $codigo = 'LA-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
            $this->db->prepare('UPDATE reservas SET codigo = ? WHERE id = ?')->execute([$codigo, $id]);

            $this->db->commit();
            return ['id' => $id, 'codigo' => $codigo];
        } catch (Throwable $ex) {
            $this->db->rollBack();
            throw $ex;
        }
    }

    public function actualizar(int $id, array $d): void
    {
        $this->db->beginTransaction();
        try {
            $hab = $this->bloquearHabitacion($d['habitacion_id']);
            $this->verificarCruce($d['habitacion_id'], $d['fecha_ingreso'], $d['fecha_salida'], $id);

            $noches = noches($d['fecha_ingreso'], $d['fecha_salida']);
            $st = $this->db->prepare("UPDATE reservas SET huesped_id = ?, habitacion_id = ?, fecha_ingreso = ?,
                                        fecha_salida = ?, num_huespedes = ?, precio_noche = ?, valor_total = ?,
                                        observaciones = ?
                                      WHERE id = ? AND estado = 'confirmada'");
            $st->execute([$d['huesped_id'], $d['habitacion_id'], $d['fecha_ingreso'], $d['fecha_salida'],
                          $d['num_huespedes'], $hab['precio_noche'], $hab['precio_noche'] * $noches,
                          $d['observaciones'] ?: null, $id]);
            $this->db->commit();
        } catch (Throwable $ex) {
            $this->db->rollBack();
            throw $ex;
        }
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        $st = $this->db->prepare("UPDATE reservas SET estado = ? WHERE id = ? AND estado = 'confirmada'");
        $st->execute([$estado, $id]);
        return $st->rowCount() === 1;
    }

    private function bloquearHabitacion(int $habitacionId): array
    {
        $st = $this->db->prepare("SELECT h.id, h.estado, t.precio_noche FROM habitaciones h
                                  JOIN tipos_habitacion t ON t.id = h.tipo_id WHERE h.id = ? FOR UPDATE");
        $st->execute([$habitacionId]);
        $hab = $st->fetch();
        if (!$hab || $hab['estado'] !== 'activa') {
            throw new DomainException('La habitación seleccionada no está disponible para reservas.');
        }
        return $hab;
    }

    private function verificarCruce(int $habitacionId, string $ingreso, string $salida, int $excluir = 0): void
    {
        $st = $this->db->prepare("SELECT codigo FROM reservas
                                  WHERE habitacion_id = ? AND estado = 'confirmada' AND id <> ?
                                    AND fecha_ingreso < ? AND fecha_salida > ? LIMIT 1");
        $st->execute([$habitacionId, $excluir, $salida, $ingreso]);
        if ($codigo = $st->fetchColumn()) {
            throw new DomainException("La habitación ya está ocupada en esas fechas por la reserva $codigo. Elija otra habitación o cambie las fechas.");
        }
    }

    /** Indicadores del panel principal. */
    public function resumenHoy(): array
    {
        $sql = "SELECT
                  SUM(estado='confirmada' AND fecha_ingreso <= CURDATE() AND fecha_salida > CURDATE()) AS ocupadas,
                  SUM(estado='confirmada' AND fecha_ingreso = CURDATE()) AS llegadas,
                  SUM(estado='confirmada' AND fecha_salida = CURDATE()) AS salidas,
                  SUM(estado='confirmada' AND fecha_ingreso > CURDATE()) AS proximas
                FROM reservas";
        return array_map('intval', $this->db->query($sql)->fetch());
    }

    /** Reservas confirmadas que tocan el rango [desde, hasta) para el tablero de ocupación. */
    public function enRango(string $desde, string $hasta): array
    {
        $st = $this->db->prepare("SELECT r.id, r.codigo, r.habitacion_id, r.fecha_ingreso, r.fecha_salida, h.nombre_completo
                                  FROM reservas r JOIN huespedes h ON h.id = r.huesped_id
                                  WHERE r.estado = 'confirmada' AND r.fecha_ingreso < ? AND r.fecha_salida > ?");
        $st->execute([$hasta, $desde]);
        return $st->fetchAll();
    }

    public function movimientosHoy(): array
    {
        $sql = self::SELECT_BASE . " WHERE r.estado = 'confirmada'
                 AND (r.fecha_ingreso = CURDATE() OR r.fecha_salida = CURDATE())
                 ORDER BY r.fecha_ingreso = CURDATE() DESC, hab.numero";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Valida los datos de la reserva (servidor). Devuelve [datos, errores].
     */
    public function validar(array $in, ?array $original = null): array
    {
        $d = [
            'huesped_id'    => filter_var($in['huesped_id'] ?? null, FILTER_VALIDATE_INT) ?: 0,
            'tipo_id'       => filter_var($in['tipo_id'] ?? null, FILTER_VALIDATE_INT) ?: 0,
            'habitacion_id' => filter_var($in['habitacion_id'] ?? null, FILTER_VALIDATE_INT) ?: 0,
            'fecha_ingreso' => trim((string) ($in['fecha_ingreso'] ?? '')),
            'fecha_salida'  => trim((string) ($in['fecha_salida'] ?? '')),
            'num_huespedes' => filter_var($in['num_huespedes'] ?? null, FILTER_VALIDATE_INT) ?: 0,
            'observaciones' => mb_substr(trim((string) ($in['observaciones'] ?? '')), 0, 255),
        ];
        $e = [];

        if (!$d['huesped_id'] || !(new Huesped())->buscar($d['huesped_id'])) {
            $e['huesped_id'] = 'Seleccione el huésped titular.';
        }

        $habModel = new Habitacion();
        $tipo = $d['tipo_id'] ? $habModel->tipo($d['tipo_id']) : null;
        if (!$tipo) {
            $e['tipo_id'] = 'Seleccione el tipo de habitación.';
        }

        $hoy = date('Y-m-d');
        $ingresoOk = $this->fechaValida($d['fecha_ingreso']);
        $salidaOk = $this->fechaValida($d['fecha_salida']);
        $ingresoSinCambio = $original && $original['fecha_ingreso'] === $d['fecha_ingreso'];

        if (!$ingresoOk) {
            $e['fecha_ingreso'] = 'Indique la fecha de ingreso.';
        } elseif ($d['fecha_ingreso'] < $hoy && !$ingresoSinCambio) {
            $e['fecha_ingreso'] = 'La fecha de ingreso no puede ser anterior a hoy.';
        }
        if (!$salidaOk) {
            $e['fecha_salida'] = 'Indique la fecha de salida.';
        } elseif ($ingresoOk && $d['fecha_salida'] <= $d['fecha_ingreso']) {
            $e['fecha_salida'] = 'La salida debe ser al menos un día después del ingreso.';
        } elseif ($ingresoOk && noches($d['fecha_ingreso'], $d['fecha_salida']) > MAX_NOCHES) {
            $e['fecha_salida'] = 'La estadía máxima por reserva es de ' . MAX_NOCHES . ' noches.';
        }

        if ($d['num_huespedes'] < 1) {
            $e['num_huespedes'] = 'Indique cuántas personas se hospedan.';
        } elseif ($tipo && $d['num_huespedes'] > (int) $tipo['capacidad']) {
            $e['num_huespedes'] = "La habitación {$tipo['nombre']} admite máximo {$tipo['capacidad']} persona(s).";
        }

        $hab = $d['habitacion_id'] ? $habModel->buscar($d['habitacion_id']) : null;
        if (!$hab) {
            $e['habitacion_id'] = 'Seleccione una habitación disponible.';
        } elseif ($tipo && (int) $hab['tipo_id'] !== (int) $tipo['id']) {
            $e['habitacion_id'] = 'La habitación no corresponde al tipo seleccionado.';
        }

        return [$d, $e];
    }

    private function fechaValida(string $f): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $f);
        return $dt && $dt->format('Y-m-d') === $f;
    }
}
