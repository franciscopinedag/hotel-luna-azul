-- =====================================================================
--  Hotel Luna Azul - Sistema de Gestión de Reservas
--  Script de creación de base de datos (MySQL 8 / MariaDB 10.4+ de XAMPP)
--  Ejecutar completo en phpMyAdmin > pestaña "SQL" o con:
--    mysql -u root < hotel_luna_azul.sql
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '-05:00';   -- hora de Colombia para las fechas de ejemplo

DROP DATABASE IF EXISTS hotel_luna_azul;
CREATE DATABASE hotel_luna_azul
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE hotel_luna_azul;

-- ---------------------------------------------------------------------
-- 1. Usuarios del sistema (autenticación)
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre          VARCHAR(100) NOT NULL,
  usuario         VARCHAR(40)  NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,          -- bcrypt generado con password_hash() de PHP
  rol             ENUM('administrador','recepcionista') NOT NULL DEFAULT 'recepcionista',
  activo          TINYINT(1)   NOT NULL DEFAULT 1,
  ultimo_acceso   DATETIME     NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_usuarios_usuario UNIQUE (usuario)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. Huéspedes
-- ---------------------------------------------------------------------
CREATE TABLE huespedes (
  id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre_completo        VARCHAR(120) NOT NULL,
  tipo_documento         ENUM('CC','CE','PA','PPT') NOT NULL,
  numero_documento       VARCHAR(20)  NOT NULL,
  direccion              VARCHAR(150) NOT NULL,
  telefono               VARCHAR(15)  NOT NULL,
  celular                VARCHAR(15)  NOT NULL,
  edad                   TINYINT UNSIGNED NOT NULL,
  correo                 VARCHAR(120) NOT NULL,
  contacto_emergencia    VARCHAR(120) NOT NULL,
  parentesco_emergencia  VARCHAR(40)  NOT NULL,
  created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_huesped_documento UNIQUE (tipo_documento, numero_documento),
  CONSTRAINT chk_huesped_edad CHECK (edad BETWEEN 18 AND 110)
) ENGINE=InnoDB;

CREATE INDEX idx_huesped_nombre ON huespedes (nombre_completo);

