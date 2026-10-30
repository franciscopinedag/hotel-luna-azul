<?php
/**
 * Módulo Reservas: registrar, consultar, modificar y cancelar.
 */
class ReservaController extends Controller
{
    private Reserva $modelo;

    public function __construct()
    {
        $this->requiereAuth();
        $this->modelo = new Reserva();
    }

    public function index(): void
    {
        $filtros = [
            'estado' => (string) ($_GET['estado'] ?? ''),
            'q'      => trim((string) ($_GET['q'] ?? '')),
            'fecha'  => (string) ($_GET['fecha'] ?? ''),
        ];
        $this->render('reservas/index', [
            'titulo'   => 'Reservas',
            'reservas' => $this->modelo->listar($filtros),
            'filtros'  => $filtros,
        ]);
    }

    public function crear(): void
    {
        $r = ['huesped_id' => (int) ($_GET['huesped_id'] ?? 0), 'num_huespedes' => 1];
        $this->formulario($r, [], 'crear');
    }

    public function guardar(): void
    {
        $this->requierePost('reservas/crear');
        [$datos, $errores] = $this->modelo->validar($_POST);

        if (!$errores) {
            try {
                $res = $this->modelo->crear($datos, usuario_actual()['id']);
                (new Bitacora())->registrar('crear', 'reserva', $res['id'], [
                    'codigo' => $res['codigo'], 'habitacion_id' => $datos['habitacion_id'],
                    'ingreso' => $datos['fecha_ingreso'], 'salida' => $datos['fecha_salida'],
                ]);
                flash('ok', "Reserva {$res['codigo']} confirmada.");
                $this->redirigir('reservas/ver', ['id' => $res['id']]);
            } catch (DomainException $ex) {
                $errores['habitacion_id'] = $ex->getMessage();
            }
        }
        $this->formulario($datos, $errores, 'crear');
    }

    public function ver(): void
    {
        $r = $this->modelo->buscar($this->idDesde($_GET));
        if (!$r) {
            flash('error', 'La reserva no existe.');
            $this->redirigir('reservas/index');
        }
        $this->render('reservas/ver', ['titulo' => 'Reserva ' . $r['codigo'], 'r' => $r]);
    }

    public function editar(): void
    {
        $r = $this->modelo->buscar($this->idDesde($_GET));
        if (!$r) {
            flash('error', 'La reserva no existe.');
            $this->redirigir('reservas/index');
        }
        if ($r['estado'] !== 'confirmada') {
            flash('aviso', "La reserva {$r['codigo']} está {$r['estado']} y ya no se puede modificar.");
            $this->redirigir('reservas/ver', ['id' => $r['id']]);
        }
        $this->formulario($r, [], 'editar');
    }

    public function actualizar(): void
    {
        $id = $this->idDesde($_POST);
        $this->requierePost('reservas/editar', ['id' => $id]);
        $original = $this->modelo->buscar($id);
        if (!$original || $original['estado'] !== 'confirmada') {
            flash('error', 'La reserva no existe o ya no se puede modificar.');
            $this->redirigir('reservas/index');
        }

        [$datos, $errores] = $this->modelo->validar($_POST, $original);
        if (!$errores) {
            try {
                $this->modelo->actualizar($id, $datos);
                (new Bitacora())->registrar('actualizar', 'reserva', $id, [
                    'codigo' => $original['codigo'],
                    'antes'  => ['habitacion' => $original['habitacion'], 'ingreso' => $original['fecha_ingreso'], 'salida' => $original['fecha_salida']],
                    'despues'=> ['habitacion_id' => $datos['habitacion_id'], 'ingreso' => $datos['fecha_ingreso'], 'salida' => $datos['fecha_salida']],
                ]);
                flash('ok', "Reserva {$original['codigo']} modificada.");
                $this->redirigir('reservas/ver', ['id' => $id]);
            } catch (DomainException $ex) {
                $errores['habitacion_id'] = $ex->getMessage();
            }
        }
        $datos = array_merge($original, $datos);
        $this->formulario($datos, $errores, 'editar');
    }

    public function cancelar(): void
    {
        $this->cambiarEstado('cancelada', 'cancelar', 'cancelada');
    }

    public function finalizar(): void
    {
        $this->cambiarEstado('finalizada', 'finalizar', 'finalizada (check-out registrado)');
    }

    private function cambiarEstado(string $estado, string $accion, string $texto): void
    {
        $id = $this->idDesde($_POST);
        $this->requierePost('reservas/ver', ['id' => $id]);
        $r = $this->modelo->buscar($id);

        if ($r && $this->modelo->cambiarEstado($id, $estado)) {
            (new Bitacora())->registrar($accion, 'reserva', $id, ['codigo' => $r['codigo'], 'motivo' => trim((string) ($_POST['motivo'] ?? ''))]);
            flash('ok', "Reserva {$r['codigo']} $texto. La habitación {$r['habitacion']} quedó libre para esas fechas.");
        } else {
            flash('error', 'Solo se pueden cambiar reservas en estado confirmada.');
        }
        $this->redirigir('reservas/ver', ['id' => $id]);
    }

    private function formulario(array $r, array $errores, string $modo): void
    {
        $this->render('reservas/form', [
            'titulo'    => $modo === 'crear' ? 'Nueva reserva' : 'Modificar reserva ' . ($r['codigo'] ?? ''),
            'r'         => $r,
            'errores'   => $errores,
            'modo'      => $modo,
            'huespedes' => (new Huesped())->opcionesSelect(),
            'tipos'     => (new Habitacion())->tipos(),
            'maxNoches' => MAX_NOCHES,
        ]);
    }
}
