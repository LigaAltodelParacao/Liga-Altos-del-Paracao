<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
	redirect('../login.php');
}

$db = Database::getInstance()->getConnection();

// Verificar si se solicitó generar bracket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_bracket'])) {
	$formato_id = $_POST['formato_id'];
	
	try {
		// 1. Verificar que TODOS los partidos de fase clasificatoria estén completos
		$stmt = $db->prepare("\
			SELECT COUNT(*) as pendientes\
			FROM partidos p\
			INNER JOIN zonas z ON p.zona_id = z.id\
			WHERE z.formato_id = ?\
			AND p.tipo_torneo = 'zona'\
			AND (p.goles_local IS NULL OR p.goles_visitante IS NULL)\
		");
		$stmt->execute([$formato_id]);
		$pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['pendientes'];
		
		if ($pendientes > 0) {
			throw new Exception("Aún hay {$pendientes} partidos pendientes en la fase clasificatoria. Todos los partidos deben estar finalizados.");
		}
		
		// 2. Verificar que no se haya generado ya el bracket
		$stmt = $db->prepare("\
			SELECT COUNT(*) as ya_generado\
			FROM partidos p\
			INNER JOIN fases_eliminatorias fe ON p.fase_eliminatoria_id = fe.id\
			WHERE fe.formato_id = ?\
		");
		$stmt->execute([$formato_id]);
		$ya_generado = $stmt->fetch(PDO::FETCH_ASSOC)['ya_generado'];
		
		if ($ya_generado > 0) {
			throw new Exception("El bracket de eliminación directa ya fue generado para este torneo.");
		}
		
		// 3. Obtener configuración de clasificación
		$stmt = $db->prepare("\
			SELECT primeros_clasifican, segundos_clasifican, terceros_clasifican, cuartos_clasifican\
			FROM campeonatos_formato\
			WHERE id = ?\
		");
		$stmt->execute([$formato_id]);
		$config = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// 4. Obtener equipos clasificados con ranking
		$clasificados = obtenerEquiposClasificados($formato_id, $config, $db);
		
		if (empty($clasificados)) {
			throw new Exception("No hay equipos clasificados según la configuración establecida.");
		}
		
		$num_clasificados = count($clasificados);
		
		// 5. Generar bracket con siembra
		$bracket = generarBracketConSiembra($clasificados);
		
		// 6. Obtener la primera fase eliminatoria activa
		$stmt = $db->prepare("\
			SELECT id, nombre \n\			FROM fases_eliminatorias \n\			WHERE formato_id = ? \n\			ORDER BY orden ASC \n\			LIMIT 1\n		");
		$stmt->execute([$formato_id]);
		$primera_fase = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if (!$primera_fase) {
			throw new Exception("No se encontró ninguna fase eliminatoria configurada.");
		}
		
		// 7. Crear partidos del bracket
		$db->beginTransaction();
		
		$orden = 1;
		foreach ($bracket as $enfrentamiento) {
			// Si equipo2 es null, significa que equipo1 tiene BYE y pasa automáticamente
			if ($enfrentamiento['equipo2'] === null) {
				// Aquí podrías registrar el BYE si lo necesitas
				continue;
			}
			
			$stmt = $db->prepare("\
				INSERT INTO partidos \n\				(fase_eliminatoria_id, numero_llave, equipo_local_id, equipo_visitante_id, tipo_torneo, estado)\
				VALUES (?, ?, ?, ?, 'eliminatoria', 'pendiente')\n			");
			$stmt->execute([
				$primera_fase['id'],
				$orden++,
				$enfrentamiento['equipo1']['id'],
				$enfrentamiento['equipo2']['id']
			]);
		}
		
		// Activar la primera fase eliminatoria
		$stmt = $db->prepare("UPDATE fases_eliminatorias SET activa = 1 WHERE id = ?");
		$stmt->execute([$primera_fase['id']]);
		
		$db->commit();
		
		header("Location: torneos_zonas.php?msg=bracket_generado");
		exit;
		
	} catch (Exception $e) {
		if ($db->inTransaction()) {
			$db->rollBack();
		}
		$error = $e->getMessage();
	}
}

/**
 * Obtener equipos clasificados ordenados por ranking
 */