-- ---------------------------------------------------------------------
-- 3. Tipos de habitación (catálogo)
-- ---------------------------------------------------------------------
CREATE TABLE tipos_habitacion (
  id            TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(40)  NOT NULL,
  capacidad     TINYINT UNSIGNED NOT NULL,
  precio_noche  DECIMAL(10,2) NOT NULL,
  descripcion   VARCHAR(200) NOT NULL,
  CONSTRAINT uq_tipo_nombre UNIQUE (nombre),
  CONSTRAINT chk_tipo_capacidad CHECK (capacidad BETWEEN 1 AND 10)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. Habitaciones físicas
-- ---------------------------------------------------------------------
CREATE TABLE habitaciones (
  id        SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero    VARCHAR(5) NOT NULL,
  tipo_id   TINYINT UNSIGNED NOT NULL,
  piso      TINYINT UNSIGNED NOT NULL,
  estado    ENUM('activa','mantenimiento') NOT NULL DEFAULT 'activa',
  CONSTRAINT uq_habitacion_numero UNIQUE (numero),
  CONSTRAINT fk_habitacion_tipo FOREIGN KEY (tipo_id)
      REFERENCES tipos_habitacion(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. Reservas
-- ---------------------------------------------------------------------
CREATE TABLE reservas (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo         VARCHAR(12)  NULL,
  huesped_id     INT UNSIGNED NOT NULL,
  habitacion_id  SMALLINT UNSIGNED NOT NULL,
  fecha_ingreso  DATE NOT NULL,                 -- check-in
  fecha_salida   DATE NOT NULL,                 -- check-out
  num_huespedes  TINYINT UNSIGNED NOT NULL,
  precio_noche   DECIMAL(10,2) NOT NULL,        -- tarifa congelada al momento de reservar
  valor_total    DECIMAL(12,2) NOT NULL,
  estado         ENUM('confirmada','cancelada','finalizada') NOT NULL DEFAULT 'confirmada',
  observaciones  VARCHAR(255) NULL,
  usuario_id     INT UNSIGNED NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_reserva_codigo UNIQUE (codigo),
  CONSTRAINT chk_reserva_fechas CHECK (fecha_salida > fecha_ingreso),
  CONSTRAINT chk_reserva_personas CHECK (num_huespedes >= 1),
  CONSTRAINT fk_reserva_huesped FOREIGN KEY (huesped_id)
      REFERENCES huespedes(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_reserva_habitacion FOREIGN KEY (habitacion_id)
      REFERENCES habitaciones(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_reserva_usuario FOREIGN KEY (usuario_id)
      REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- Índice que acelera la consulta de cruce de fechas (la que evita la doble asignación)
CREATE INDEX idx_reserva_ocupacion ON reservas (habitacion_id, estado, fecha_ingreso, fecha_salida);

-- =====================================================================
--  DATOS INICIALES
-- =====================================================================

-- Contraseñas: admin -> Admin123*   |   recepcion -> Recepcion123*
INSERT INTO usuarios (nombre, usuario, password_hash, rol) VALUES
('Administrador del sistema', 'admin',
 '$2y$10$GoMacE3HoemO0ZJbj5OwRO4I/pUyn0c2JocN.ALBhlpD3BLkeslye', 'administrador'),
('Recepción turno día', 'recepcion',
 '$2y$10$fiSVP.hOnFDdkpzz1uHEh.9fZ5xTAM25hRIVBvDgUZjz2y.B6Di82', 'recepcionista');

INSERT INTO tipos_habitacion (nombre, capacidad, precio_noche, descripcion) VALUES
('Sencilla', 1, 180000, 'Cama semidoble, escritorio y baño privado'),
('Doble',    2, 260000, 'Cama doble o dos camas sencillas, baño privado'),
('Familiar', 4, 380000, 'Dos camas dobles, sala pequeña y baño privado'),
('Suite',    2, 450000, 'Cama king, sala, minibar y vista al jardín');

INSERT INTO habitaciones (numero, tipo_id, piso, estado) VALUES
('101',1,1,'activa'),('102',1,1,'activa'),('103',1,1,'activa'),
('104',2,1,'activa'),('105',2,1,'activa'),
('201',2,2,'activa'),('202',2,2,'activa'),('203',3,2,'activa'),('204',3,2,'activa'),
('301',4,3,'activa'),('302',4,3,'activa'),('303',4,3,'mantenimiento');

INSERT INTO huespedes (nombre_completo, tipo_documento, numero_documento, direccion, telefono, celular,
                       edad, correo, contacto_emergencia, parentesco_emergencia) VALUES
('Laura Marcela Rincón Díaz','CC','1020456789','Cra 15 # 82-30, Bogotá','6015551234','3104567890',34,'laura.rincon@correo.com','Andrés Rincón','Hermano(a)'),
('Carlos Eduardo Pérez Gómez','CC','80123456','Cl 10 # 43-12, Medellín','6044412233','3157778899',47,'carlos.perez@correo.com','Gloria Gómez','Cónyuge'),
('Ana Sofía Martínez Ruiz','CC','1098765432','Av 6N # 25-40, Cali','6023345566','3001112233',29,'ana.martinez@correo.com','Rosa Ruiz','Madre'),
('John Michael Smith','PA','X1234567','45 Ocean Drive, Miami FL','3055550101','3209876543',52,'john.smith@mail.com','Mary Smith','Cónyuge'),
('Valentina Herrera Castro','CE','4567890','Cl 72 # 11-05, Bogotá','6017654321','3014445566',38,'valentina.herrera@correo.com','Diego Castro','Hermano(a)'),
('Jorge Iván Ospina Mejía','CC','71654987','Cra 43A # 1-50, Medellín','6043216547','3186665544',61,'jorge.ospina@correo.com','Camila Ospina','Hijo(a)');

-- Reservas de demostración relativas a la fecha actual (así el tablero siempre muestra datos)
INSERT INTO reservas (codigo, huesped_id, habitacion_id, fecha_ingreso, fecha_salida, num_huespedes, precio_noche, valor_total, estado, observaciones, usuario_id) VALUES
('LA-00001',1,4, CURDATE() - INTERVAL 1 DAY, CURDATE() + INTERVAL 2 DAY, 2, 260000, 780000,'confirmada','Llegada tarde, después de las 9 p. m.',1),
('LA-00002',2,8, CURDATE(),                  CURDATE() + INTERVAL 4 DAY, 4, 380000,1520000,'confirmada','Viaja con dos niños',1),
('LA-00003',3,1, CURDATE() + INTERVAL 2 DAY, CURDATE() + INTERVAL 5 DAY, 1, 180000, 540000,'confirmada',NULL,2),
('LA-00004',4,10,CURDATE() + INTERVAL 1 DAY, CURDATE() + INTERVAL 6 DAY, 2, 450000,2250000,'confirmada','Solicita traslado desde el aeropuerto',1),
('LA-00005',5,6, CURDATE() - INTERVAL 3 DAY, CURDATE(),                  2, 260000, 780000,'confirmada',NULL,2),
('LA-00006',6,2, CURDATE() + INTERVAL 5 DAY, CURDATE() + INTERVAL 8 DAY, 1, 180000, 540000,'confirmada',NULL,2),
('LA-00007',1,11,CURDATE() - INTERVAL 20 DAY,CURDATE() - INTERVAL 17 DAY,2, 450000,1350000,'finalizada',NULL,1),
('LA-00008',3,5, CURDATE() + INTERVAL 3 DAY, CURDATE() + INTERVAL 6 DAY, 2, 260000, 780000,'cancelada','Cancelada por el huésped',2);
