<?php
// ver_queja.php - Ver detalles de una queja específica
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Incluir archivo de configuración
require_once "../config.php";

// Función para verificar si el usuario es administrador
function isAdmin() {
    // Si no hay información sobre el rol en la sesión, asumir que no es admin
    if (!isset($_SESSION['admin_role'])) {
        return false;
    }
    // Devolver true si el rol es 'admin'
    return $_SESSION['admin_role'] === 'admin';
}

// Verificar si se proporcionó un ID válido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Consulta para obtener detalles de la queja
$sql = "SELECT q.*, c.nombre as ciudad, e.nombre as eps, t.nombre as tipo_queja
        FROM quejas q
        JOIN ciudades c ON q.ciudad_id = c.id
        JOIN eps e ON q.eps_id = e.id
        JOIN tipos_queja t ON q.tipo_queja_id = t.id
        WHERE q.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    // Si no se encuentra la queja, redirigir
    header("location: index.php");
    exit;
}

$queja = $result->fetch_assoc();

// Consulta para obtener seguimientos de la queja
$sqlSeguimientos = "SELECT * FROM seguimientos WHERE queja_id = ? ORDER BY fecha_creacion DESC";
$stmtSeguimientos = $conn->prepare($sqlSeguimientos);
$stmtSeguimientos->bind_param("i", $id);
$stmtSeguimientos->execute();
$resultSeguimientos = $stmtSeguimientos->get_result();

// Mensaje de estado
$mensaje = '';
$tipo_mensaje = '';

// Procesar actualización de estado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $nuevo_estado = $_POST['estado'];
    $comentario = trim($_POST['comentario']);
    
    if (empty($comentario)) {
        $mensaje = "Por favor, agregue un comentario explicando el cambio de estado.";
        $tipo_mensaje = "danger";
    } else {
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Actualizar estado de la queja
            $stmt = $conn->prepare("UPDATE quejas SET estado = ? WHERE id = ?");
            $stmt->bind_param("si", $nuevo_estado, $id);
            $stmt->execute();
            
            // Registrar seguimiento
            $stmt = $conn->prepare("INSERT INTO seguimientos (queja_id, estado, comentario, usuario_id) VALUES (?, ?, ?, ?)");
            $usuario_id = $_SESSION['admin_id'];
            $stmt->bind_param("issi", $id, $nuevo_estado, $comentario, $usuario_id);
            $stmt->execute();
            
            // Confirmar transacción
            $conn->commit();
            
            $mensaje = "Estado actualizado correctamente.";
            $tipo_mensaje = "success";
            
            // Recargar la queja con los datos actualizados
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $queja = $result->fetch_assoc();
            
            // Recargar seguimientos
            $stmtSeguimientos->execute();
            $resultSeguimientos = $stmtSeguimientos->get_result();
            
        } catch (Exception $e) {
            // Revertir cambios en caso de error
            $conn->rollback();
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}