function obtenerEquiposClasificados($formato_id, $config, $db) {
	$clasificados = [];
	
	// Obtener todas las zonas
	$stmt = $db->prepare("SELECT id, nombre FROM zonas WHERE formato_id = ? ORDER BY orden");
	$stmt->execute([$formato_id]);
	$zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	foreach ($zonas as $zona) {
		$tabla = calcularTablaPosiciones($zona['id'], $db);
		
		// Clasificar primeros (siempre)
		if (isset($tabla[0])) {
			$clasificados[] = array_merge($tabla[0], [
				'posicion_zona' => 1,
				'zona_id' => $zona['id'],
				'zona_nombre' => $zona['nombre']
			]);
		}
		
		// Clasificar segundos (si aplica)
		if ($config['segundos_clasifican'] > 0 && isset($tabla[1])) {
			$clasificados[] = array_merge($tabla[1], [
				'posicion_zona' => 2,
				'zona_id' => $zona['id'],
				'zona_nombre' => $zona['nombre']
			]);
		}
		
		// Clasificar terceros (si aplica)
		if ($config['terceros_clasifican'] > 0 && isset($tabla[2])) {
			$clasificados[] = array_merge($tabla[2], [
				'posicion_zona' => 3,
				'zona_id' => $zona['id'],
				'zona_nombre' => $zona['nombre']
			]);
		}
		
		// Clasificar cuartos (si aplica)
		if ($config['cuartos_clasifican'] > 0 && isset($tabla[3])) {
			$clasificados[] = array_merge($tabla[3], [
				'posicion_zona' => 4,
				'zona_id' => $zona['id'],
				'zona_nombre' => $zona['nombre']
			]);
		}
	}
	
	// Ordenar clasificados por: posición en zona → puntos → diferencia de gol → goles a favor
	usort($clasificados, function($a, $b) {
		if ($a['posicion_zona'] != $b['posicion_zona']) {
			return $a['posicion_zona'] - $b['posicion_zona'];
		}
		if ($a['puntos'] != $b['puntos']) {
			return $b['puntos'] - $a['puntos'];
		}
		$dif_a = $a['goles_favor'] - $a['goles_contra'];
		$dif_b = $b['goles_favor'] - $b['goles_contra'];
		if ($dif_a != $dif_b) {
			return $dif_b - $dif_a;
		}
		return $b['goles_favor'] - $a['goles_favor'];
	});
	
	return $clasificados;
}

/**
 * Calcular tabla de posiciones de una zona
 */
