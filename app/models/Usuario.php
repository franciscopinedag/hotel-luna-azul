<?php
class Usuario extends Model
{
    public function buscarPorUsuario(string $usuario): ?array
    {
        $st = $this->db->prepare('SELECT * FROM usuarios WHERE usuario = ? LIMIT 1');
        $st->execute([$usuario]);
        return $st->fetch() ?: null;
    }

    public function registrarAcceso(int $id): void
    {
        $this->db->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?')->execute([$id]);
    }

    /** Si PHP recomienda un algoritmo más fuerte, se actualiza el hash. */
    public function actualizarHash(int $id, string $password): void
    {
        $this->db->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')
                 ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }
}
