<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasPermission(['superadmin','admin'])) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// ----------------------
// Crear planillero nuevo
// ----------------------
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['dni'], $_POST['cancha_id'], $_POST['fecha_id'], $_POST['action']) && $_POST['action']=='crear_planillero') {
    $dni = trim($_POST['dni']);
    $cancha_id = $_POST['cancha_id'];
    $fecha_id = $_POST['fecha_id'];

    try {
        $db->beginTransaction();

        // Contraseña aleatoria
        $password = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"),0,6);

        // Crear usuario planillero
        $stmt = $db->prepare("INSERT INTO usuarios (username,password,nombre,tipo,activo) VALUES (?,?,?,?,1)");
        $stmt->execute([$dni, $password, $dni, 'planillero']);
        $planillero_id = $db->lastInsertId();

        // Traer partidos de esa cancha y fecha
        $stmt = $db->prepare("SELECT id FROM partidos WHERE cancha_id=? AND fecha_id=?");
        $stmt->execute([$cancha_id, $fecha_id]);
        $partidos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Generar código de acceso aleatorio
        $codigo_acceso = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"),0,6);

        foreach($partidos as $partido_id){
            $stmt = $db->prepare("INSERT INTO planillas (planillero_id,partido_id,codigo_acceso) VALUES (?,?,?)");
            $stmt->execute([$planillero_id, $partido_id, $codigo_acceso]);
        }

        // Actualizar usuario con clave
        $stmt = $db->prepare("UPDATE usuarios SET codigo_planillero=? WHERE id=?");
        $stmt->execute([$codigo_acceso, $planillero_id]);

        $db->commit();
        $message = "Planillero creado: Usuario = $dni | Contraseña = $password | Código = $codigo_acceso";

    } catch(Exception $e){
        $db->rollBack();
        $error = "Error al crear planillero: ".$e->getMessage();
    }
}

// ----------------------
// Generar claves para planilleros existentes
// ----------------------
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['fecha_id'], $_POST['action']) && $_POST['action']=='generar_claves') {
    $fecha_id = $_POST['fecha_id'];

    $stmt = $db->prepare("SELECT DISTINCT p.cancha_id FROM partidos p WHERE p.fecha_id=?");
    $stmt->execute([$fecha_id]);
    $canchas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $clavesGeneradas = [];
    foreach($canchas as $cancha){
        $clave = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"),0,6);

        $stmtUpd = $db->prepare("UPDATE usuarios u 
                                 JOIN planillas pl ON u.id=pl.planillero_id
                                 JOIN partidos p ON pl.partido_id=p.id
                                 SET u.codigo_planillero=? 
                                 WHERE p.fecha_id=? AND p.cancha_id=?");
        $stmtUpd->execute([$clave, $fecha_id, $cancha['cancha_id']]);

        $clavesGeneradas[] = ['cancha_id'=>$cancha['cancha_id'],'clave'=>$clave];
    }
}

// Obtener canchas y fechas
$canchas = $db->query("SELECT id,nombre FROM canchas")->fetchAll(PDO::FETCH_ASSOC);
$fechas = $db->query("SELECT id,numero_fecha FROM fechas")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Generar Claves / Crear Planillero</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
<link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">

    <h3>Generar Claves y Crear Planillero</h3>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row mt-4">

        <!-- Crear Planillero -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">Crear Planillero</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="crear_planillero">
                        <div class="mb-3">
                            <label>DNI del Planillero</label>
                            <input type="text" name="dni" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Cancha</label>
                            <select name="cancha_id" class="form-control" required>
                                <?php foreach($canchas as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo $c['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Fecha</label>
                            <select name="fecha_id" class="form-control" required>
                                <?php foreach($fechas as $f): ?>
                                    <option value="<?php echo $f['id']; ?>">Fecha <?php echo $f['numero_fecha']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Generar Planillero</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Generar Claves -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">Generar Claves para Planilleros Existentes</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="generar_claves">
                        <div class="mb-3">
                            <label>Seleccionar Fecha</label>
                            <select name="fecha_id" class="form-control" required>
                                <?php foreach($fechas as $f): ?>
                                    <option value="<?php echo $f['id']; ?>">Fecha <?php echo $f['numero_fecha']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Generar Claves</button>
                    </form>

                    <?php if(!empty($clavesGeneradas)): ?>
                        <table class="table table-bordered mt-3">
                            <thead>
                                <tr><th>Cancha</th><th>Clave</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($clavesGeneradas as $c): 
                                    $stmtC = $db->prepare("SELECT nombre FROM canchas WHERE id=?");
                                    $stmtC->execute([$c['cancha_id']]);
                                    $nombre = $stmtC->fetchColumn();
                                ?>
                                    <tr><td><?php echo $nombre; ?></td><td><?php echo $c['clave']; ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>

</div>
</body>
</html>
