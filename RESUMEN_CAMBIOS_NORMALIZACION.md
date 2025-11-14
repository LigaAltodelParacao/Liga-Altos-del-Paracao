# Resumen de Cambios: Normalización de Torneos

## Objetivo
Separar completamente las estadísticas entre campeonatos largos (Apertura, Clausura) y torneos por zonas (Torneo Nocturno, etc.), excepto las tarjetas rojas que se comparten.

## Archivos Creados

### 1. `migracion_normalizacion_torneos_completa.sql`
Script SQL completo para normalizar la base de datos:
- Agrega campo `tipo_campeonato` a tabla `campeonatos`
- Agrega campo `es_torneo_zonal` a tabla `eventos_partido`
- Modifica trigger para calcular automáticamente el tipo
- Crea índices para optimización
- Crea vista para facilitar consultas

### 2. `DOCUMENTACION_NORMALIZACION_TORNEOS.md`
Documentación completa con:
- Explicación de cambios
- Ejemplos de uso
- Consultas SQL de ejemplo
- Comportamiento de tarjetas rojas

## Archivos Modificados

### 1. `public/jugador.php`
**Cambios**:
- Líneas 135-171: Agregado cálculo de estadísticas separadas (`$dest_largos` y `$dest_zonales`)
- Líneas 180-310: Modificadas consultas SQL para separar por tipo de torneo
- Líneas 395-424: Modificada visualización para mostrar dos secciones separadas

**Resultado**: Muestra estadísticas de "Campeonatos Largos" y "Torneos por Zonas" por separado.

### 2. `public/historial_jugador.php`
**Cambios**:
- Líneas 27-91: Modificada consulta para obtener `tipo_campeonato` y cálculo de totales separados
- Líneas 306-385: Modificada visualización para mostrar dos tarjetas separadas

**Resultado**: Muestra totales separados por tipo de torneo antes del historial detallado.

## Estructura de Base de Datos

### Nuevos Campos

**Tabla `campeonatos`**:
```sql
tipo_campeonato ENUM('largo', 'zonal') DEFAULT 'largo'
```

**Tabla `eventos_partido`**:
```sql
es_torneo_zonal TINYINT(1) DEFAULT 0
```

### Trigger Modificado

El trigger `trg_eventos_partido_campeonato` ahora:
1. Calcula `campeonato_id` desde el partido
2. Determina `tipo_campeonato` del campeonato
3. Establece `es_torneo_zonal` automáticamente
4. Guarda `tipo_partido` del partido

## Funcionalidad

### Separación de Estadísticas

✅ **Partidos**: Separados completamente
- Torneos largos: Solo partidos de campeonatos con `tipo_campeonato = 'largo'`
- Torneos zonales: Solo partidos de campeonatos con `tipo_campeonato = 'zonal'`

✅ **Goles**: Separados completamente
- Se cuentan según `es_torneo_zonal` del evento

✅ **Amarillas**: Separadas completamente
- Se cuentan según `es_torneo_zonal` del evento

✅ **Rojas**: Compartidas entre ambos tipos
- Se muestran en ambas secciones con el mismo valor
- Una roja en un tipo afecta a ambos tipos

### Visualización

El historial del jugador ahora muestra:

1. **Sección "Campeonatos Largos"**:
   - Partidos, goles, amarillas, rojas de torneos largos

2. **Sección "Torneos por Zonas"**:
   - Partidos, goles, amarillas, rojas de torneos zonales

3. **Timeline detallado**:
   - Mantiene el mismo diseño
   - Separa visualmente torneos nocturnos de campeonatos largos

## Ejemplo de Resultado

**Antes**:
- Total: 47 partidos, 18 goles, 9 amarillas, 2 rojas

**Ahora**:
- **Campeonatos Largos**: 40 partidos, 15 goles, 8 amarillas, 2 rojas
- **Torneos por Zonas**: 7 partidos, 3 goles, 1 amarilla, 2 rojas

## Próximos Pasos Recomendados

1. ✅ Ejecutar script de migración SQL
2. ⚠️ Revisar `public/goleadores.php` para separar estadísticas
3. ⚠️ Revisar otros archivos que calculen estadísticas
4. ✅ Verificar que el trigger funciona correctamente
5. ✅ Probar la visualización con datos reales

## Notas Técnicas

- El trigger se ejecuta automáticamente al insertar eventos, no se requiere modificar código de guardado
- Las consultas usan `LEFT JOIN` para mantener compatibilidad con datos antiguos
- Se usa `COALESCE` para manejar valores NULL en campos nuevos
- Los índices mejoran el rendimiento de las consultas separadas

## Compatibilidad

- ✅ Compatible con datos existentes
- ✅ El trigger actualiza automáticamente eventos nuevos
- ✅ Los eventos antiguos se actualizan con el script de migración
- ✅ No rompe funcionalidad existente

