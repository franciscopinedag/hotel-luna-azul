/* =====================================================================
   Hotel Luna Azul · Lógica del cliente (JavaScript ES2015+)
   1. Utilidades y petición AJAX (fetch)
   2. Validación de formularios en el navegador
   3. Verificación AJAX de documento duplicado
   4. Búsqueda en vivo de huéspedes (AJAX)
   5. Disponibilidad de habitaciones y cálculo de la reserva (AJAX)
   6. Diálogo de confirmación, menú móvil y mostrar contraseña
   ===================================================================== */
(() => {
  'use strict';

  // ---------------------------------------------------------------
  // 1. Utilidades
  // ---------------------------------------------------------------
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

  const escapar = (txt) => String(txt ?? '').replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));

  const moneda = (v) => '$ ' + Math.round(v).toLocaleString('es-CO');

  const hoyISO = () => {
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 10);
  };

  const diasEntre = (a, b) => Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000);

  const debounce = (fn, ms) => {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
  };

  const tokenCsrf = () => $('meta[name="csrf-token"]')?.content || $('input[name="_csrf"]')?.value || '';

  /** Petición AJAX que devuelve JSON. Si la sesión expiró, recarga para ir al login. */
  async function pedirJSON(url, { signal } = {}) {
    const resp = await fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      credentials: 'same-origin',
      signal,
    });
    if (resp.status === 401) {
      window.location.reload();
      throw new Error('Sesión expirada');
    }
    const datos = await resp.json();
    if (!resp.ok && !datos) throw new Error('Error ' + resp.status);
    return datos;
  }

  const conParams = (base, params) => base + (base.includes('?') ? '&' : '?') + new URLSearchParams(params).toString();

  // ---------------------------------------------------------------
  // 2. Validación de formularios
  //    Cada campo declara su regla con data-regla="...".
  //    Las mismas reglas se repiten en PHP (nunca se confía solo en el cliente).
  // ---------------------------------------------------------------
  const SOLO_LETRAS = /^[\p{L}\s'.-]+$/u;
  const CORREO = /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i;

  const REGLAS_DOCUMENTO = {
    CC: [/^\d{6,10}$/, 'La cédula debe tener entre 6 y 10 dígitos.'],
    CE: [/^\d{6,10}$/, 'La cédula de extranjería debe tener entre 6 y 10 dígitos.'],
    PA: [/^[A-Z0-9]{5,12}$/, 'El pasaporte debe tener entre 5 y 12 letras o números.'],
    PPT: [/^\d{6,12}$/, 'El PPT debe tener entre 6 y 12 dígitos.'],
  };

  const reglas = {
    requerido: (v) => (v ? '' : 'Este campo es obligatorio.'),

    usuario: (v) => (!v ? 'Escriba su usuario.' : (!/^[\w.-]{3,40}$/.test(v) ? 'El usuario solo admite letras, números, punto y guion.' : '')),

    nombre: (v) => {
      if (!v) return 'Escriba el nombre completo.';
      if (v.length < 5 || !SOLO_LETRAS.test(v)) return 'Use solo letras (mínimo 5 caracteres).';
      if (v.trim().split(/\s+/).length < 2) return 'Incluya al menos un nombre y un apellido.';
      return '';
    },

    documento: (v, campo) => {
      const tipo = $('#tipo_documento')?.value;
      const limpio = v.replace(/[.\s-]/g, '').toUpperCase();
      if (!limpio) return 'Escriba el número de documento.';
      if (!tipo) return 'Seleccione primero el tipo de documento.';
      const [patron, msg] = REGLAS_DOCUMENTO[tipo];
      if (!patron.test(limpio)) return msg;
      if (campo.dataset.duplicado === '1') return 'Ya existe un huésped registrado con este documento.';
      return '';
    },

    edad: (v) => {
      const n = Number(v);
      if (v === '') return 'Indique la edad.';
      return Number.isInteger(n) && n >= 18 && n <= 110 ? '' : 'El titular debe ser mayor de edad (18 a 110 años).';
    },

    direccion: (v) => (v.length >= 5 && v.length <= 150 ? '' : 'Escriba una dirección válida (mínimo 5 caracteres).'),

    telefono: (v) => (/^\d{7,10}$/.test(v.replace(/\D/g, '')) ? '' : 'El teléfono debe tener entre 7 y 10 dígitos.'),

    celular: (v) => (/^3\d{9}$/.test(v.replace(/\D/g, '')) ? '' : 'El celular debe tener 10 dígitos y empezar por 3.'),

    correo: (v) => (CORREO.test(v) ? '' : 'Escriba un correo válido, por ejemplo nombre@dominio.com.'),

    contacto: (v) => (v.length >= 3 && SOLO_LETRAS.test(v) ? '' : 'Escriba el nombre de la persona de contacto (solo letras).'),

    ingreso: (v, campo) => {
      if (!v) return 'Indique la fecha de ingreso.';
      const original = campo.form.dataset.ingresoOriginal;
      if (v < hoyISO() && v !== original) return 'La fecha de ingreso no puede ser anterior a hoy.';
      return '';
    },

    salida: (v, campo) => {
      const ingreso = $('#fecha_ingreso', campo.form).value;
      const max = Number(campo.form.dataset.maxNoches || 30);
      if (!v) return 'Indique la fecha de salida.';
      if (ingreso && v <= ingreso) return 'La salida debe ser al menos un día después del ingreso.';
      if (ingreso && diasEntre(ingreso, v) > max) return `La estadía máxima por reserva es de ${max} noches.`;
      return '';
    },

    personas: (v, campo) => {
      const n = Number(v);
      if (!Number.isInteger(n) || n < 1) return 'Indique cuántas personas se hospedan.';
      const opcion = $('#tipo_id', campo.form)?.selectedOptions[0];
      const cap = Number(opcion?.dataset.capacidad || 0);
      if (cap && n > cap) return `Este tipo de habitación admite máximo ${cap} persona(s).`;
      return '';
    },
  };

  function mostrarError(campo, mensaje) {
    const contenedor = campo.closest('.campo');
    if (!contenedor) return;
    const salida = $('.error-campo', contenedor);
    if (salida) salida.textContent = mensaje;
    contenedor.classList.toggle('con-error', !!mensaje);
    contenedor.classList.toggle('valido', !mensaje && campo.value !== '');
    campo.setAttribute('aria-invalid', mensaje ? 'true' : 'false');
  }

  function validarCampo(campo) {
    const regla = reglas[campo.dataset.regla];
    if (!regla) return true;
    const msg = regla(campo.value.trim(), campo);
    mostrarError(campo, msg);
    return !msg;
  }

  function prepararValidacion(form) {
    const campos = $$('[data-regla]', form);

    campos.forEach((campo) => {
      campo.addEventListener('blur', () => { campo.dataset.tocado = '1'; validarCampo(campo); });
      campo.addEventListener('input', () => { if (campo.dataset.tocado) validarCampo(campo); });
      campo.addEventListener('change', () => { campo.dataset.tocado = '1'; validarCampo(campo); });
    });

    form.addEventListener('submit', (ev) => {
      let primero = null;
      campos.forEach((c) => { if (!validarCampo(c) && !primero) primero = c; });

      // Validaciones extra que dependen del formulario (p. ej. habitación elegida)
      const extra = form._validacionExtra ? form._validacionExtra() : null;
      if (primero || extra) {
        ev.preventDefault();
        (primero || extra).focus();
        return;
      }
      const boton = $('button[type="submit"]', form);
      if (boton) { boton.disabled = true; boton.textContent = 'Guardando…'; }
    });
  }

  $$('form[data-validar]').forEach(prepararValidacion);

  // ---------------------------------------------------------------
  // 3. Documento duplicado (AJAX) en el formulario de huésped
  // ---------------------------------------------------------------
  const formHuesped = $('#form-huesped');
  if (formHuesped) {
    const numero = $('#numero_documento');
    const tipo = $('#tipo_documento');
    const idActual = $('input[name="id"]', formHuesped).value;
    let control;

    const verificar = async () => {
      numero.dataset.duplicado = '0';
      if (!validarCampo(numero)) return;           // primero la regla local
      control?.abort();
      control = new AbortController();
      const contenedor = numero.closest('.campo');
      contenedor.classList.add('verificando');
      $('.error-campo', contenedor).textContent = 'Verificando documento…';
      try {
        const r = await pedirJSON(conParams(formHuesped.dataset.endpointDocumento, {
          tipo: tipo.value, numero: numero.value, excluir: idActual,
        }), { signal: control.signal });
        numero.dataset.duplicado = r.existe ? '1' : '0';
      } catch (e) {
        if (e.name === 'AbortError') return;
      } finally {
        contenedor.classList.remove('verificando');
      }
      validarCampo(numero);
    };

    numero.addEventListener('blur', verificar);
    tipo.addEventListener('change', () => { if (numero.value) verificar(); });
    numero.addEventListener('input', () => { numero.dataset.duplicado = '0'; });
  }

  // ---------------------------------------------------------------
  // 4. Búsqueda en vivo de huéspedes (AJAX)
  // ---------------------------------------------------------------
  const buscador = $('#buscar-huesped');
  if (buscador) {
    const tabla = $('#tabla-huespedes');
    const cuerpo = $('tbody', tabla);
    const conteo = $('#conteo-huespedes');
    const sinResultados = $('#sin-resultados');
    let control;

    const fila = (h) => `
      <tr>
        <td>${escapar(h.nombre_completo)}</td>
        <td><span class="doc">${escapar(h.tipo_documento)}</span> ${escapar(h.numero_documento)}</td>
        <td>${escapar(h.celular)}</td>
        <td>${escapar(h.correo)}</td>
        <td class="num">${h.edad}</td>
        <td class="num">${h.total_reservas}</td>
        <td class="acciones-fila">
          <a href="${escapar(conParams(tabla.dataset.urlReservar, { huesped_id: h.id }))}">Reservar</a>
          <a href="${escapar(conParams(tabla.dataset.urlEditar, { id: h.id }))}">Editar</a>
          <button type="button" class="enlace-peligro" data-titulo="Eliminar huésped"
            data-confirmar="¿Eliminar a ${escapar(h.nombre_completo)}? Esta acción no se puede deshacer."
            data-accion="${escapar(tabla.dataset.urlEliminar)}" data-id="${h.id}">Eliminar</button>
        </td>
      </tr>`;

    const buscar = debounce(async () => {
      control?.abort();
      control = new AbortController();
      buscador.parentElement.classList.add('cargando');
      try {
        const r = await pedirJSON(conParams(buscador.dataset.endpoint, { q: buscador.value.trim() }), { signal: control.signal });
        cuerpo.innerHTML = r.huespedes.map(fila).join('');
        conteo.textContent = `${r.total} registrado(s)` + (buscador.value.trim() ? ' que coinciden' : '');
        sinResultados.hidden = r.total > 0;
        const url = new URL(window.location);
        buscador.value.trim() ? url.searchParams.set('q', buscador.value.trim()) : url.searchParams.delete('q');
        history.replaceState(null, '', url);
      } catch (e) {
        if (e.name !== 'AbortError') conteo.textContent = 'No se pudo completar la búsqueda.';
      } finally {
        buscador.parentElement.classList.remove('cargando');
      }
    }, 300);

    buscador.addEventListener('input', buscar);
  }

  // ---------------------------------------------------------------
  // 5. Reserva: disponibilidad (AJAX) y resumen de valores
  // ---------------------------------------------------------------
  const formReserva = $('#form-reserva');
  if (formReserva) {
    const tipo = $('#tipo_id');
    const ingreso = $('#fecha_ingreso');
    const salida = $('#fecha_salida');
    const personas = $('#num_huespedes');
    const opciones = $('#habitaciones-opciones');
    const estado = $('#estado-disponibilidad');
    const errorHab = $('#error-habitacion');
    const bloqueHab = $('#bloque-habitaciones');
    const res = {
      noches: $('#res-noches'), tarifa: $('#res-tarifa'),
      habitacion: $('#res-habitacion'), total: $('#res-total'),
    };
    let seleccionPrevia = formReserva.dataset.habitacionActual || '';
    let control;

    const limpiarResumen = () => { Object.values(res).forEach((el) => { el.textContent = '—'; }); };

    const actualizarResumenHabitacion = () => {
      const marcada = $('input[name="habitacion_id"]:checked', opciones);
      res.habitacion.textContent = marcada ? marcada.dataset.numero : '—';
      if (marcada) {
        seleccionPrevia = marcada.value;
        errorHab.textContent = '';
        bloqueHab.classList.remove('con-error');
      }
    };

    const ajustarCapacidad = () => {
      const cap = tipo.selectedOptions[0]?.dataset.capacidad;
      personas.max = cap || 10;
      if (personas.value) validarCampo(personas);
    };

    const consultar = debounce(async () => {
      const listo = tipo.value && ingreso.value && salida.value && salida.value > ingreso.value;
      if (!listo) {
        opciones.innerHTML = '';
        estado.textContent = 'Elija el tipo de habitación y las fechas para ver qué habitaciones están libres.';
        limpiarResumen();
        return;
      }
      control?.abort();
      control = new AbortController();
      estado.textContent = 'Consultando disponibilidad';
      estado.classList.add('cargando');
      try {
        const r = await pedirJSON(conParams(formReserva.dataset.endpointDisponibilidad, {
          tipo_id: tipo.value, ingreso: ingreso.value, salida: salida.value,
          excluir: formReserva.dataset.reservaId,
        }), { signal: control.signal });

        if (!r.ok) {
          estado.textContent = r.mensaje;
          opciones.innerHTML = '';
          limpiarResumen();
          return;
        }

        opciones.innerHTML = r.habitaciones.map((h) => `
          <label class="hab-opcion">
            <input type="radio" name="habitacion_id" value="${h.id}" data-numero="${escapar(h.numero)}"
              ${String(h.id) === String(seleccionPrevia) ? 'checked' : ''}>
            <span><strong>${escapar(h.numero)}</strong><small>Piso ${h.piso}</small></span>
          </label>`).join('');

        // Si solo hay una habitación libre, se selecciona automáticamente
        if (r.habitaciones.length === 1 && !$('input:checked', opciones)) {
          $('input', opciones).checked = true;
        }

        estado.textContent = r.habitaciones.length
          ? `${r.mensaje} Seleccione una.`
          : `${r.mensaje} Pruebe otro tipo de habitación o cambie las fechas.`;
        res.noches.textContent = r.noches;
        res.tarifa.textContent = moneda(r.precio_noche);
        res.total.textContent = moneda(r.total);
        actualizarResumenHabitacion();
      } catch (e) {
        if (e.name !== 'AbortError') estado.textContent = 'No se pudo consultar la disponibilidad. Intente de nuevo.';
      } finally {
        estado.classList.remove('cargando');
      }
    }, 250);

    // La fecha de salida mínima es el día siguiente al ingreso
    const ajustarMinSalida = () => {
      if (!ingreso.value) return;
      const d = new Date(ingreso.value + 'T00:00:00');
      d.setDate(d.getDate() + 1);
      salida.min = d.toISOString().slice(0, 10);
      if (salida.value && salida.value <= ingreso.value) {
        salida.value = salida.min;
      }
    };

    tipo.addEventListener('change', () => { ajustarCapacidad(); consultar(); });
    ingreso.addEventListener('change', () => { ajustarMinSalida(); if (salida.value) validarCampo(salida); consultar(); });
    salida.addEventListener('change', consultar);
    opciones.addEventListener('change', actualizarResumenHabitacion);

    formReserva._validacionExtra = () => {
      if ($('input[name="habitacion_id"]:checked', opciones)) return null;
      errorHab.textContent = opciones.children.length
        ? 'Seleccione una de las habitaciones disponibles.'
        : 'No hay una habitación disponible seleccionada para estas fechas.';
      bloqueHab.classList.add('con-error');
      return tipo;
    };

    ajustarCapacidad();
    ajustarMinSalida();
    consultar();   // en edición o tras un error del servidor, carga la disponibilidad
  }

  // ---------------------------------------------------------------
  // 6. Diálogo de confirmación (eliminar / cancelar / finalizar)
  // ---------------------------------------------------------------
  const dialogo = $('#dlg-confirmar');
  if (dialogo) {
    let pendiente = null;

    document.addEventListener('click', (ev) => {
      const boton = ev.target.closest('[data-confirmar]');
      if (!boton) return;
      pendiente = boton;
      $('#dlg-titulo').textContent = boton.dataset.titulo || 'Confirmar';
      $('#dlg-mensaje').textContent = boton.dataset.confirmar;
      $('#dlg-aceptar').textContent = boton.dataset.boton || boton.dataset.titulo || 'Confirmar';
      $('#dlg-motivo-campo').hidden = !('motivo' in boton.dataset);
      $('#dlg-motivo').value = '';
      dialogo.showModal();
    });

    dialogo.addEventListener('close', () => {
      if (dialogo.returnValue !== 'si' || !pendiente) return;
      const form = document.createElement('form');
      form.method = 'post';
      form.action = pendiente.dataset.accion;
      const campos = { _csrf: tokenCsrf(), id: pendiente.dataset.id, motivo: $('#dlg-motivo').value };
      Object.entries(campos).forEach(([nombre, valor]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = nombre;
        input.value = valor;
        form.appendChild(input);
      });
      document.body.appendChild(form);
      form.submit();
    });
  }

  // ---------------------------------------------------------------
  // Menú móvil y mostrar/ocultar contraseña
  // ---------------------------------------------------------------
  const botonMenu = $('.menu-movil');
  if (botonMenu) {
    botonMenu.addEventListener('click', () => {
      const abierto = $('#lateral').classList.toggle('abierto');
      botonMenu.setAttribute('aria-expanded', abierto);
    });
  }

  $$('.ver-clave').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.getAttribute('aria-controls'));
      const ver = input.type === 'password';
      input.type = ver ? 'text' : 'password';
      btn.textContent = ver ? 'Ocultar' : 'Mostrar';
    });
  });
})();
