<?php
class BitacoraController extends Controller
{
    public function index(): void
    {
        $this->requiereAuth();
        $entidad = in_array($_GET['entidad'] ?? '', ['huesped', 'reserva', 'usuario'], true) ? $_GET['entidad'] : '';
        $this->render('bitacora/index', [
            'titulo'    => 'Bitácora',
            'registros' => (new Bitacora())->ultimos(150, $entidad),
            'entidad'   => $entidad,
        ]);
    }
}
