# Documentación: Normalización de Torneos Largos vs Zonales

## Resumen

Se ha normalizado la base de datos y el código del sistema de torneos para distinguir correctamente entre:
- **Campeonatos Largos**: Apertura, Clausura, etc.
- **Torneos por Zonas**: Torneo Nocturno, Torneo Relámpago, etc.

Las estadísticas ahora se separan completamente entre ambos tipos, excepto las tarjetas rojas que se comparten entre ambos tipos de torneo.

## Cambios en la Base de Datos

### 1. Tabla `campeonatos`
- **Nuevo campo**: `tipo_campeonato` ENUM('largo', 'zonal')
  - Se calcula automáticamente desde `es_torneo_nocturno`
  - Si `es_torneo_nocturno = 1` → `tipo_campeonato = 'zonal'`
  - Si `es_torneo_nocturno = 0` → `tipo_campeonato = 'largo'`

### 2. Tabla `eventos_partido`
- **Nuevo campo**: `es_torneo_zonal` TINYINT(1)
  - Indica si el evento pertenece a un torneo zonal (1) o largo (0)
  - Se calcula automáticamente mediante un trigger

### 3. Trigger `trg_eventos_partido_campeonato`
- Modificado para determinar automáticamente `es_torneo_zonal` al insertar eventos
- Verifica el `tipo_campeonato` del campeonato relacionado
- También considera el `tipo_torneo` del partido ('zona', 'eliminatoria' = zonal)

### 4. Vista `v_estadisticas_jugador_por_tipo`
- Nueva vista que facilita consultas de estadísticas separadas por tipo de torneo
- Incluye: partidos, goles, amarillas (separados) y rojas (compartidas)

## Archivos Modificados

### 1. `migracion_normalizacion_torneos_completa.sql`
**Descripción**: Script de migración completo para normalizar la base de datos.

**Cambios principales**:
- Agrega campo `tipo_campeonato` a `campeonatos`
- Agrega campo `es_torneo_zonal` a `eventos_partido`
- Modifica el trigger para calcular automáticamente el tipo de torneo
- Crea índices para mejorar el rendimiento
- Crea vista para facilitar consultas

**Ejecución**:
```sql
-- Ejecutar en MySQL/MariaDB
SOURCE migracion_normalizacion_torneos_completa.sql;
```

### 2. `public/jugador.php`
**Descripción**: Página de perfil del jugador con historial y estadísticas.

**Cambios principales**:
- Calcula estadísticas separadas por tipo de torneo (`$dest_largos` y `$dest_zonales`)
- Modifica consultas SQL para separar partidos y eventos por tipo
- Muestra dos secciones de estadísticas: "Campeonatos Largos" y "Torneos por Zonas"
- Las tarjetas rojas se muestran en ambas secciones (compartidas)

**Ejemplo de consulta modificada**:
```php
// Partidos en torneos largos
SELECT COUNT(DISTINCT jp.partido_id)
FROM jugadores_partido jp
JOIN partidos p ON jp.partido_id = p.id
LEFT JOIN fechas f ON p.fecha_id = f.id
LEFT JOIN categorias cat ON f.categoria_id = cat.id
LEFT JOIN campeonatos camp ON cat.campeonato_id = camp.id
WHERE jp.jugador_id = ? AND p.estado = 'finalizado'
  AND (camp.tipo_campeonato = 'largo' OR camp.tipo_campeonato IS NULL)
  AND p.tipo_torneo = 'normal'
```

### 3. `public/historial_jugador.php`
**Descripción**: Página de historial completo del jugador.

**Cambios principales**:
- Obtiene `tipo_campeonato` y `es_torneo_nocturno` en la consulta
- Calcula totales separados por tipo de torneo
- Muestra dos tarjetas: "Campeonatos Largos" y "Torneos por Zonas"
- El historial detallado se mantiene igual, pero ahora diferencia visualmente los tipos

## Ejemplos de Uso

### Ejemplo 1: Guardar un evento en un torneo largo

Cuando se guarda un gol en un partido del "Apertura 2025":

```php
// El trigger automáticamente:
// 1. Detecta que el partido es tipo 'normal'
// 2. Obtiene el campeonato desde fecha -> categoria -> campeonato
// 3. Verifica que tipo_campeonato = 'largo'
// 4. Establece es_torneo_zonal = 0
// 5. Guarda el evento con estos valores

INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto)
VALUES (123, 45, 'gol', 25);
-- Resultado: es_torneo_zonal = 0, campeonato_id = 1 (Apertura 2025)
```

### Ejemplo 2: Guardar un evento en un torneo zonal

Cuando se guarda una tarjeta amarilla en un partido del "Torneo Nocturno 2025":

```php
// El trigger automáticamente:
// 1. Detecta que el partido es tipo 'zona' o 'eliminatoria'
// 2. Obtiene el campeonato desde zona -> formato -> campeonato
// 3. Verifica que tipo_campeonato = 'zonal'
// 4. Establece es_torneo_zonal = 1
// 5. Guarda el evento con estos valores

INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto)
VALUES (456, 45, 'amarilla', 15);
-- Resultado: es_torneo_zonal = 1, campeonato_id = 2 (Torneo Nocturno 2025)
```

