<?php
// ===================== CONFIGURACIÓN =====================

// Base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'u959527289_Nuevo');
define('DB_USER', 'u959527289_Nuevo');
define('DB_PASS', '98Nuevo12');

// Configuración general
define('SITE_URL', 'https://salmon-frog-819056.hostingersite.com/');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Configuración de sesión
session_start();

// Zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// ===================== CONEXIÓN A BASE DE DATOS =====================
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            die("Error de conexión a la base de datos.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}

// ===================== FUNCIONES DE AUTENTICACIÓN =====================
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isSuperAdmin() {
    $user = getCurrentUser();
    return $user && $user['tipo'] === 'superadmin';
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function hasPermission($required_type) {
    $user = getCurrentUser();
    if (!$user) return false;
    $permissions = ['planillero'=>1, 'admin'=>2, 'superadmin'=>3];

    // Si se envía una lista de roles permitidos, calcular el umbral mínimo
    if (is_array($required_type) && !empty($required_type)) {
        $minRequired = null;
        foreach ($required_type as $role) {
            if (isset($permissions[$role])) {
                $minRequired = ($minRequired === null)
                    ? $permissions[$role]
                    : min($minRequired, $permissions[$role]);
            }
        }
        if ($minRequired === null) return false; // Ningún rol válido en la lista
        return ($permissions[$user['tipo']] ?? 0) >= $minRequired;
    }

    // Caso simple: un solo rol requerido
    return ($permissions[$user['tipo']] ?? 0) >= ($permissions[$required_type] ?? 0);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

// ===================== FUNCIONES UTILES =====================
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function calculateAge($birthdate) {
    $birth = new DateTime($birthdate);
    $today = new DateTime();
    return $today->diff($birth)->y;
}

function generateCode($length = 6) {
    return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

// ===================== SUBIDA DE ARCHIVOS =====================
function uploadFile($file, $folder = 'general') {
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return false;

    $upload_dir = UPLOAD_PATH . $folder . '/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg','jpeg','png','gif','pdf','xlsx','xls'];

    if (!in_array($file_extension, $allowed_extensions)) return false;
    if ($file['size'] > MAX_FILE_SIZE) return false;

    // Usar nombre original del archivo (limpiando espacios y caracteres raros)
    $filename = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', basename($file['name']));
    $filepath = $upload_dir . $filename;

    // Si ya existe un archivo con el mismo nombre, agregar un número para no sobreescribir
    $i = 1;
    $original_filename = $filename;
    while (file_exists($filepath)) {
        $filename = pathinfo($original_filename, PATHINFO_FILENAME) . "_$i." . $file_extension;
        $filepath = $upload_dir . $filename;
        $i++;
    }

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        logActivity("Archivo subido: $folder/$filename");
        return $folder . '/' . $filename;
    }

    return false;
}

// ===================== LOG DE ACTIVIDAD =====================
function logActivity($message) {
    $log_dir = __DIR__ . '/logs/';
    if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
    $log_file = $log_dir . 'activity.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message\n", FILE_APPEND);
}

// ===================== FUNCIONES DE SANCIONES (DEPRECADAS - Usar nuevo sistema) =====================
function actualizarSanciones($db, $equipo_id) {
    // DEPRECADO: Esta función ya no se usa
    // El nuevo sistema automático cumple las sanciones cuando el partido finaliza
    error_log("ADVERTENCIA: actualizarSanciones() está deprecada. Usar cumplirSancionesAutomaticas()");
}

// ===================== FUNCIONES DE EVENTOS =====================
function getEventosPartido($partido_id) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM eventos_partido WHERE partido_id = ?");
    $stmt->execute([$partido_id]);
    return $stmt->fetchAll();
}

function saveEventosPartido($partido_id, $eventos) {
    $db = Database::getInstance()->getConnection();
    $stmt_delete = $db->prepare("DELETE FROM eventos_partido WHERE partido_id = ?");
    $stmt_delete->execute([$partido_id]);

    $stmt_insert = $db->prepare("
        INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto, descripcion)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($eventos as $e) {
        $stmt_insert->execute([
            $partido_id,
            $e['jugador_id'] ?? null,
            $e['tipo_evento'] ?? null,
            $e['minuto'] ?? null,
            $e['descripcion'] ?? null
        ]);
    }

    logActivity("Eventos guardados para partido ID $partido_id");
}

// ===================== INCLUIR SISTEMA AUTOMÁTICO DE SANCIONES =====================
$sanciones_file = __DIR__ . '/include/sanciones_functions.php';

if (file_exists($sanciones_file)) {
    require_once $sanciones_file;
    
    // Verificar que las funciones se cargaron correctamente
    if (!function_exists('cumplirSancionesAutomaticas')) {
        error_log("ERROR CRÍTICO: sanciones_functions.php se incluyó pero las funciones no están disponibles");
    }
} else {
    error_log("ADVERTENCIA: No se encontró include/sanciones_functions.php en: " . $sanciones_file);
    error_log("Directorio actual: " . __DIR__);
}
?>
