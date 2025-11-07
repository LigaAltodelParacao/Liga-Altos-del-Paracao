# Sistema de Torneos por Zonas - Documentación Completa

## 📋 Descripción General

Sistema completo para la gestión de torneos con fase de grupos (zonas) y fases eliminatorias. El sistema permite crear, gestionar y visualizar torneos con todas las funcionalidades necesarias.

## 🗄️ Estructura de Base de Datos

### Tablas Principales

1. **campeonatos_formato**: Configuración del torneo por zonas
   - Almacena configuración de zonas, clasificación y fases eliminatorias
   
2. **zonas**: Zonas del torneo
   - Cada zona tiene un nombre y orden
   
3. **equipos_zonas**: Relación entre equipos y zonas
   - Almacena estadísticas de cada equipo en su zona
   
4. **partidos_zona**: Partidos de la fase de grupos
   - Todos contra todos dentro de cada zona
   
5. **fases_eliminatorias**: Fases eliminatorias (octavos, cuartos, semis, final)
   - Controla qué fases están activas
   
6. **partidos_eliminatorios**: Partidos de las fases eliminatorias
   - Permite penales y rastreo de origen de equipos

### Vista

- **v_tabla_posiciones_zona**: Vista calculada para tabla de posiciones

## 🚀 Instalación

1. **Ejecutar el script SQL**:
   ```sql
   -- Ejecutar database_torneos_zonas.sql
   source database_torneos_zonas.sql;
   ```

2. **Verificar permisos**:
   - Asegurarse de que los archivos PHP tengan permisos de lectura
   - Verificar que la carpeta `uploads` tenga permisos de escritura

## 📁 Archivos del Sistema

### Backend (Admin)

- `admin/crear_torneo_zonas.php`: Creación de torneos
- `admin/funciones_torneos_zonas.php`: Funciones auxiliares
- `admin/control_partidos_zonas.php`: Gestión de partidos de grupos
- `admin/control_eliminatorias.php`: Gestión de partidos eliminatorios
- `admin/generar_eliminatorias.php`: Generación automática de eliminatorias
- `admin/torneos_zonas.php`: Listado de torneos
- `admin/ajax/get_partido_zona.php`: AJAX para obtener partidos de zona
- `admin/ajax/get_partido_eliminatorio.php`: AJAX para obtener partidos eliminatorios

### Frontend (Público)

- `public/tablas_zonas.php`: Tablas de posiciones por zona
- `public/bracket_zonas.php`: Bracket de eliminación directa

## 🎯 Funcionalidades

### 1. Creación de Torneo

**Características:**
- Selección de campeonato y categoría
- Configuración de cantidad de zonas
- Distribución automática de equipos (homogénea)
- Configuración de clasificación (cuántos equipos clasifican por posición)
- Selección de fases eliminatorias

**Pasos:**
1. Ir a `admin/crear_torneo_zonas.php`
2. Seleccionar campeonato y categoría
3. Elegir cantidad de zonas
4. Configurar clasificación
5. Seleccionar fases eliminatorias
6. Seleccionar equipos participantes
7. Crear torneo

### 2. Distribución Automática

El sistema distribuye automáticamente los equipos de forma homogénea:
- Si hay 24 equipos en 4 zonas → 6 equipos por zona
- Si hay 17 equipos en 4 zonas → 3 zonas de 4 equipos + 1 zona de 5 equipos

### 3. Fixture de Grupos

- Generación automática de fixture (todos contra todos)
- Algoritmo Round Robin
- Manejo de equipos impares

### 4. Tabla de Posiciones

**Criterios de Desempate (en orden):**
1. Mayor diferencia de goles (GF - GC)
2. Mayor cantidad de goles a favor
3. Resultado entre equipos empatados (enfrentamiento directo)
4. Menor cantidad de tarjetas rojas
5. Menor cantidad de tarjetas amarillas
6. Sorteo

### 5. Clasificación

- El primero de cada zona SIEMPRE clasifica
- Configurable: cuántos segundos, terceros y cuartos clasifican
- Cálculo automático de clasificados

### 6. Generación de Eliminatorias

- Automática cuando todos los partidos de grupos están finalizados
- Emparejamiento: 1° vs Último, 2° vs Penúltimo, etc.
- Avance automático a siguiente fase cuando se completan todos los partidos

### 7. Fases Eliminatorias

- Octavos de Final (opcional, 16 equipos)
- Cuartos de Final (opcional, 8 equipos)
- Semifinales (siempre, 4 equipos)
- Tercer Puesto (opcional)
- Final (siempre)

### 8. Carga de Resultados

