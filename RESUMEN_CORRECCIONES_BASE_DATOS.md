# Resumen de Correcciones Necesarias en Base de Datos

Basado en el análisis de `sistema_futbol.sql`, estas son las correcciones necesarias:

## ✅ Lo que YA está bien

1. **Tabla `partidos`**: ✅ Tiene todos los campos necesarios:
   - `zona_id`, `fase_eliminatoria_id`, `numero_llave`
   - `origen_local`, `origen_visitante`
   - `goles_local_penales`, `goles_visitante_penales`
   - `tipo_torneo`, `jornada_zona`
   - Índices correctos

2. **Tablas base**: ✅ `zonas`, `fases_eliminatorias`, `campeonatos_formato` existen

3. **Foreign keys**: ✅ Las relaciones principales están correctas

## ❌ Lo que FALTA o necesita corrección

### 1. Tabla `equipos_zonas`
**Faltan columnas:**
- `tarjetas_amarillas` int(11) DEFAULT 0
- `tarjetas_rojas` int(11) DEFAULT 0

**Estado actual:**
```sql
CREATE TABLE `equipos_zonas` (
  ...
  `diferencia_gol` int(11) GENERATED ALWAYS AS (`goles_favor` - `goles_contra`) STORED,
  `posicion` int(11) DEFAULT 0,
  `clasificado` tinyint(1) DEFAULT 0
  -- ❌ Faltan: tarjetas_amarillas, tarjetas_rojas
)
```

### 2. Tabla `campeonatos_formato`
**Faltan columnas:**
- `categoria_id` int(11) DEFAULT NULL
- `primeros_clasifican` int(11) NOT NULL DEFAULT 0
- `segundos_clasifican` int(11) NOT NULL DEFAULT 0
- `terceros_clasifican` int(11) NOT NULL DEFAULT 0
- `cuartos_clasifican` int(11) NOT NULL DEFAULT 0

**Estado actual:**
```sql
CREATE TABLE `campeonatos_formato` (
  ...
  `tipo_clasificacion` varchar(50) DEFAULT NULL,  -- ❌ No se usa
  `equipos_por_zona` int(11) NOT NULL DEFAULT 3,  -- ❌ No se usa
  -- ❌ Falta: categoria_id
  -- ❌ Faltan: primeros_clasifican, segundos_clasifican, etc.
)
```

### 3. Tabla `fechas`
**Faltan columnas:**
- `zona_id` int(11) DEFAULT NULL
- `fase_eliminatoria_id` int(11) DEFAULT NULL
- `tipo_fecha` enum('normal','zona','eliminatoria') DEFAULT 'normal'

**Estado actual:**
```sql
CREATE TABLE `fechas` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `numero_fecha` int(11) NOT NULL,
  `fecha_programada` date NOT NULL,
  `activa` tinyint(1) DEFAULT 1
  -- ❌ Faltan: zona_id, fase_eliminatoria_id, tipo_fecha
)
```

### 4. Tabla `fases_eliminatorias`
**Falta columna:**
- `generada` tinyint(1) DEFAULT 0

**Estado actual:**
```sql
CREATE TABLE `fases_eliminatorias` (
  ...
  `activa` tinyint(1) DEFAULT 1
  -- ❌ Falta: generada
)
```

### 5. Vista `v_tabla_posiciones_zona`
**No existe**, necesita ser creada.

## 📝 Script de Correcciones

He creado `correcciones_base_datos_zonas.sql` que:

1. ✅ Agrega `tarjetas_amarillas` y `tarjetas_rojas` a `equipos_zonas`
2. ✅ Agrega `categoria_id` y columnas de clasificación a `campeonatos_formato`
3. ✅ Agrega `zona_id`, `fase_eliminatoria_id`, `tipo_fecha` a `fechas`
4. ✅ Agrega índices necesarios
5. ✅ Agrega foreign keys
6. ✅ Crea la vista `v_tabla_posiciones_zona`

## 🚀 Cómo Aplicar

```sql
-- Ejecutar el script de correcciones
source correcciones_base_datos_zonas.sql;
```

El script verifica automáticamente qué existe y solo agrega lo que falta. Es seguro ejecutarlo múltiples veces.

## 📌 Notas

- Las tablas `partidos_zona` y `partidos_eliminatorios` existen pero **no se usan** en el sistema integrado (todo va a `partidos`). Está bien dejarlas, no causan problemas.
- La tabla `equipos_zonas` tiene `diferencia_gol` como columna GENERATED, lo cual está bien y es más eficiente.