### Ejemplo 3: Mostrar historial del jugador

En `public/jugador.php`, el historial muestra:

**Campeonatos Largos:**
- Partidos: 40 (suma de Apertura 2025: 20 + Clausura 2025: 20)
- Goles: 15
- Amarillas: 8
- Rojas: 2

**Torneos por Zonas:**
- Partidos: 7
- Goles: 3
- Amarillas: 1
- Rojas: 2 (compartida con largos)

**Total NO mostrado como suma**: Las estadísticas NO se suman (no muestra 47 partidos totales), sino que se muestran separadas.

### Ejemplo 4: Consulta de estadísticas separadas

```sql
-- Obtener estadísticas de un jugador en torneos largos
SELECT 
    COUNT(DISTINCT CASE WHEN ep.es_torneo_zonal = 0 THEN jp.partido_id END) as partidos_largos,
    SUM(CASE WHEN ep.es_torneo_zonal = 0 AND ep.tipo_evento = 'gol' THEN 1 ELSE 0 END) as goles_largos,
    SUM(CASE WHEN ep.es_torneo_zonal = 0 AND ep.tipo_evento = 'amarilla' THEN 1 ELSE 0 END) as amarillas_largos
FROM jugadores j
LEFT JOIN jugadores_partido jp ON j.id = jp.jugador_id
LEFT JOIN partidos p ON jp.partido_id = p.id AND p.estado = 'finalizado'
LEFT JOIN eventos_partido ep ON j.id = ep.jugador_id AND ep.partido_id = p.id
WHERE j.id = 45
GROUP BY j.id;

-- Obtener estadísticas de un jugador en torneos zonales
SELECT 
    COUNT(DISTINCT CASE WHEN ep.es_torneo_zonal = 1 THEN jp.partido_id END) as partidos_zonales,
    SUM(CASE WHEN ep.es_torneo_zonal = 1 AND ep.tipo_evento = 'gol' THEN 1 ELSE 0 END) as goles_zonales,
    SUM(CASE WHEN ep.es_torneo_zonal = 1 AND ep.tipo_evento = 'amarilla' THEN 1 ELSE 0 END) as amarillas_zonales
FROM jugadores j
LEFT JOIN jugadores_partido jp ON j.id = jp.jugador_id
LEFT JOIN partidos p ON jp.partido_id = p.id AND p.estado = 'finalizado'
LEFT JOIN eventos_partido ep ON j.id = ep.jugador_id AND ep.partido_id = p.id
WHERE j.id = 45
GROUP BY j.id;

-- Rojas compartidas (se cuentan en ambos tipos)
SELECT 
    SUM(CASE WHEN ep.tipo_evento = 'roja' THEN 1 ELSE 0 END) as rojas_totales
FROM jugadores j
LEFT JOIN eventos_partido ep ON j.id = ep.jugador_id
WHERE j.id = 45;
```

## Comportamiento de las Tarjetas Rojas

Las tarjetas rojas se comparten entre ambos tipos de torneo. Esto significa:

1. Si un jugador recibe una roja en un torneo largo, esa roja también se cuenta en los torneos zonales activos.
2. Si un jugador recibe una roja en un torneo zonal, esa roja también se cuenta en los torneos largos activos.
3. En el historial, las rojas se muestran en ambas secciones con el mismo valor (máximo entre ambos tipos).

**Ejemplo**:
- Jugador recibe roja en "Apertura 2025" (torneo largo)
- Esa roja se refleja en:
  - Estadísticas de "Campeonatos Largos": 1 roja
  - Estadísticas de "Torneos por Zonas": 1 roja (si hay torneos zonales activos)

## Archivos que Requieren Revisión Adicional

Los siguientes archivos pueden necesitar modificaciones similares si calculan estadísticas:

1. `public/goleadores.php` - Tabla de goleadores
2. `public/estadisticas_historicas.php` - Estadísticas históricas
3. `admin/eventos.php` - Gestión de eventos
4. Cualquier otro archivo que calcule totales de partidos, goles o tarjetas

## Notas Importantes

1. **Compatibilidad hacia atrás**: Los campos existentes se mantienen para compatibilidad, pero las nuevas consultas usan los nuevos campos.

2. **Migración de datos existentes**: El script de migración actualiza automáticamente los eventos existentes basándose en el `tipo_campeonato` del campeonato.

3. **Rendimiento**: Se agregaron índices en `es_torneo_zonal` y `tipo_campeonato` para mejorar el rendimiento de las consultas.

4. **Validación**: Asegúrate de que todos los campeonatos tengan correctamente configurado `es_torneo_nocturno` o `tipo_campeonato` antes de ejecutar la migración.

## Próximos Pasos

1. Ejecutar el script de migración en la base de datos
2. Verificar que los eventos existentes se actualizaron correctamente
3. Probar la visualización del historial de jugadores
4. Revisar y actualizar otros archivos que calculan estadísticas
5. Documentar cualquier comportamiento específico adicional

