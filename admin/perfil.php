<?php
// perfil.php - Página de perfil de usuario
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

// Obtener ID de usuario desde la sesión
$usuario_id = $_SESSION['admin_id'];

// Mensaje de estado
$mensaje = '';
$tipo_mensaje = '';

// Procesar actualización de perfil
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar y sanear los datos recibidos
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $clave_actual = trim($_POST['clave_actual']);
    $clave_nueva = trim($_POST['clave_nueva']);
    $confirmar_clave = trim($_POST['confirmar_clave']);
    
    // Verificar si los campos obligatorios están completos
    if (empty($nombre) || empty($email)) {
        $mensaje = "Los campos de nombre y email son obligatorios.";
        $tipo_mensaje = "danger";
    } else {
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Actualizar información básica
            $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nombre, $email, $usuario_id);
            $stmt->execute();
            
            // Si se proporcionó una contraseña actual, intentar cambiar la contraseña
            if (!empty($clave_actual)) {
                // Verificar si las contraseñas nuevas coinciden
                if ($clave_nueva !== $confirmar_clave) {
                    throw new Exception("Las contraseñas nuevas no coinciden.");
                }
                
                // Obtener la contraseña actual hash de la base de datos
                $stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
                $stmt->bind_param("i", $usuario_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $usuario = $result->fetch_assoc();
                    
                    // Verificar si la contraseña actual es correcta
                    if (!password_verify($clave_actual, $usuario['password'])) {
                        throw new Exception("La contraseña actual es incorrecta.");
                    }
                    
                    // Actualizar la contraseña
                    $password_hash = password_hash($clave_nueva, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                    $stmt->bind_param("si", $password_hash, $usuario_id);
                    $stmt->execute();
                } else {
                    throw new Exception("No se pudo encontrar el usuario.");
                }
            }
            
            // Confirmar transacción
            $conn->commit();
            $mensaje = "Perfil actualizado correctamente.";
            $tipo_mensaje = "success";
            
            // Actualizar datos de sesión
            $_SESSION['admin_nombre'] = $nombre;
            $_SESSION['admin_email'] = $email;
            
        } catch (Exception $e) {
            // Revertir cambios en caso de error
            $conn->rollback();
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}

// Obtener datos actuales del usuario
$usuario = null;
$stmt = $conn->prepare("SELECT id, nombre, email, username, role FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $usuario = $result->fetch_assoc();
} else {
    $mensaje = "No se pudo cargar la información del usuario.";
    $tipo_mensaje = "danger";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Sistema de Quejas</title>
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
                            <a class="nav-link" href="quejas.php">
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
                            <a class="nav-link active" href="perfil.php">
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
                    <h1 class="h2">Mi Perfil</h1>
                </div>

                <!-- Mensajes de estado -->
                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Información del perfil -->
                <?php if ($usuario): ?>
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Información Personal</h5>
                            </div>
                            <div class="card-body">
                                <form action="perfil.php" method="post">
                                    <div class="mb-3">
                                        <label for="nombre" class="form-label">Nombre Completo</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Correo Electrónico</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Nombre de Usuario</label>
                                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($usuario['username']); ?>" readonly>
                                        <div class="form-text">El nombre de usuario no se puede cambiar.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Rol</label>
                                        <input type="text" class="form-control" id="role" value="<?php echo htmlspecialchars($usuario['role']); ?>" readonly>
                                    </div>
                                    
                                    <hr>
                                    
                                    <h5 class="mb-3">Cambiar Contraseña</h5>
                                    
                                    <div class="mb-3">
                                        <label for="clave_actual" class="form-label">Contraseña Actual</label>
                                        <input type="password" class="form-control" id="clave_actual" name="clave_actual">
                                        <div class="form-text">Complete estos campos solo si desea cambiar su contraseña.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="clave_nueva" class="form-label">Contraseña Nueva</label>
                                        <input type="password" class="form-control" id="clave_nueva" name="clave_nueva">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirmar_clave" class="form-label">Confirmar Contraseña Nueva</label>
                                        <input type="password" class="form-control" id="confirmar_clave" name="confirmar_clave">
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>