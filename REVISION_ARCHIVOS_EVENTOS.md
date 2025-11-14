# Revisión de Archivos de Eventos y Estadísticas

## Archivos que Insertan Eventos

### ✅ `admin/control_fechas.php`
**Estado**: ✅ Correcto
- Líneas 135, 150, 169: Inserciones de eventos
- El trigger `trg_eventos_partido_campeonato` determina automáticamente el tipo de torneo
- **Comentarios agregados** para documentar el funcionamiento del trigger

### ✅ `planillero/partido_live.php`
**Estado**: ✅ Correcto
- Línea 198: Inserción de eventos en tiempo real
- Línea 387: Inserción de roja por doble amarilla
- El trigger determina automáticamente el tipo de torneo
- **Comentarios agregados** para documentar el funcionamiento del trigger

### ✅ `planillero/guardar_eventos.php`
**Estado**: ✅ Correcto
- Línea 26: Inserción de eventos
- El trigger funciona automáticamente
- No requiere cambios

### ✅ `admin/ajax/guardar_eventos.php`
**Estado**: ✅ Correcto
- Línea 30: Inserción de eventos
- El trigger funciona automáticamente
- No requiere cambios

### ✅ `admin/eventos_vivo.php`
**Estado**: ✅ Correcto
- Línea 75: Inserción de eventos
- El trigger funciona automáticamente
- No requiere cambios

## Archivos GET que Consultan Eventos

### ✅ `planillero/get_eventos.php`
**Estado**: ✅ Correcto
- Solo consulta eventos de un partido específico
- No calcula estadísticas totales
- No requiere cambios

### ✅ `admin/ajax/get_eventos.php`
**Estado**: ✅ Correcto
- Solo consulta eventos de un partido específico
- No calcula estadísticas totales
- No requiere cambios

### ✅ `admin/ajax/get_eventos_partido.php`
**Estado**: ✅ Correcto
- Solo consulta eventos de un partido específico
- No calcula estadísticas totales
- No requiere cambios

### ✅ `admin/ajax/get_eventos_zonas.php`
**Estado**: ✅ Correcto
- Solo consulta eventos de un partido específico de zonas
- Filtra por `tipo_partido = 'zona'`
- No requiere cambios

### ✅ `admin/ajax/get_jugadores_sanciones.php`
**Estado**: ✅ Correcto
- Calcula amarillas acumuladas para sanciones
- Las sanciones se aplican globalmente (no se separan por tipo de torneo)
- No requiere cambios

### ✅ `admin/get_jugadores_sanciones.php`
**Estado**: ✅ Correcto
- Solo cuenta sanciones activas
- No calcula estadísticas de eventos
- No requiere cambios

## Archivos que Calculan Estadísticas

### ⚠️ `public/goleadores.php`
**Estado**: ⚠️ Requiere Revisión Futura (Opcional)

**Consultas que están bien** (filtradas por campeonato):
- Líneas 104-136: Estadísticas generales filtradas por campeonato ✅
- Líneas 464-468: Pókers filtrados por campeonato ✅

**Consulta que podría necesitar separación** (línea 496):
```php
// Goleadores históricos de la liga (sin filtro de campeonato)
SELECT j.id, j.apellido_nombre, j.foto, COUNT(ev.id) AS goles_totales
FROM eventos_partido ev
JOIN jugadores j ON ev.jugador_id = j.id
JOIN partidos p ON ev.partido_id = p.id
WHERE ev.tipo_evento = 'gol'
GROUP BY j.id, j.apellido_nombre, j.foto
ORDER BY goles_totales DESC
```

**Nota**: Esta consulta muestra goleadores históricos de TODA la liga. Si se desea separar por tipo de torneo, se podría modificar, pero como es un ranking histórico general, puede mantenerse así.

### ⚠️ `public/estadisticas_historicas.php`
**Estado**: ⚠️ Requiere Revisión Futura (Opcional)
- Calcula estadísticas históricas generales
- Si se desea separar por tipo de torneo, requeriría modificaciones
- Por ahora está bien porque muestra estadísticas generales de la liga

## Resumen

### ✅ Archivos Correctos (No Requieren Cambios)
1. `admin/control_fechas.php` - ✅ Con comentarios agregados
2. `planillero/partido_live.php` - ✅ Con comentarios agregados
3. Todos los archivos `get_*.php` que solo consultan eventos de partidos específicos
4. Archivos que calculan sanciones (se aplican globalmente)

### ⚠️ Archivos para Revisión Futura (Opcional)
1. `public/goleadores.php` - Línea 496 (ranking histórico general)
2. `public/estadisticas_historicas.php` - Estadísticas históricas generales

**Nota**: Los archivos marcados como "Revisión Futura" muestran estadísticas históricas generales de toda la liga. Si se desea separar por tipo de torneo, se pueden modificar, pero no es crítico porque son rankings generales.

## Conclusión

✅ **Todos los archivos que insertan eventos están correctos** - El trigger funciona automáticamente.

✅ **Todos los archivos GET que consultan eventos de partidos específicos están correctos** - No calculan estadísticas totales.

⚠️ **Los archivos que calculan estadísticas históricas generales** pueden mantenerse así o modificarse en el futuro si se desea separar por tipo de torneo.

