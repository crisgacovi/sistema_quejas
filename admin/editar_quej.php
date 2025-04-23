<?php
// editar_queja.php - Editar una queja existente
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
$mensaje = '';
$tipo_mensaje = '';

// Obtener datos de la queja
$sql = "SELECT * FROM quejas WHERE id = ?";
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

// Consultar ciudades para el menú desplegable
$sqlCiudades = "SELECT id, nombre FROM ciudades ORDER BY nombre";
$resultCiudades = $conn->query($sqlCiudades);

// Consultar EPS para el menú desplegable
$sqlEps = "SELECT id, nombre FROM eps ORDER BY nombre";
$resultEps = $conn->query($sqlEps);

// Consultar tipos de queja para el menú desplegable
$sqlTiposQueja = "SELECT id, nombre FROM tipos_queja ORDER BY nombre";
$resultTiposQueja = $conn->query($sqlTiposQueja);

// Procesar el formulario de actualización
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger los datos del formulario
    $nombre_paciente = trim($_POST['nombre_paciente']);
    $documento_identidad = trim($_POST['documento_identidad']);
    $email = trim($_POST['email']);
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : null;
    $ciudad_id = (int)$_POST['ciudad_id'];
    $eps_id = (int)$_POST['eps_id'];
    $tipo_queja_id = (int)$_POST['tipo_queja_id'];
    $descripcion = trim($_POST['descripcion']);
    $estado = trim($_POST['estado']);
    
    // Validar campos obligatorios
    if (empty($nombre_paciente) || empty($documento_identidad) || empty($email) || empty($descripcion)) {
        $mensaje = "Todos los campos marcados con * son obligatorios.";
        $tipo_mensaje = "danger";
    } else {
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Actualizar la queja
            $sql = "UPDATE quejas SET 
                    nombre_paciente = ?,
                    documento_identidad = ?,
                    email = ?,
                    telefono = ?,
                    ciudad_id = ?,
                    eps_id = ?,
                    tipo_queja_id = ?,
                    descripcion = ?,
                    estado = ?
                    WHERE id = ?";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssiiissi", 
                $nombre_paciente, 
                $documento_identidad, 
                $email, 
                $telefono, 
                $ciudad_id, 
                $eps_id, 
                $tipo_queja_id, 
                $descripcion, 
                $estado,
                $id
            );
            
            if ($stmt->execute()) {
                // Si el estado cambió, agregar un registro de seguimiento
                if ($estado !== $queja['estado']) {
                    $comentario = "Estado actualizado de '{$queja['estado']}' a '$estado' a través del formulario de edición.";
                    $usuario_id = $_SESSION['admin_id'];
                    
                    $sqlSeguimiento = "INSERT INTO seguimientos (queja_id, estado, comentario, usuario_id) VALUES (?, ?, ?, ?)";
                    $stmtSeguimiento = $conn->prepare($sqlSeguimiento);
                    $stmtSeguimiento->bind_param("issi", $id, $estado, $comentario, $usuario_id);
                    $stmtSeguimiento->execute();
                }
                
                // Confirmar transacción
                $conn->commit();
                
                $mensaje = "Queja actualizada exitosamente.";
                $tipo_mensaje = "success";
                
                // Actualizar los datos de la queja después de la actualización
                $stmt = $conn->prepare("SELECT * FROM quejas WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $queja = $result->fetch_assoc();
            } else {
                throw new Exception("Error al actualizar la queja.");
            }
        } catch (Exception $e) {
            // Revertir cambios en caso de error
            $conn->rollback();
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
    <title>Editar Queja #<?php echo $id; ?> - Sistema de Quejas</title>
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
                    <h1 class="h2">Editar Queja #<?php echo $id; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="quejas.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-secondary" id="btn-volver">
                                <i class="bi bi-arrow-left"></i> Volver
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

                <!-- Formulario de edición -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Formulario de Edición</h5>
                    </div>
                    <div class="card-body">
                        <form action="editar_queja.php?id=<?php echo $id; ?>" method="post">
                            <div class="row">
                                <!-- Datos del paciente -->
                                <div class="col-md-6">
                                    <h5 class="mb-3">Datos del Paciente</h5>
                                    
                                    <div class="mb-3">
                                        <label for="nombre_paciente" class="form-label">Nombre Completo *</label>
                                        <input type="text" class="form-control" id="nombre_paciente" name="nombre_paciente" value="<?php echo htmlspecialchars($queja['nombre_paciente']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="documento_identidad" class="form-label">Documento de Identidad *</label>
                                        <input type="text" class="form-control" id="documento_identidad" name="documento_identidad" value="<?php echo htmlspecialchars($queja['documento_identidad']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Correo Electrónico *</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($queja['email']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($queja['telefono'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <!-- Detalles de la queja -->
                                <div class="col-md-6">
                                    <h5 class="mb-3">Detalles de la Queja</h5>
                                    
                                    <div class="mb-3">
                                        <label for="ciudad_id" class="form-label">Ciudad *</label>
                                        <select class="form-select" id="ciudad_id" name="ciudad_id" required>
                                            <option value="">Seleccionar Ciudad</option>
                                            <?php if ($resultCiudades && $resultCiudades->num_rows > 0): ?>
                                                <?php while ($ciudad = $resultCiudades->fetch_assoc()): ?>
                                                    <option value="<?php echo $ciudad['id']; ?>" <?php echo $queja['ciudad_id'] == $ciudad['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($ciudad['nombre']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="eps_id" class="form-label">EPS *</label>
                                        <select class="form-select" id="eps_id" name="eps_id" required>
                                            <option value="">Seleccionar EPS</option>
                                            <?php if ($resultEps && $resultEps->num_rows > 0): ?>
                                                <?php while ($eps = $resultEps->fetch_assoc()): ?>
                                                    <option value="<?php echo $eps['id']; ?>" <?php echo $queja['eps_id'] == $eps['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($eps['nombre']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="tipo_queja_id" class="form-label">Tipo de Queja *</label>
                                        <select class="form-select" id="tipo_queja_id" name="tipo_queja_id" required>
                                            <option value="">Seleccionar Tipo</option>
                                            <?php if ($resultTiposQueja && $resultTiposQueja->num_rows > 0): ?>
                                                <?php while ($tipo = $resultTiposQueja->fetch_assoc()): ?>
                                                    <option value="<?php echo $tipo['id']; ?>" <?php echo $queja['tipo_queja_id'] == $tipo['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($tipo['nombre']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="estado" class="form-label">Estado *</label>
                                        <select class="form-select" id="estado" name="estado" required>
                                            <option value="Pendiente" <?php echo $queja['estado'] === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                            <option value="En revisión" <?php echo $queja['estado'] === 'En revisión' ? 'selected' : ''; ?>>En revisión</option>
                                            <option value="Resuelto" <?php echo $queja['estado'] === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                                            <option value="Rechazado" <?php echo $queja['estado'] === 'Rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Descripción -->
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="descripcion" class="form-label">Descripción de la Queja *</label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required><?php echo htmlspecialchars($queja['descripcion']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-text">* Campos obligatorios</div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="quejas.php?id=<?php echo $id; ?>" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
