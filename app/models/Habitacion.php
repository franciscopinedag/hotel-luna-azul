<?php
/**
 * Modelo Habitación: tipos, inventario y disponibilidad por fechas.
 */
class Habitacion extends Model
{
    public function tipos(): array
    {
        return $this->db->query('SELECT * FROM tipos_habitacion ORDER BY precio_noche')->fetchAll();
    }

    public function tipo(int $id): ?array
    {
        $st = $this->db->prepare('SELECT * FROM tipos_habitacion WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function buscar(int $id): ?array
    {
        $st = $this->db->prepare('SELECT h.*, t.nombre AS tipo, t.capacidad, t.precio_noche
                                  FROM habitaciones h JOIN tipos_habitacion t ON t.id = h.tipo_id WHERE h.id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** Inventario con el estado de ocupación del día de hoy. */
    public function listarConEstado(): array
    {
        $sql = "SELECT h.*, t.nombre AS tipo, t.capacidad, t.precio_noche,
                       (SELECT CONCAT(r.codigo, '|', hu.nombre_completo, '|', r.fecha_salida)
                          FROM reservas r JOIN huespedes hu ON hu.id = r.huesped_id
                         WHERE r.habitacion_id = h.id AND r.estado = 'confirmada'
                           AND r.fecha_ingreso <= CURDATE() AND r.fecha_salida > CURDATE()
                         LIMIT 1) AS ocupacion_hoy,
                       (SELECT MIN(r.fecha_ingreso) FROM reservas r
                         WHERE r.habitacion_id = h.id AND r.estado = 'confirmada'
                           AND r.fecha_ingreso > CURDATE()) AS proxima_llegada
                FROM habitaciones h
                JOIN tipos_habitacion t ON t.id = h.tipo_id
                ORDER BY h.numero";
        return $this->db->query($sql)->fetchAll();
    }

    public function activas(): array
    {
        return $this->db->query("SELECT h.id, h.numero, h.piso, t.nombre AS tipo
                                 FROM habitaciones h JOIN tipos_habitacion t ON t.id = h.tipo_id
                                 WHERE h.estado = 'activa' ORDER BY h.numero")->fetchAll();
    }

    /**
     * Habitaciones de un tipo que NO tienen reservas confirmadas que se crucen con el rango.
     * Dos rangos [A,B) y [C,D) se cruzan cuando A < D y B > C.
     * El día de salida queda libre para un nuevo ingreso.
     */
    public function disponibles(int $tipoId, string $ingreso, string $salida, int $excluirReserva = 0): array
    {
        $sql = "SELECT h.id, h.numero, h.piso
                FROM habitaciones h
                WHERE h.tipo_id = ? AND h.estado = 'activa'
                  AND NOT EXISTS (
                      SELECT 1 FROM reservas r
                      WHERE r.habitacion_id = h.id
                        AND r.estado = 'confirmada'
                        AND r.id <> ?
                        AND r.fecha_ingreso < ?
                        AND r.fecha_salida  > ?
                  )
                ORDER BY h.numero";
        $st = $this->db->prepare($sql);
        $st->execute([$tipoId, $excluirReserva, $salida, $ingreso]);
        return $st->fetchAll();
    }

    public function contarActivas(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM habitaciones WHERE estado = 'activa'")->fetchColumn();
    }
}
