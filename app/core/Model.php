<?php
/**
 * Modelo base: entrega la conexión PDO a todos los modelos.
 */
abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::conexion();
    }
}
