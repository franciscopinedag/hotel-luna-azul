# Hotel Luna Azul · Sistema de gestión de reservas

Aplicación web en **PHP 8 + MySQL** con patrón **MVC**, validaciones en el cliente con **JavaScript y AJAX**,
autenticación segura y una bitácora **no relacional** en documentos JSON. Incluye un script en **Python**
que exporta la base relacional a colecciones de documentos (modelo NoSQL tipo MongoDB).

## Requisitos
- XAMPP 8.x (Apache + MySQL/MariaDB + PHP 8.1 o superior, con `pdo_mysql` y `mbstring`, activos por defecto)
- Navegador moderno (Chrome, Edge, Firefox)
- Opcional: Python 3.10+ con `pip install pymysql`

## Instalación en XAMPP
1. Copie la carpeta `hotel_luna_azul` dentro de `C:\xampp\htdocs\`.
2. Abra el panel de XAMPP y pulse **Start** en **Apache** y **MySQL**.
3. Entre a `http://localhost/phpmyadmin`, abra la pestaña **Importar** y cargue
   `database/hotel_luna_azul.sql` (crea la base `hotel_luna_azul` con datos de ejemplo).
4. Si su usuario `root` de MySQL tiene contraseña, escríbala en `config/config.php` (`DB_PASS`).
5. Abra `http://localhost/hotel_luna_azul/`

| Usuario    | Contraseña      | Rol            |
|------------|-----------------|----------------|
| admin      | Admin123*       | Administrador  |
| recepcion  | Recepcion123*   | Recepcionista  |

## Estructura (MVC)
```
hotel_luna_azul/
├── index.php                 Front controller: recibe todas las peticiones (?r=controlador/accion)
├── config/config.php         Constantes de conexión, seguridad y reglas de negocio
├── app/
│   ├── core/                 Router, Controller y Model base, Database (PDO), helpers
│   ├── models/               Huesped, Reserva, Habitacion, Usuario, Bitacora (JSON)
│   ├── controllers/          Auth, Dashboard, Huesped, Reserva, Habitacion, Bitacora, Api (AJAX)
│   └── views/                Vistas HTML5 por módulo + layout
├── public/css/styles.css     Estilos CSS3 (responsivo)
├── public/js/app.js          Validaciones, AJAX, diálogos
├── database/hotel_luna_azul.sql
├── storage/                  bitacora.jsonl (base no relacional) y exportaciones
└── python/exportar_nosql.py  Exportación a documentos JSON / MongoDB
```

## Funcionalidades
- Inicio de sesión con contraseñas bcrypt, bloqueo tras 5 intentos, CSRF y cierre por inactividad.
- CRUD completo de huéspedes con los 10 datos exigidos y validación en cliente y servidor.
- Registro, consulta, modificación, cancelación y check-out de reservas.
- **Sin doble asignación:** consulta de cruce de fechas + transacción con `SELECT ... FOR UPDATE`.
- Disponibilidad de habitaciones en tiempo real por AJAX y cálculo automático del valor.
- Panel con indicadores del día y tablero de ocupación de 14 días.
- Bitácora de auditoría no relacional (un documento JSON por evento).

## Script Python (opcional)
```
cd python
pip install pymysql
python exportar_nosql.py
```
Genera `storage/nosql/reservas_documentos.json`, `huespedes_documentos.json` y `reporte_ocupacion.json`.
Con la variable `MONGO_URI` y `pymongo` instalado, también inserta las colecciones en MongoDB.