// Procesar agregar seguimiento
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_comment') {
    $comentario = trim($_POST['comentario']);
    
    if (empty($comentario)) {
        $mensaje = "El comentario no puede estar vacío.";
        $tipo_mensaje = "danger";
    } else {
        try {
            // Registrar seguimiento sin cambiar el estado
            $stmt = $conn->prepare("INSERT INTO seguimientos (queja_id, estado, comentario, usuario_id) VALUES (?, ?, ?, ?)");
            $usuario_id = $_SESSION['admin_id'];
            $estado_actual = $queja['estado'];
            $stmt->bind_param("issi", $id, $estado_actual, $comentario, $usuario_id);
            $stmt->execute();
            
            $mensaje = "Comentario agregado correctamente.";
            $tipo_mensaje = "success";
            
            // Recargar seguimientos
            $stmtSeguimientos->execute();
            $resultSeguimientos = $stmtSeguimientos->get_result();
            
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Queja #<?php echo $id; ?> - Sistema de Quejas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin-styles.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h5 class="text-white">HealthComplaints</h5>
                        <p class="text-white-50">Panel de Administración</p>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="bi bi-house-door me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="quejas.php">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Gestión de Quejas
                            </a>
                        </li>
                        <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="eps.php">
                                <i class="bi bi-building me-2"></i>
                                Gestión de EPS
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="ciudades.php">
                                <i class="bi bi-geo-alt me-2"></i>
                                Gestión de Ciudades
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="tipos_queja.php">
                                <i class="bi bi-tags me-2"></i>
                                Tipos de Queja
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="usuarios.php">
                                <i class="bi bi-people me-2"></i>
                                Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reportes.php">
                                <i class="bi bi-graph-up me-2"></i>
                                Reportes
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <hr class="text-white-50">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="perfil.php">
                                <i class="bi bi-person me-2"></i>
                                Perfil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Detalles de la Queja #<?php echo $id; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="quejas.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                            <a href="editar_queja.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mensajes de estado -->
                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Detalles de la queja -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Información de la Queja</h5>
                                <?php
                                $badgeClass = '';
                                switch ($queja['estado']) {
                                    case 'Pendiente':
                                        $badgeClass = 'bg-warning';
                                        break;
                                    case 'En revisión':
                                        $badgeClass = 'bg-primary';
                                        break;
                                    case 'Resuelto':
                                        $badgeClass = 'bg-success';
                                        break;
                                    case 'Rechazado':
                                        $badgeClass = 'bg-danger';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo $queja['estado']; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6 class="text-muted">Información del Paciente</h6>
                                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($queja['nombre_paciente']); ?></p>
                                        <p><strong>Documento:</strong> <?php echo htmlspecialchars($queja['documento_identidad']); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($queja['email']); ?></p>
                                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($queja['telefono'] ?? 'No especificado'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-muted">Detalles de la Queja</h6>
                                        <p><strong>Ciudad:</strong> <?php echo htmlspecialchars($queja['ciudad']); ?></p>
                                        <p><strong>EPS:</strong> <?php echo htmlspecialchars($queja['eps']); ?></p>
                                        <p><strong>Tipo de Queja:</strong> <?php echo htmlspecialchars($queja['tipo_queja']); ?></p>
                                        <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($queja['fecha_creacion'])); ?></p>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <h6 class="text-muted">Descripción</h6>
                                    <div class="border rounded p-3 bg-light">
                                        <?php echo nl2br(htmlspecialchars($queja['descripcion'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Historial de seguimientos -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Historial de Seguimiento</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($resultSeguimientos && $resultSeguimientos->num_rows > 0): ?>
                                    <div class="timeline">
                                        <?php while ($seguimiento = $resultSeguimientos->fetch_assoc()): 
                                            // Obtener información del usuario que realizó el seguimiento
                                            $sql_usuario = "SELECT nombre FROM usuarios WHERE id = ?";
                                            $stmt_usuario = $conn->prepare($sql_usuario);
                                            $stmt_usuario->bind_param("i", $seguimiento['usuario_id']);
                                            $stmt_usuario->execute();
                                            $result_usuario = $stmt_usuario->get_result();
                                            $nombre_usuario = ($result_usuario && $result_usuario->num_rows > 0) ? 
                                                            $result_usuario->fetch_assoc()['nombre'] : 'Usuario desconocido';
                                        ?>
                                            <div class="timeline-item">
                                                <div class="timeline-date">
                                                    <?php echo date('d/m/Y H:i', strtotime($seguimiento['fecha_creacion'])); ?>
                                                </div>
                                                <div class="timeline-content">
                                                    <h6 class="mb-1">
                                                        <?php
                                                        $estadoBadgeClass = '';
                                                        switch ($seguimiento['estado']) {
                                                            case 'Pendiente':
                                                                $estadoBadgeClass = 'bg-warning';
                                                                break;
                                                            case 'En revisión':
                                                                $estadoBadgeClass = 'bg-primary';
                                                                break;
                                                            case 'Resuelto':
                                                                $estadoBadgeClass = 'bg-success';
                                                                break;
                                                            case 'Rechazado':
                                                                $estadoBadgeClass = 'bg-danger';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $estadoBadgeClass; ?>"><?php echo $seguimiento['estado']; ?></span>
                                                        <span class="text-muted ms-2">por <?php echo htmlspecialchars($nombre_usuario); ?></span>
                                                    </h6>
                                                    <div class="border rounded p-2 bg-light">
                                                        <?php echo nl2br(htmlspecialchars($seguimiento['comentario'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">No hay registros de seguimiento para esta queja.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Panel lateral -->
                    <div class="col-md-4">
                        <!-- Cambiar estado -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Actualizar Estado</h5>
                            </div>
                            <div class="card-body">
                                <form action="ver_queja.php?id=<?php echo $id; ?>" method="post">
                                    <input type="hidden" name="action" value="update_status">
                                    
                                    <div class="mb-3">
                                        <label for="estado" class="form-label">Estado</label>
                                        <select class="form-select" id="estado" name="estado" required>
                                            <option value="Pendiente" <?php echo $queja['estado'] === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                            <option value="En revisión" <?php echo $queja['estado'] === 'En revisión' ? 'selected' : ''; ?>>En revisión</option>
                                            <option value="Resuelto" <?php echo $queja['estado'] === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                                            <option value="Rechazado" <?php echo $queja['estado'] === 'Rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="comentario" class="form-label">Comentario</label>
                                        <textarea class="form-control" id="comentario" name="comentario" rows="3" required></textarea>
                                        <div class="form-text">Explique brevemente el motivo del cambio de estado.</div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Actualizar Estado</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Agregar comentario -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Agregar Comentario</h5>
                            </div>
                            <div class="card-body">
                                <form action="ver_queja.php?id=<?php echo $id; ?>" method="post">
                                    <input type="hidden" name="action" value="add_comment">
                                    
                                    <div class="mb-3">
                                        <label for="comentario_nuevo" class="form-label">Comentario</label>
                                        <textarea class="form-control" id="comentario_nuevo" name="comentario" rows="3" required></textarea>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-secondary">Agregar Comentario</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>