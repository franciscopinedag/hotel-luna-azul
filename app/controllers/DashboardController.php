<?php
class DashboardController extends Controller
{
    private const DIAS_TABLERO = 14;

    public function index(): void
    {
        $this->requiereAuth();

        $reservas = new Reserva();
        $habitaciones = new Habitacion();

        $desde = date('Y-m-d');
        $hasta = date('Y-m-d', strtotime('+' . self::DIAS_TABLERO . ' days'));
        $dias = [];
        for ($i = 0; $i < self::DIAS_TABLERO; $i++) {
            $dias[] = date('Y-m-d', strtotime("+$i days"));
        }

        // Matriz habitación x día: qué reserva ocupa cada noche
        $ocupacion = [];
        foreach ($reservas->enRango($desde, $hasta) as $r) {
            foreach ($dias as $dia) {
                if ($r['fecha_ingreso'] <= $dia && $r['fecha_salida'] > $dia) {
                    $ocupacion[$r['habitacion_id']][$dia] = $r;
                }
            }
        }

        $listaHab = $habitaciones->activas();
        $resumen = $reservas->resumenHoy();
        $totalActivas = count($listaHab);

        $this->render('dashboard/index', [
            'titulo'        => 'Panel',
            'resumen'       => $resumen,
            'porcentaje'    => $totalActivas ? round($resumen['ocupadas'] * 100 / $totalActivas) : 0,
            'totalActivas'  => $totalActivas,
            'totalHuespedes'=> (new Huesped())->contar(),
            'dias'          => $dias,
            'habitaciones'  => $listaHab,
            'ocupacion'     => $ocupacion,
            'movimientos'   => $reservas->movimientosHoy(),
        ]);
    }
}