- Resultados de fase de grupos
- Resultados eliminatorios con soporte para penales
- Actualización automática de estadísticas
- Actualización automática de tabla de posiciones

## 📊 Uso del Sistema

### Para Administradores

1. **Crear Torneo**:
   - `admin/crear_torneo_zonas.php`
   - Seguir el asistente paso a paso

2. **Cargar Resultados de Grupos**:
   - `admin/control_partidos_zonas.php?formato_id=X`
   - Cargar resultados por jornada
   - Ver tablas de posiciones en tiempo real

3. **Generar Eliminatorias** (automático o manual):
   - Automático: cuando todos los partidos están finalizados
   - Manual: `admin/generar_eliminatorias.php?formato_id=X`

4. **Cargar Resultados Eliminatorios**:
   - `admin/control_eliminatorias.php?formato_id=X`
   - Avance automático a siguiente fase

### Para Público

1. **Ver Tablas de Posiciones**:
   - `public/tablas_zonas.php`
   - Seleccionar categoría

2. **Ver Bracket**:
   - `public/bracket_zonas.php?formato_id=X`
   - Visualización completa del árbol de eliminación

## 🔧 Funciones Principales

### `calcularDistribucionZonas($total_equipos, $num_zonas)`
Calcula distribución homogénea de equipos en zonas.

### `generarFixtureZona($zona_id, $db)`
Genera fixture todos contra todos para una zona.

### `actualizarEstadisticasZona($zona_id, $equipo_id, $db)`
Actualiza estadísticas de un equipo después de un partido.

### `obtenerTablaPosicionesZona($zona_id, $db)`
Obtiene tabla de posiciones con desempates aplicados.

### `aplicarDesempates($equipos, $zona_id, $db)`
Aplica criterios de desempate a equipos con igual puntaje.

### `obtenerEquiposClasificados($formato_id, $db)`
Obtiene equipos clasificados según configuración.

### `generarFixtureEliminatorias($formato_id, $db)`
Genera partidos de la primera fase eliminatoria.

### `avanzarSiguienteFase($fase_id, $db)`
Avanza equipos ganadores a la siguiente fase.

## ⚙️ Configuración

### Clasificación

En `campeonatos_formato`:
- `primeros_clasifican`: Cantidad de primeros que clasifican (normalmente igual a cantidad de zonas)
- `segundos_clasifican`: Cantidad de segundos que clasifican
- `terceros_clasifican`: Cantidad de terceros que clasifican
- `cuartos_clasifican`: Cantidad de cuartos que clasifican

### Fases Eliminatorias

- `tiene_octavos`: 1 si hay octavos, 0 si no
- `tiene_cuartos`: 1 si hay cuartos, 0 si no
- `tiene_semifinal`: 1 si hay semifinales (recomendado siempre)
- `tiene_tercer_puesto`: 1 si hay partido por tercer puesto

## 🎨 Interfaz

- **Bootstrap 5.1.3**: Framework CSS
- **Font Awesome 6.0**: Iconos
- **Responsive**: Compatible con móviles y tablets

## 📝 Notas Importantes

1. **Eventos de Partidos**: Los eventos (goles, tarjetas) se registran en `eventos_partido` usando `partido_id` que puede referirse tanto a `partidos` como a `partidos_zona`.

2. **Actualización de Estadísticas**: Se actualizan automáticamente al finalizar un partido.

3. **Generación Automática**: Las eliminatorias se generan automáticamente cuando todos los partidos de grupos están finalizados.

4. **Avance de Fases**: El avance a la siguiente fase es automático cuando todos los partidos de la fase actual están finalizados.

5. **Penales**: Solo se solicitan si el partido empató.

## 🐛 Solución de Problemas

### Error: "Aún hay partidos pendientes"
- Verificar que todos los partidos de la fase estén marcados como "finalizado"
- Revisar estado de partidos en `partidos_zona` o `partidos_eliminatorios`

### Error: "No se encontró formato del campeonato"
- Verificar que el `formato_id` sea correcto
- Verificar que `campeonatos_formato.activo = 1`

### Tabla de posiciones no se actualiza
- Verificar que los partidos estén marcados como "finalizado"
- Ejecutar manualmente `actualizarEstadisticasZona()` si es necesario

## 📞 Soporte

Para dudas o problemas, revisar:
1. Logs de errores de PHP
2. Logs de la base de datos
3. Verificar permisos de archivos
4. Verificar configuración de base de datos

## 🔄 Versión

- **Versión**: 1.0
- **Última actualización**: 2025
- **Compatibilidad**: PHP 7.2+, MySQL/MariaDB 5.7+

