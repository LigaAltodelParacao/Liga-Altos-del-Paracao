# Sistema de Campeonatos de Fútbol

Sistema completo para la gestión de campeonatos de fútbol con funcionalidades avanzadas para administradores, planilleros y público en general.

## 🚀 Características Principales

### Para Administradores
- ✅ Gestión completa de campeonatos y categorías
- ✅ Registro de equipos con logos
- ✅ Gestión de jugadores (individual y por Excel)
- ✅ Programación automática de partidos
- ✅ Eventos en tiempo real con cronómetro
- ✅ Sistema de sanciones automáticas
- ✅ Generación de planillas PDF
- ✅ Gestión de usuarios y permisos

### Para Planilleros
- ✅ Acceso mediante códigos únicos
- ✅ Registro de eventos en vivo
- ✅ Planillas descargables
- ✅ Interface móvil optimizada

### Para el Público
- ✅ Resultados en tiempo real
- ✅ Tablas de posiciones automáticas
- ✅ Tabla de goleadores
- ✅ Estadísticas de fair play
- ✅ Fixture completo
- ✅ Exportación a PDF y Excel

## 📋 Requisitos del Sistema

### Servidor Web
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Apache/Nginx
- Extensiones PHP: PDO, GD, ZipArchive

### Para Hostinger
- Plan Premium o superior
- Base de datos MySQL incluida
- PHP 7.4+ disponible

## 🔧 Instalación

### Paso 1: Subir Archivos
1. Descarga todos los archivos del sistema
2. Sube los archivos a tu hosting via FTP o panel de control
3. Asegúrate de que todos los archivos estén en la carpeta raíz de tu dominio

### Paso 2: Crear Base de Datos
1. Accede al panel de control de Hostinger
2. Ve a "Bases de Datos MySQL"
3. Crea una nueva base de datos (ej: `sistema_futbol`)
4. Anota el nombre de usuario y contraseña

### Paso 3: Importar Base de Datos
1. Accede a phpMyAdmin desde tu panel de control
2. Selecciona la base de datos creada
3. Ve a la pestaña "Importar"
4. Sube el archivo `sistema_futbol.sql`
5. Haz clic en "Continuar"

### Paso 4: Configurar Conexión
1. Edita el archivo `config.php`
2. Modifica las siguientes líneas:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tu_nombre_bd');     // Nombre de tu base de datos
define('DB_USER', 'tu_usuario');       // Usuario de la BD
define('DB_PASS', 'tu_contraseña');    // Contraseña de la BD
define('SITE_URL', 'https://tudominio.com'); // Tu dominio
```

### Paso 5: Configurar Permisos
Asegúrate de que las siguientes carpetas tengan permisos de escritura (755):
- `uploads/`
- `uploads/equipos/`
- `uploads/jugadores/`
- `uploads/general/`

### Paso 6: Crear Carpetas de Upload
Si no existen, crea estas carpetas:
```
uploads/
├── equipos/
├── jugadores/
└── general/
```

## 👤 Acceso Inicial

### Usuario Administrador por Defecto
- **Usuario:** admin
- **Contraseña:** password

**⚠️ IMPORTANTE:** Cambia esta contraseña inmediatamente después de instalar el sistema.

## 🔒 Configuración de Seguridad

### Cambiar Contraseña del Admin
1. Inicia sesión con las credenciales por defecto
2. Ve a "Usuarios" en el panel de administración
3. Edita el usuario "admin"
4. Cambia la contraseña por una segura

### Opcional: Cambiar Usuario Admin
Puedes crear un nuevo usuario administrador y eliminar el por defecto:
```sql
INSERT INTO usuarios (username, password, email, nombre, tipo) 
VALUES ('tu_usuario', '$2y$10$hash_de_tu_contraseña', 'tu@email.com', 'Tu Nombre', 'superadmin');
```

## 📱 Características Especiales

### Eventos en Tiempo Real
- Cronómetro automático de 30 minutos por tiempo
- Registro de goles, tarjetas y cambios
- Actualización automática de marcadores
- Sanciones automáticas por acumulación de tarjetas

### Sistema de Planilleros
- Códigos únicos de acceso por partido
- Interface simplificada para registro rápido
- Planillas PDF con firmas
- Respaldo manual requerido

### Importación de Jugadores
El sistema acepta archivos Excel con el siguiente formato:
- **Columna A:** Apellido y Nombre
- **Columna B:** DNI
- **Columna C:** Fecha de Nacimiento (DD/MM/YYYY)

### Generación Automática de Fixture
- Partidos todos los sábados desde fecha de inicio
- Asignación automática de canchas
- Modificación manual disponible
- Sistema round-robin

## 🎨 Personalización

### Colores y Estilos
Edita el archivo `assets/css/style.css` para personalizar:
- Colores del tema
- Logos y branding
- Fuentes y tipografía

### Configuraciones del Sistema
En `config.php` puedes modificar:
- Tamaño máximo de archivos
- Rutas de upload
- Configuraciones de sesión

## 📊 Funcionalidades Avanzadas

### Estadísticas Automáticas
- Tabla de posiciones en tiempo real
- Goleadores por categoría
- Fair play (tarjetas por equipo)
- Estadísticas de sanciones

### Exportaciones
- PDF: Tablas, planillas, fixture
- Excel: Jugadores, estadísticas, tablas
- Formato profesional listo para imprimir

### Sistema de Roles
1. **Superadmin:** Acceso total + gestión de usuarios
2. **Admin:** Gestión completa excepto usuarios
3. **Planillero:** Solo acceso a eventos de sus partidos asignados

## 🚨 Solución de Problemas

### Error de Conexión a BD
- Verifica credenciales en `config.php`
- Confirma que la BD existe
- Revisa permisos del usuario de BD

### Problemas con Uploads
- Verifica permisos de carpeta `uploads/` (755)
- Confirma que existe la carpeta
- Revisa límites de PHP (upload_max_filesize)

### Eventos No Se Guardan
- Verifica que el partido esté en estado "en_curso"
- Confirma que el jugador no esté sancionado
- Revisa permisos del planillero

## 📞 Soporte

### Logs de Error
Los errores se registran en:
- Logs de PHP del servidor
- Panel de control de Hostinger

### Backup Recomendado
Realiza backup regulares de:
- Base de datos completa
- Carpeta `uploads/`
- Archivo `config.php`

## 🔄 Actualizaciones

Para futuras actualizaciones:
1. Realiza backup completo
2. Sube nuevos archivos
3. Ejecuta scripts SQL adicionales si es necesario
4. Verifica funcionalidad

## 📜 Licencia

Sistema desarrollado para uso libre. Puedes modificar y adaptar según tus necesidades.

---

**¡Listo para comenzar!** 🚀

Una vez instalado, accede a tu dominio y comienza a configurar tu primer campeonato de fútbol.