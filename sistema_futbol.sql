-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('superadmin', 'admin', 'planillero') NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    codigo_planillero VARCHAR(10) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de campeonatos
CREATE TABLE campeonatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de categorías
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campeonato_id INT NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    activa BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (campeonato_id) REFERENCES campeonatos(id) ON DELETE CASCADE
);

-- Tabla de equipos
CREATE TABLE equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    logo VARCHAR(255),
    color_camiseta VARCHAR(50),
    director_tecnico VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);

-- Tabla de canchas
CREATE TABLE canchas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    ubicacion VARCHAR(200),
    activa BOOLEAN DEFAULT TRUE
);

-- Tabla de jugadores
CREATE TABLE jugadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT NOT NULL,
    dni VARCHAR(20) UNIQUE NOT NULL,
    apellido_nombre VARCHAR(150) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    foto VARCHAR(255),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
);

-- Tabla de fechas/jornadas
CREATE TABLE fechas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    numero_fecha INT NOT NULL,
    fecha_programada DATE NOT NULL,
    activa BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);

-- Tabla de partidos
CREATE TABLE partidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_id INT NOT NULL,
    equipo_local_id INT NOT NULL,
    equipo_visitante_id INT NOT NULL,
    cancha_id INT NOT NULL,
    fecha_partido DATE NOT NULL,
    hora_partido TIME NOT NULL,
    goles_local INT DEFAULT 0,
    goles_visitante INT DEFAULT 0,
    estado ENUM('programado', 'en_curso', 'finalizado', 'suspendido') DEFAULT 'programado',
    minuto_actual INT DEFAULT 0,
    tiempo_actual ENUM('primer_tiempo', 'descanso', 'segundo_tiempo', 'finalizado') DEFAULT 'primer_tiempo',
    iniciado_at TIMESTAMP NULL,
    finalizado_at TIMESTAMP NULL,
    FOREIGN KEY (fecha_id) REFERENCES fechas(id) ON DELETE CASCADE,
    FOREIGN KEY (equipo_local_id) REFERENCES equipos(id),
    FOREIGN KEY (equipo_visitante_id) REFERENCES equipos(id),
    FOREIGN KEY (cancha_id) REFERENCES canchas(id)
);

-- Tabla de eventos del partido
CREATE TABLE eventos_partido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    jugador_id INT NOT NULL,
    tipo_evento ENUM('gol', 'amarilla', 'roja', 'cambio') NOT NULL,
    minuto INT NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE,
    FOREIGN KEY (jugador_id) REFERENCES jugadores(id)
);

-- Crear tabla de planilleros
CREATE TABLE planilleros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  dni VARCHAR(20) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  cancha_id INT NOT NULL,
  activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (cancha_id) REFERENCES canchas(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Tabla de sanciones
CREATE TABLE sanciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jugador_id INT NOT NULL,
    tipo ENUM('amarillas_acumuladas', 'doble_amarilla', 'roja_directa', 'administrativa') NOT NULL,
    partidos_suspension INT NOT NULL,
    partidos_cumplidos INT DEFAULT 0,
    descripcion TEXT,
    activa BOOLEAN DEFAULT TRUE,
    fecha_sancion DATE NOT NULL,
    FOREIGN KEY (jugador_id) REFERENCES jugadores(id) ON DELETE CASCADE
);

-- Tabla de planillas
CREATE TABLE planillas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    planillero_id INT NOT NULL,
    codigo_acceso VARCHAR(10) NOT NULL,
    descargada BOOLEAN DEFAULT FALSE,
    fecha_descarga TIMESTAMP NULL,
    FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE,
    FOREIGN KEY (planillero_id) REFERENCES usuarios(id)
);

-- Insertar usuario superadmin por defecto (contraseña: password)
INSERT INTO usuarios (username, password, email, nombre, tipo) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@futbol.com', 'Administrador', 'superadmin');

-- Insertar datos de ejemplo
INSERT INTO canchas (nombre, ubicacion) VALUES 
('Cancha Central', 'Av. Principal 123'),
('Cancha Norte', 'Barrio Norte s/n'),
('Cancha Sur', 'Zona Sur 456');

-- Datos de ejemplo para testing
INSERT INTO campeonatos (nombre, descripcion, fecha_inicio) VALUES
('Torneo Apertura 2024', 'Campeonato de apertura del año 2024', '2024-03-01');

INSERT INTO categorias (campeonato_id, nombre, descripcion) VALUES
(1, 'Primera División', 'Categoría principal del torneo'),
(1, 'Veteranos +35', 'Para jugadores mayores de 35 años');

-- Crear vistas para estadísticas
CREATE VIEW tabla_posiciones AS
SELECT 
    e.id as equipo_id,
    e.nombre as equipo,
    COUNT(p.id) as partidos_jugados,
    SUM(CASE 
        WHEN (p.equipo_local_id = e.id AND p.goles_local > p.goles_visitante) OR 
             (p.equipo_visitante_id = e.id AND p.goles_visitante > p.goles_local) 
        THEN 1 ELSE 0 END) as ganados,
    SUM(CASE 
        WHEN p.goles_local = p.goles_visitante AND p.estado = 'finalizado'
        THEN 1 ELSE 0 END) as empatados,
    SUM(CASE 
        WHEN (p.equipo_local_id = e.id AND p.goles_local < p.goles_visitante) OR 
             (p.equipo_visitante_id = e.id AND p.goles_visitante < p.goles_local) 
        THEN 1 ELSE 0 END) as perdidos,
    SUM(CASE 
        WHEN p.equipo_local_id = e.id THEN p.goles_local 
        WHEN p.equipo_visitante_id = e.id THEN p.goles_visitante 
        ELSE 0 END) as goles_favor,
    SUM(CASE 
        WHEN p.equipo_local_id = e.id THEN p.goles_visitante 
        WHEN p.equipo_visitante_id = e.id THEN p.goles_local 
        ELSE 0 END) as goles_contra,
    (SUM(CASE 
        WHEN p.equipo_local_id = e.id THEN p.goles_local 
        WHEN p.equipo_visitante_id = e.id THEN p.goles_visitante 
        ELSE 0 END) - SUM(CASE 
        WHEN p.equipo_local_id = e.id THEN p.goles_visitante 
        WHEN p.equipo_visitante_id = e.id THEN p.goles_local 
        ELSE 0 END)) as diferencia_goles,
    (SUM(CASE 
        WHEN (p.equipo_local_id = e.id AND p.goles_local > p.goles_visitante) OR 
             (p.equipo_visitante_id = e.id AND p.goles_visitante > p.goles_local) 
        THEN 3
        WHEN p.goles_local = p.goles_visitante AND p.estado = 'finalizado'
        THEN 1 ELSE 0 END)) as puntos
FROM equipos e
LEFT JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id) AND p.estado = 'finalizado'
GROUP BY e.id, e.nombre
ORDER BY puntos DESC, diferencia_goles DESC, goles_favor DESC;

CREATE VIEW tabla_goleadores AS
SELECT 
    j.id as jugador_id,
    j.apellido_nombre,
    e.nombre as equipo,
    COUNT(ev.id) as goles,
    j.fecha_nacimiento
FROM jugadores j
JOIN equipos e ON j.equipo_id = e.id
LEFT JOIN eventos_partido ev ON j.id = ev.jugador_id AND ev.tipo_evento = 'gol'
GROUP BY j.id, j.apellido_nombre, e.nombre, j.fecha_nacimiento
HAVING goles > 0
ORDER BY goles DESC, j.apellido_nombre;