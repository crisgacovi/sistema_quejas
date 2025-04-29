<?php
/**
 * Editar Queja - Sistema de Quejas
 * Última modificación: 2025-04-26 20:15:24 UTC
 * @author crisgacovi
 */

session_start();

// Definir constante para acceso seguro al sidebar
define('IN_ADMIN', true);

// Verificar si el usuario está autenticado
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("location: login.php");
    exit;
}

require_once "../config.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("location: quejas.php");
    exit;
}

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $estado = $_POST['estado'];
        $respuesta = trim($_POST['respuesta']);
        $fecha_respuesta = !empty($_POST['fecha_respuesta']) ? $_POST['fecha_respuesta'] : null;
        
        // Actualizar la queja incluyendo la respuesta y fecha
        $stmt = $conn->prepare("UPDATE quejas SET estado = ?, respuesta = ?, fecha_respuesta = ? WHERE id = ?");
        $stmt->bind_param("sssi", $estado, $respuesta, $fecha_respuesta, $id);
        
        if ($stmt->execute()) {
            $success = true;
            $mensaje = "Queja actualizada exitosamente.";
        } else {
            throw new Exception("Error al actualizar la queja: " . $stmt->error);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener datos de la queja
try {
    $sql = "SELECT q.*, c.nombre AS ciudad_nombre, e.nombre AS eps_nombre, 
            t.nombre AS tipo_queja_nombre
            FROM quejas q
            JOIN ciudades c ON q.ciudad_id = c.id
            JOIN eps e ON q.eps_id = e.id
            JOIN tipos_queja t ON q.tipo_queja_id = t.id
            WHERE q.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("location: quejas.php");
        exit;
    }
    
    $queja = $result->fetch_assoc();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Función para obtener la clase de badge según el estado
function getBadgeClass($estado) {
    $badges = array(
        'Pendiente' => 'warning',
        'En Proceso' => 'info',
        'Resuelto' => 'success',
        'Cerrado' => 'secondary'
    );
    return isset($badges[$estado]) ? $badges[$estado] : 'primary';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Queja - Sistema de Quejas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin-styles.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Editar Queja #<?php echo $id; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="quejas.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (isset($success) && $success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i> <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form action="editar_queja.php?id=<?php echo $id; ?>" method="POST" id="quejaForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="card-title">Información del Paciente</h5>
                                    <dl class="row">
                                        <dt class="col-sm-4">Nombre:</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($queja['nombre_paciente']); ?></dd>
                                        
                                        <dt class="col-sm-4">Documento:</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($queja['documento_identidad']); ?></dd>
                                        
                                        <dt class="col-sm-4">Email:</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($queja['email']); ?></dd>
                                        
                                        <dt class="col-sm-4">Teléfono:</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($queja['telefono']); ?></dd>
                                        
                                        <dt class="col-sm-4">Ciudad:</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($queja['ciudad_nombre']); ?></dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="card-title">Detalles de la Queja</h5>
                                    <dl class="row">
                                        <dt class="col-sm-4">EPS:</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($queja['eps_nombre']); ?></dd>
                                        
                                        <dt class="col-sm-4">Tipo de Queja:</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($queja['tipo_queja_nombre']); ?></dd>
                                        
                                        <dt class="col-sm-4">Estado:</dt>
                                        <dd class="col-sm-8">
                                            <select name="estado" class="form-select" required>
                                                <option value="Pendiente" <?php echo $queja['estado'] === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                                <option value="En Proceso" <?php echo $queja['estado'] === 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                                                <option value="Resuelto" <?php echo $queja['estado'] === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                                                <option value="Cerrado" <?php echo $queja['estado'] === 'Cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                                            </select>
                                        </dd>
                                        
                                        <dt class="col-sm-4">Fecha Creación:</dt>
                                        <dd class="col-sm-8"><?php echo date('d/m/Y H:i', strtotime($queja['fecha_creacion'])); ?></dd>
                                    </dl>
                                </div>
                            </div>
                            
                            <h5 class="card-title mt-4">Descripción de la Queja</h5>
                            <div class="card mb-4">
                                <div class="card-body bg-light">
                                    <?php echo nl2br(htmlspecialchars($queja['descripcion'])); ?>
                                </div>
                            </div>

                            <?php if ($queja['archivo_adjunto']): ?>
                            <h6 class="card-title mt-4">Archivo Adjunto</h6>
                            <div class="card">
                                <div class="card-body">
                                    <?php
                                    $archivo_path = "../" . $queja['archivo_adjunto'];
                                    if (file_exists($archivo_path)): ?>
                                        <a href="descargar_archivo.php?id=<?php echo $id; ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="bi bi-file-earmark"></i> Descargar archivo adjunto
                                        </a>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle"></i> El archivo no se encuentra disponible.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Sección de Respuesta con Fecha -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Respuesta de la Queja</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="fecha_respuesta" class="form-label">Fecha de Respuesta</label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="fecha_respuesta" 
                                                   name="fecha_respuesta"
                                                   value="<?php echo $queja['fecha_respuesta'] ?? ''; ?>"
                                                   max="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div class="col-md-9 mb-3">
                                            <label for="respuesta" class="form-label">Respuesta</label>
                                            <textarea class="form-control" 
                                                      id="respuesta"
                                                      name="respuesta" 
                                                      rows="5" 
                                                      placeholder="Escriba aquí la respuesta a la queja..."><?php 
                                                echo htmlspecialchars($queja['respuesta'] ?? ''); 
                                            ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const fechaRespuesta = document.getElementById('fecha_respuesta');
        const respuestaTextarea = document.getElementById('respuesta');
        const today = new Date().toISOString().split('T')[0];
        
        // Establecer fecha máxima como hoy
        fechaRespuesta.max = today;
        
        // Validar que si hay respuesta, debe haber fecha
        document.getElementById('quejaForm').addEventListener('submit', function(e) {
            const respuestaTexto = respuestaTextarea.value.trim();
            const fecha = fechaRespuesta.value;
            
            if (respuestaTexto && !fecha) {
                e.preventDefault();
                alert('Si ingresa una respuesta, debe especificar la fecha de respuesta.');
                return false;
            }
            
            if (fecha && !respuestaTexto) {
                e.preventDefault();
                alert('Si especifica una fecha de respuesta, debe ingresar la respuesta.');
                return false;
            }
            
            return true;
        });
    });
    </script>
</body>
</html>