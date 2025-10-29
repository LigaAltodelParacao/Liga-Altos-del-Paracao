-- =====================================
-- Base de datos: Sistema de Torneos
-- =====================================

-- ========================
-- Tabla de usuarios
-- ========================
CREATE TABLE IF NOT EXISTS usuarios (
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

-- ========================
-- Tabla de campeonatos
-- ========================
CREATE TABLE IF NOT EXISTS campeonatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================
-- Tabla de categorías
-- ========================
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campeonato_id INT NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    activa BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (campeonato_id) REFERENCES campeonatos(id) ON DELETE CASCADE
);

-- ========================
-- Tabla de equipos
-- ========================
CREATE TABLE IF NOT EXISTS equipos (
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

-- ========================
-- Tabla de canchas
-- ========================
CREATE TABLE IF NOT EXISTS canchas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    ubicacion VARCHAR(200),
    activa BOOLEAN DEFAULT TRUE
);

-- ========================
-- Tabla de jugadores
-- ========================
CREATE TABLE IF NOT EXISTS jugadores (
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

-- ========================
-- Tabla de fechas/jornadas
-- ========================
CREATE TABLE IF NOT EXISTS fechas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    numero_fecha INT NOT NULL,
    fecha_programada DATE NOT NULL,
    activa BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);

