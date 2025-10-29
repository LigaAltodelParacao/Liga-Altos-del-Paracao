<?php
require_once '../config.php';

if (!isLoggedIn() || !hasPermission(['superadmin','admin'])) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dni'], $_POST['cancha_id'], $_POST['fecha_id'])) {
    $dni = trim($_POST['dni']);
    $cancha_id = $_POST['cancha_id'];
    $fecha_id = $_POST['fecha_id'];

    try {
        $db->beginTransaction();

        // Generar contraseña aleatoria para usuario
        $password = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"),0,6);

        // Crear usuario planillero
        $stmt = $db->prepare("INSERT INTO usuarios (username, password, nombre, tipo, activo) VALUES (?, ?, ?, 'planillero', 1)");
        $stmt->execute([$dni, $password, $dni]);
        $planillero_id = $db->lastInsertId();

        // Traer partidos de esa cancha y fecha
        $stmt = $db->prepare("SELECT id FROM partidos WHERE cancha_id=? AND fecha_id=?");
        $stmt->execute([$cancha_id, $fecha_id]);
        $partidos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Generar código de acceso aleatorio para el planillero
        $codigo_acceso = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"),0,6);

        foreach ($partidos as $partido_id) {
            $stmt = $db->prepare("INSERT INTO planillas (planillero_id, partido_id, codigo_acceso) VALUES (?, ?, ?)");
            $stmt->execute([$planillero_id, $partido_id, $codigo_acceso]);
        }

        // Actualizar usuario con la clave de acceso
        $stmt = $db->prepare("UPDATE usuarios SET codigo_planillero=? WHERE id=?");
        $stmt->execute([$codigo_acceso, $planillero_id]);

        $db->commit();
        $message = "Planillero creado correctamente. Usuario: $dni | Contraseña inicial: $password | Código de acceso: $codigo_acceso";
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error al crear planillero: " . $e->getMessage();
    }
}

// Obtener canchas y fechas para el formulario
$canchas = $db->query("SELECT id, nombre FROM canchas")->fetchAll(PDO::FETCH_ASSOC);
$fechas = $db->query("SELECT id, numero_fecha FROM fechas")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Crear Planillero</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <h3>Crear Planillero</h3>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="mt-4">
        <div class="mb-3">
            <label>DNI del Planillero</label>
            <input type="text" name="dni" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Seleccionar Cancha</label>
            <select name="cancha_id" class="form-control" required>
                <?php foreach($canchas as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo $c['nombre']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Seleccionar Fecha</label>
            <select name="fecha_id" class="form-control" required>
                <?php foreach($fechas as $f): ?>
                    <option value="<?php echo $f['id']; ?>">Fecha <?php echo $f['numero_fecha']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Generar Planillero</button>
    </form>
</div>
</body>
</html>