function calcularTablaPosiciones($zona_id, $db) {
	$stmt = $db->prepare("\
		SELECT \n\t\t e.id,\n\t\t e.nombre,\n\t\t ez.puntos,\n\t\t COALESCE(SUM(CASE \n\t\t\tWHEN p.equipo_local_id = e.id THEN p.goles_local\n\t\t\tWHEN p.equipo_visitante_id = e.id THEN p.goles_visitante\n\t\t\tELSE 0 \n\t\t END), 0) as goles_favor,\n\t\t COALESCE(SUM(CASE \n\t\t\tWHEN p.equipo_local_id = e.id THEN p.goles_visitante\n\t\t\tWHEN p.equipo_visitante_id = e.id THEN p.goles_local\n\t\t\tELSE 0 \n\t\t END), 0) as goles_contra,\n\t\t COUNT(p.id) as partidos_jugados\n		FROM equipos e\n		INNER JOIN equipos_zonas ez ON e.id = ez.equipo_id\n		LEFT JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id)\n\t\t AND p.zona_id = ?\n\t\t AND p.tipo_torneo = 'zona'\n\t\t AND p.goles_local IS NOT NULL \n\t\t AND p.goles_visitante IS NOT NULL\n		WHERE ez.zona_id = ?\n		GROUP BY e.id, e.nombre, ez.puntos\n		ORDER BY ez.puntos DESC, (goles_favor - goles_contra) DESC, goles_favor DESC\n	");
	$stmt->execute([$zona_id, $zona_id]);
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Generar bracket con siembra (mejor vs peor)
 */
function generarBracketConSiembra($equipos) {
	$num_equipos = count($equipos);
	
	// Calcular la potencia de 2 más cercana
	$potencia_2 = pow(2, ceil(log($num_equipos, 2)));
	
	$bracket = [];
	
	// Si el número de equipos es exactamente una potencia de 2
	if ($num_equipos == $potencia_2) {
		for ($i = 0; $i < $num_equipos / 2; $i++) {
			$bracket[] = [
				'equipo1' => $equipos[$i],
				'equipo2' => $equipos[$num_equipos - 1 - $i]
			];
		}
	} else {
		// Hay equipos con BYE
		$equipos_con_bye = $potencia_2 - $num_equipos;
		
		// Los mejores sembrados (primeros) tienen BYE
		for ($i = 0; $i < $equipos_con_bye; $i++) {
			$bracket[] = [
				'equipo1' => $equipos[$i],
				'equipo2' => null // BYE
			];
		}
		
		// Resto de enfrentamientos
		$equipos_sin_bye = $num_equipos - $equipos_con_bye;
		for ($i = 0; $i < $equipos_sin_bye / 2; $i++) {
			$idx1 = $equipos_con_bye + $i;
			$idx2 = $num_equipos - 1 - $i;
			
			$bracket[] = [
				'equipo1' => $equipos[$idx1],
				'equipo2' => $equipos[$idx2]
			];
		}
	}
	
	return $bracket;
}

// Si se está accediendo directamente al archivo, mostrar interfaz
if (!isset($_POST['generar_bracket'])) {
	// Obtener formatos que tienen fase clasificatoria completa pero sin bracket generado
	$stmt = $db->query("\
		SELECT \n\t\t cf.id,\n\t\t c.nombre as campeonato,\n\t\t cat.nombre as categoria,\n\t\t cf.cantidad_zonas,\n\t\t COUNT(DISTINCT z.id) as zonas_creadas,\n\t\t COUNT(p.id) as total_partidos,\n\t\t SUM(CASE WHEN p.goles_local IS NOT NULL THEN 1 ELSE 0 END) as partidos_completados\n		FROM campeonatos_formato cf\n		INNER JOIN campeonatos c ON cf.campeonato_id = c.id\n		INNER JOIN categorias cat ON c.id = cat.campeonato_id\n		LEFT JOIN zonas z ON cf.id = z.formato_id\n		LEFT JOIN partidos p ON z.id = p.zona_id AND p.tipo_torneo = 'zona'\n		WHERE cf.tipo_formato = 'mixto'\n		AND cf.activo = 1\n		GROUP BY cf.id\n		HAVING total_partidos > 0\n	");
	$formatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Generar Bracket Eliminatorio</title>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-dark bg-success">
		<div class="container-fluid">
			<a class="navbar-brand" href="<?php echo SITE_URL; ?>">
				<i class="fas fa-futbol"></i> Fútbol Manager
			</a>
			<div class="navbar-nav ms-auto">
				<a class="nav-link" href="dashboard.php">Dashboard</a>
				<a class="nav-link" href="<?php echo SITE_URL; ?>logout.php">Salir</a>
			</div>
		</div>
	</nav>

	<div class="container mt-5">
		<div class="row">
			<div class="col-md-12">
				<h2><i class="fas fa-sitemap"></i> Generar Bracket de Eliminación Directa</h2>
				<p class="text-muted">Sistema automático con siembra (mejor vs peor)</p>
				
				<?php if (isset($error)): ?>
					<div class="alert alert-danger">
						<i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
					</div>
				<?php endif; ?>
				
				<div class="alert alert-info">
					<i class="fas fa-info-circle"></i> <strong>Importante:</strong> 
					El bracket solo se puede generar cuando TODOS los partidos de la fase clasificatoria estén completados.
					El sistema ordenará automáticamente a los clasificados y creará los enfrentamientos con siembra.
				</div>
				
				<?php if (empty($formatos)): ?>
					<div class="alert alert-warning">
						No hay torneos con fase clasificatoria disponibles
					</div>
				<?php else: ?>
					<div class="table-responsive">
						<table class="table table-striped">
							<thead class="table-dark">
								<tr>
									<th>Torneo</th>
									<th>Zonas</th>
									<th>Partidos</th>
									<th>Estado</th>
									<th>Acción</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($formatos as $formato): 
									$completado = $formato['total_partidos'] > 0 && 
												 $formato['total_partidos'] == $formato['partidos_completados'];
								?>
									<tr>
										<td>
											<strong><?= htmlspecialchars($formato['campeonato']) ?></strong><br>
											<small class="text-muted"><?= htmlspecialchars($formato['categoria']) ?></small>
										</td>
										<td><?= $formato['zonas_creadas'] ?> zonas</td>
										<td>
											<?= $formato['partidos_completados'] ?> / <?= $formato['total_partidos'] ?>
											<div class="progress mt-1" style="height: 5px;">
												<div class="progress-bar" role="progressbar" 
														 style="width: <?= $formato['total_partidos'] > 0 ? ($formato['partidos_completados'] / $formato['total_partidos'] * 100) : 0 ?>%"></div>
											</div>
										</td>
										<td>
											<?php if ($completado): ?>
												<span class="badge bg-success">
													<i class="fas fa-check"></i> Completo
												</span>
											<?php else: ?>
												<span class="badge bg-warning text-dark">
													<i class="fas fa-clock"></i> Pendiente
												</span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($completado): ?>
												<form method="POST" style="display: inline;">
													<input type="hidden" name="formato_id" value="<?= $formato['id'] ?>">
													<button type="submit" name="generar_bracket" 
															class="btn btn-primary btn-sm"
															onclick="return confirm('¿Está seguro de generar el bracket eliminatorio? Esta acción no se puede deshacer.')">
														<i class="fas fa-sitemap"></i> Generar Bracket
														</button>
												</form>
											<?php else: ?>
												<button class="btn btn-secondary btn-sm" disabled>
													<i class="fas fa-lock"></i> No disponible
												</button>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
				
				<div class="mt-4">
					<a href="torneos_zonas.php" class="btn btn-secondary">
						<i class="fas fa-arrow-left"></i> Volver
					</a>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
}
?>