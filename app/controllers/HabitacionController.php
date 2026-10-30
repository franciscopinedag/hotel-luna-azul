<?php
class HabitacionController extends Controller
{
    public function index(): void
    {
        $this->requiereAuth();
        $m = new Habitacion();
        $this->render('habitaciones/index', [
            'titulo'       => 'Habitaciones',
            'habitaciones' => $m->listarConEstado(),
            'tipos'        => $m->tipos(),
        ]);
    }
}
