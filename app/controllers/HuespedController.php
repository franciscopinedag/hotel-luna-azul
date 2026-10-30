<?php
/**
 * Módulo Huéspedes (CRUD): registrar, listar, editar y eliminar.
 */
class HuespedController extends Controller
{
    private Huesped $modelo;

    public function __construct()
    {
        $this->requiereAuth();
        $this->modelo = new Huesped();
    }

    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $this->render('huespedes/index', [
            'titulo'    => 'Huéspedes',
            'huespedes' => $this->modelo->listar($q),
            'q'         => $q,
        ]);
    }

    public function crear(): void
    {
        $this->formulario([], [], 'crear');
    }

    public function guardar(): void
    {
        $this->requierePost('huespedes/crear');
        [$datos, $errores] = $this->modelo->validar($_POST);

        if ($errores) {
            $this->formulario($datos, $errores, 'crear');
            return;
        }
        $id = $this->modelo->crear($datos);
        (new Bitacora())->registrar('crear', 'huesped', $id, ['nombre' => $datos['nombre_completo'], 'documento' => $datos['tipo_documento'] . ' ' . $datos['numero_documento']]);
        flash('ok', "Huésped {$datos['nombre_completo']} registrado.");

        // Si venía desde "nueva reserva", regresa allí con el huésped seleccionado
        if (($_POST['volver'] ?? '') === 'reserva') {
            $this->redirigir('reservas/crear', ['huesped_id' => $id]);
        }
        $this->redirigir('huespedes/index');
    }

    public function editar(): void
    {
        $h = $this->modelo->buscar($this->idDesde($_GET));
        if (!$h) {
            flash('error', 'El huésped no existe o fue eliminado.');
            $this->redirigir('huespedes/index');
        }
        $this->formulario($h, [], 'editar', (new Reserva())->porHuesped((int) $h['id']));
    }

    public function actualizar(): void
    {
        $id = $this->idDesde($_POST);
        $this->requierePost('huespedes/editar', ['id' => $id]);
        if (!$this->modelo->buscar($id)) {
            flash('error', 'El huésped no existe o fue eliminado.');
            $this->redirigir('huespedes/index');
        }

        [$datos, $errores] = $this->modelo->validar($_POST, $id);
        if ($errores) {
            $datos['id'] = $id;
            $this->formulario($datos, $errores, 'editar', (new Reserva())->porHuesped($id));
            return;
        }
        $this->modelo->actualizar($id, $datos);
        (new Bitacora())->registrar('actualizar', 'huesped', $id, ['nombre' => $datos['nombre_completo']]);
        flash('ok', 'Datos del huésped actualizados.');
        $this->redirigir('huespedes/index');
    }

    public function eliminar(): void
    {
        $this->requierePost('huespedes/index');
        $id = $this->idDesde($_POST);
        $h = $this->modelo->buscar($id);

        if (!$h) {
            flash('error', 'El huésped no existe o ya fue eliminado.');
        } elseif (($n = $this->modelo->contarReservas($id)) > 0) {
            // Integridad referencial: se conserva el historial de reservas
            flash('error', "No se puede eliminar a {$h['nombre_completo']}: tiene $n reserva(s) en el historial. Cancele o conserve sus reservas.");
        } else {
            $this->modelo->eliminar($id);
            (new Bitacora())->registrar('eliminar', 'huesped', $id, ['nombre' => $h['nombre_completo'], 'documento' => $h['numero_documento']]);
            flash('ok', "Huésped {$h['nombre_completo']} eliminado.");
        }
        $this->redirigir('huespedes/index');
    }

    private function formulario(array $h, array $errores, string $modo, array $historial = []): void
    {
        $this->render('huespedes/form', [
            'titulo'    => $modo === 'crear' ? 'Registrar huésped' : 'Editar huésped',
            'h'         => $h,
            'errores'   => $errores,
            'modo'      => $modo,
            'historial' => $historial,
            'volver'    => $_POST['volver'] ?? ($_GET['volver'] ?? ''),
        ]);
    }
}