-- ========================
-- Tabla de partidos
-- ========================
CREATE TABLE IF NOT EXISTS partidos (
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

-- ========================
-- Tabla de eventos del partido
-- ========================
CREATE TABLE IF NOT EXISTS eventos_partido (
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

-- ========================
-- Tabla de sanciones
-- ========================
CREATE TABLE IF NOT EXISTS sanciones (
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

-- ========================
-- Tabla de planillas
-- ========================
CREATE TABLE IF NOT EXISTS planillas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    planillero_id INT NOT NULL,
    codigo_acceso VARCHAR(10) NOT NULL,
    descargada BOOLEAN DEFAULT FALSE,
    fecha_descarga TIMESTAMP NULL,
    FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE,
    FOREIGN KEY (planillero_id) REFERENCES usuarios(id)
);

-- ========================
-- Insertar superadmin por defecto
-- ========================
INSERT INTO usuarios (username, password, email, nombre, tipo) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@futbol.com', 'Administrador', 'superadmin');

-- ========================
-- Insertar canchas
-- ========================
INSERT INTO canchas (nombre, ubicacion) VALUES 
('Cancha Central', 'Av. Principal 123'),
('Cancha Norte', 'Barrio Norte s/n'),
('Cancha Sur', 'Zona Sur 456');

-- ========================
-- Insertar campeonato de ejemplo
-- ========================
INSERT INTO campeonatos (nombre, descripcion, fecha_inicio) VALUES
('Torneo Apertura 2024', 'Campeonato de apertura del año 2024', '2024-03-01');

-- ========================
-- Insertar categorías iniciales + nuevas
-- ========================
INSERT INTO categorias (campeonato_id, nombre, descripcion) VALUES
(1, 'Primera División', 'Categoría principal del torneo'),
(1, 'Veteranos +35', 'Para jugadores mayores de 35 años'),
(1, 'M40', 'Categoría M40 del Torneo Apertura 2024'),
(1, 'M30 A', 'Categoría M30 A del Torneo Apertura 2024'),
(1, 'M30 B', 'Categoría M30 B del Torneo Apertura 2024');

-- ========================
-- Insertar equipos M40
-- ========================
SET @cat_m40 = (SELECT id FROM categorias WHERE nombre='M40');
INSERT INTO equipos (categoria_id, nombre, activo) VALUES
(@cat_m40, 'Distribuidora Tata', TRUE),
(@cat_m40, 'La Pingüina M40', TRUE),
(@cat_m40, 'Nono Gringo M40', TRUE),
(@cat_m40, 'Camioneros M40', TRUE),
(@cat_m40, 'AVA M40', TRUE),
(@cat_m40, 'Agrupación Roma M40', TRUE),
(@cat_m40, 'Avenida Distribuciones M40', TRUE),
(@cat_m40, 'Taladro M40', TRUE),
(@cat_m40, 'Villa Urquiza M40', TRUE),
(@cat_m40, 'AFCAPER M40', TRUE),
(@cat_m40, 'Farmacia Abril', TRUE),
(@cat_m40, 'El Fortin M40', TRUE),
(@cat_m40, 'Arrecife M40', TRUE),
(@cat_m40, 'Agrupación Amadeus M40', TRUE),
(@cat_m40, 'Agrupación Mariano Moreno FC M40', TRUE);

-- ========================
-- Insertar equipos M30 A
-- ========================
SET @cat_m30a = (SELECT id FROM categorias WHERE nombre='M30 A');
INSERT INTO equipos (categoria_id, nombre, activo) VALUES
(@cat_m30a, 'Agrupación La Chimenea M30', TRUE),
(@cat_m30a, 'Atlético Las Rosas M30', TRUE),
(@cat_m30a, 'Coco´s Team M30', TRUE),
(@cat_m30a, 'Deportivo Branca M30', TRUE),
(@cat_m30a, 'Ever + 10 M30', TRUE),
(@cat_m30a, 'Hay equipo M30', TRUE),
(@cat_m30a, 'La Pingüina M30', TRUE),
(@cat_m30a, 'La Rossana Futbol Ranch M30', TRUE),
(@cat_m30a, 'Librería Francisco M30', TRUE),
(@cat_m30a, 'Los Amigos M30', TRUE),
(@cat_m30a, 'Monos Team M30', TRUE),
(@cat_m30a, 'Noreste M30', TRUE),
(@cat_m30a, 'Olympiakos M30', TRUE),
(@cat_m30a, 'Once Calvas M30', TRUE),
(@cat_m30a, 'PSV FC M30', TRUE),
(@cat_m30a, 'Santos M30', TRUE),
(@cat_m30a, 'Sportivo Rustico M30', TRUE),
(@cat_m30a, 'Ta Lento M30', TRUE),
(@cat_m30a, 'Unión de Amigos M30', TRUE),
(@cat_m30a, 'Vialenses FC M30', TRUE);

-- ========================
-- Insertar equipos M30 B
-- ========================
SET @cat_m30b = (SELECT id FROM categorias WHERE nombre='M30 B');
INSERT INTO equipos (categoria_id, nombre, activo) VALUES
(@cat_m30b, 'Unión de Viale M30', TRUE),
(@cat_m30b, 'Mistico M30', TRUE),
(@cat_m30b, 'Bar Munich - Der Klub M30', TRUE),
(@cat_m30b, 'Nono Gringo M30', TRUE),
(@cat_m30b, 'Las Rosas M30', TRUE),
(@cat_m30b, 'LS Celulares M30', TRUE),
(@cat_m30b, 'Erio FC M30', TRUE),
(@cat_m30b, 'Los Murcielagos FC', TRUE),
(@cat_m30b, 'Paso a Paso M30', TRUE),
(@cat_m30b, 'Los del Palmar M30', TRUE),
(@cat_m30b, 'Atlético Yerman M30', TRUE),
(@cat_m30b, 'Tercer Tiempo M30', TRUE),
(@cat_m30b, 'TT Fútbol Club M30', TRUE),
(@cat_m30b, 'AQNV M30', TRUE),
(@cat_m30b, 'Bayer FC M30', TRUE),
(@cat_m30b, 'Panteras M30', TRUE),
(@cat_m30b, 'Celtic Paraná M30', TRUE),
(@cat_m30b, 'La 20 FC M30', TRUE),
(@cat_m30b, 'Gambeta FC M30', TRUE),
(@cat_m30b, 'RGB M30', TRUE);

-- ========================
-- Crear vistas estadísticas
-- ========================
CREATE OR REPLACE VIEW tabla_posiciones AS
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

CREATE OR REPLACE VIEW tabla_goleadores AS
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
