-- =====================================================
-- ACTUALIZACIÓN DE DATOS HISTÓRICOS
-- =====================================================
-- Este script actualiza los registros existentes en
-- jugadores_equipos_historial con las estadísticas
-- separadas por tipo de torneo.
-- =====================================================

-- Actualizar jugadores_equipos_historial con estadísticas separadas
UPDATE `jugadores_equipos_historial` jeh
SET 
    -- Partidos separados por tipo
    `partidos_largos` = (
        SELECT COUNT(DISTINCT jp.partido_id)
        FROM jugadores_partido jp
        JOIN partidos p ON jp.partido_id = p.id
        LEFT JOIN fechas f ON p.fecha_id = f.id
        LEFT JOIN categorias c ON f.categoria_id = c.id
        LEFT JOIN campeonatos camp ON c.campeonato_id = camp.id
        WHERE jp.jugador_id IN (SELECT id FROM jugadores WHERE dni = jeh.jugador_dni)
          AND p.estado = 'finalizado'
          AND (
              (p.tipo_torneo = 'normal' AND (camp.tipo_campeonato = 'largo' OR camp.tipo_campeonato IS NULL))
              OR
              (p.tipo_torneo IN ('zona', 'eliminatoria') AND camp.tipo_campeonato = 'largo')
          )
          AND camp.id = jeh.campeonato_id
    ),
    `partidos_zonales` = (
        SELECT COUNT(DISTINCT jp.partido_id)
        FROM jugadores_partido jp
        JOIN partidos p ON jp.partido_id = p.id
        LEFT JOIN fechas f ON p.fecha_id = f.id
        LEFT JOIN categorias c ON f.categoria_id = c.id
        LEFT JOIN campeonatos camp ON c.campeonato_id = camp.id
        LEFT JOIN zonas z ON p.zona_id = z.id
        LEFT JOIN campeonatos_formato cf ON z.formato_id = cf.id
        WHERE jp.jugador_id IN (SELECT id FROM jugadores WHERE dni = jeh.jugador_dni)
          AND p.estado = 'finalizado'
          AND (
              (p.tipo_torneo IN ('zona', 'eliminatoria') AND (camp.tipo_campeonato = 'zonal' OR cf.campeonato_id = jeh.campeonato_id))
              OR
              (camp.tipo_campeonato = 'zonal' AND camp.id = jeh.campeonato_id)
          )
          AND (
              camp.id = jeh.campeonato_id 
              OR cf.campeonato_id = jeh.campeonato_id
          )
    ),
    -- Goles separados por tipo
    `goles_largos` = (
        SELECT COUNT(*)
        FROM eventos_partido ep
        JOIN partidos p ON ep.partido_id = p.id
        WHERE ep.jugador_id IN (SELECT id FROM jugadores WHERE dni = jeh.jugador_dni)
          AND ep.tipo_evento = 'gol'
          AND ep.es_torneo_zonal = 0
          AND ep.campeonato_id = jeh.campeonato_id
    ),
    `goles_zonales` = (
        SELECT COUNT(*)
        FROM eventos_partido ep
        JOIN partidos p ON ep.partido_id = p.id
        WHERE ep.jugador_id IN (SELECT id FROM jugadores WHERE dni = jeh.jugador_dni)
          AND ep.tipo_evento = 'gol'
          AND ep.es_torneo_zonal = 1
          AND ep.campeonato_id = jeh.campeonato_id
    ),
    -- Amarillas separadas por tipo
    `amarillas_largos` = (
        SELECT COUNT(*)
        FROM eventos_partido ep
        WHERE ep.jugador_id IN (SELECT id FROM jugadores WHERE dni = jeh.jugador_dni)
          AND ep.tipo_evento = 'amarilla'
          AND ep.es_torneo_zonal = 0
          AND ep.campeonato_id = jeh.campeonato_id
    ),
    `amarillas_zonales` = (
        SELECT COUNT(*)
        FROM eventos_partido ep
        WHERE ep.jugador_id IN (SELECT id FROM jugadores WHERE dni = jeh.jugador_dni)
          AND ep.tipo_evento = 'amarilla'
          AND ep.es_torneo_zonal = 1
          AND ep.campeonato_id = jeh.campeonato_id
    ),
    -- Rojas (compartidas, pero registradas en ambos)
    `rojas_largos` = (
        SELECT COUNT(*)
        FROM eventos_partido ep
        WHERE ep.jugador_id IN (SELECT id FROM jugadores WHERE dni = jeh.jugador_dni)
          AND ep.tipo_evento = 'roja'
          AND ep.campeonato_id = jeh.campeonato_id
    ),
    `rojas_zonales` = (
        SELECT COUNT(*)
        FROM eventos_partido ep
        WHERE ep.jugador_id IN (SELECT id FROM jugadores WHERE dni = jeh.jugador_dni)
          AND ep.tipo_evento = 'roja'
          AND ep.campeonato_id = jeh.campeonato_id
    )
WHERE jeh.campeonato_id IS NOT NULL;

-- Nota: Las tarjetas rojas se duplican en ambos tipos porque son compartidas
-- según los requisitos del usuario.

