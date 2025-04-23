<?php
// editar_queja.php - Editar una queja existente
session_start();

// Definir constante para acceso seguro al sidebar
define('IN_ADMIN', true);

// Verificar si el usuario está autenticado
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Incluir archivo de configuración
require_once "../config.php";

// Función para verificar si el usuario es administrador
function isAdmin() {
    if (!isset($_SESSION['admin_role'])) {
        return false;
    }
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

try {
    // Obtener datos de la queja
    $sql = "SELECT q.*, c.nombre as ciudad_nombre, e.nombre as eps_nombre, t.nombre as tipo_queja_nombre 
            FROM quejas q
            LEFT JOIN ciudades c ON q.ciudad_id = c.id
            LEFT JOIN eps e ON q.eps_id = e.id
            LEFT JOIN tipos_queja t ON q.tipo_queja_id = t.id
            WHERE q.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        throw new Exception("No se encontró la queja especificada.");
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

} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("location: index.php");
    exit;
}

// Procesar el formulario de actualización
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Recoger y validar los datos del formulario
        $nombre_paciente = trim($_POST['nombre_paciente'] ?? '');
        $documento_identidad = trim($_POST['documento_identidad'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $ciudad_id = (int)($_POST['ciudad_id'] ?? 0);
        $eps_id = (int)($_POST['eps_id'] ?? 0);
        $tipo_queja_id = (int)($_POST['tipo_queja_id'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado = trim($_POST['estado'] ?? '');

        // Validar campos obligatorios
        $errores = [];
        if (empty($nombre_paciente)) $errores[] = "El nombre del paciente es obligatorio.";
        if (empty($documento_identidad)) $errores[] = "El documento de identidad es obligatorio.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = "El correo electrónico es inválido.";
        if ($ciudad_id <= 0) $errores[] = "Debe seleccionar una ciudad.";
        if ($eps_id <= 0) $errores[] = "Debe seleccionar una EPS.";
        if ($tipo_queja_id <= 0) $errores[] = "Debe seleccionar un tipo de queja.";
        if (empty($descripcion)) $errores[] = "La descripción es obligatoria.";
        if (empty($estado)) $errores[] = "El estado es obligatorio.";

        if (!empty($errores)) {
            throw new Exception(implode("<br>", $errores));
        }

        // Iniciar transacción
        $conn->begin_transaction();

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
                estado = ?,
                fecha_actualizacion = NOW()
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
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar la queja: " . $stmt->error);
        }

        // Si el estado cambió, agregar un registro de seguimiento
        if ($estado !== $queja['estado']) {
            $comentario = "Estado actualizado de '{$queja['estado']}' a '$estado' por el usuario {$_SESSION['admin_username']}.";
            $usuario_id = $_SESSION['admin_id'];
            
            $sqlSeguimiento = "INSERT INTO seguimientos (queja_id, estado, comentario, usuario_id, fecha_creacion) 
                             VALUES (?, ?, ?, ?, NOW())";
            $stmtSeguimiento = $conn->prepare($sqlSeguimiento);
            $stmtSeguimiento->bind_param("issi", $id, $estado, $comentario, $usuario_id);
            
            if (!$stmtSeguimiento->execute()) {
                throw new Exception("Error al registrar el seguimiento: " . $stmtSeguimiento->error);
            }
        }
        
        // Confirmar transacción
        $conn->commit();
        
        $mensaje = "Queja actualizada exitosamente.";
        $tipo_mensaje = "success";
        
    } catch (Exception $e) {
        // Revertir cambios en caso de error
        $conn->rollback();
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin-styles.css">
    <style>
        .btn-group .btn {
            margin-right: 5px;
        }
        .form-control.is-invalid:focus,
        .form-select.is-invalid:focus {
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
        .form-control.is-valid:focus,
        .form-select.is-valid:focus {
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }
        .modal-content {
            border-radius: 0.5rem;
        }
        .spinner-border-sm {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Editar Queja #<?php echo $id; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="ver_queja.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-secondary" id="btn-volver">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Formulario de Edición</h5>
                    </div>
                    <div class="card-body">
                        <form action="editar_queja.php?id=<?php echo $id; ?>" method="post" id="form-editar-queja" class="needs-validation" novalidate>
                            <div class="row">
                                <!-- Datos del paciente -->
                                <div class="col-md-6">
                                    <h5 class="mb-3">Datos del Paciente</h5>
                                    
                                    <div class="mb-3">
                                        <label for="nombre_paciente" class="form-label">Nombre Completo *</label>
                                        <input type="text" class="form-control" id="nombre_paciente" name="nombre_paciente" 
                                               value="<?php echo htmlspecialchars($queja['nombre_paciente']); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor ingrese el nombre completo del paciente.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="documento_identidad" class="form-label">Documento de Identidad *</label>
                                        <input type="text" class="form-control" id="documento_identidad" name="documento_identidad" 
                                               value="<?php echo htmlspecialchars($queja['documento_identidad']); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor ingrese el documento de identidad.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Correo Electrónico *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($queja['email']); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor ingrese un correo electrónico válido.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="text" class="form-control" id="telefono" name="telefono" 
                                               value="<?php echo htmlspecialchars($queja['telefono'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <!-- Detalles de la queja -->
                                <div class="col-md-6">
                                    <h5 class="mb-3">Detalles de la Queja</h5>
                                    
                                    <div class="mb-3">
                                        <label for="ciudad_id" class="form-label">Ciudad *</label>
                                        <select class="form-select" id="ciudad_id" name="ciudad_id" required>
                                            <option value="">Seleccionar Ciudad</option>
                                            <?php while ($ciudad = $resultCiudades->fetch_assoc()): ?>
                                                <option value="<?php echo $ciudad['id']; ?>" 
                                                        <?php echo $queja['ciudad_id'] == $ciudad['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($ciudad['nombre']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback">
                                            Por favor seleccione una ciudad.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="eps_id" class="form-label">EPS *</label>
                                        <select class="form-select" id="eps_id" name="eps_id" required>
                                            <option value="">Seleccionar EPS</option>
                                            <?php while ($eps = $resultEps->fetch_assoc()): ?>
                                                <option value="<?php echo $eps['id']; ?>" 
                                                        <?php echo $queja['eps_id'] == $eps['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($eps['nombre']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback">
                                            Por favor seleccione una EPS.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="tipo_queja_id" class="form-label">Tipo de Queja *</label>
                                        <select class="form-select" id="tipo_queja_id" name="tipo_queja_id" required>
                                            <option value="">Seleccionar Tipo</option>
                                            <?php while ($tipo = $resultTiposQueja->fetch_assoc()): ?>
                                                <option value="<?php echo $tipo['id']; ?>" 
                                                        <?php echo $queja['tipo_queja_id'] == $tipo['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($tipo['nombre']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback">
                                            Por favor seleccione un tipo de queja.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="estado" class="form-label">Estado *</label>
                                        <select class="form-select" id="estado" name="estado" required>
                                            <option value="pendiente" <?php echo strtolower($queja['estado']) === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                            <option value="en_proceso" <?php echo strtolower($queja['estado']) === 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                                            <option value="resuelto" <?php echo strtolower($queja['estado']) === 'resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                                            <option value="cerrado" <?php echo strtolower($queja['estado']) === 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Por favor seleccione un estado.
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Descripción -->
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="descripcion" class="form-label">Descripción de la Queja *</label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required><?php echo htmlspecialchars($queja['descripcion']); ?></textarea>
                                        <div class="invalid-feedback">
                                            Por favor ingrese la descripción de la queja.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-text">* Campos obligatorios</div>
                            </div>
                            
                            <!-- Botones de acción -->
                            <div class="d-flex justify-content-between">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-secondary" id="btn-cancelar">
                                        <i class="bi bi-x-circle"></i> Cancelar
                                    </button>
                                    <a href="ver_queja.php?id=<?php echo $id; ?>" class="btn btn-info" id="btn-volver">
                                        <i class="bi bi-arrow-left"></i> Volver
                                    </a>
                                </div>
                                <button type="submit" class="btn btn-primary" id="btn-guardar">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal de confirmación -->
                <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="confirmModalLabel">Confirmar acción</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>¿Estás seguro de que deseas salir sin guardar los cambios?</p>
                                <p class="text-danger"><small>Los cambios no guardados se perderán.</small></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x"></i> Cancelar
                                </button>
                                <a href="#" id="confirm-redirect" class="btn btn-primary">
                                    <i class="bi bi-check"></i> Sí, salir
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Referencias a elementos del DOM
        const form = document.getElementById('form-editar-queja');
        const btnCancelar = document.getElementById('btn-cancelar');
        const btnVolver = document.getElementById('btn-volver');
        const btnGuardar = document.getElementById('btn-guardar');
        const confirmModal = document.getElementById('confirmModal');
        const confirmRedirect = document.getElementById('confirm-redirect');
        const modal = new bootstrap.Modal(confirmModal);
        
        // Estado inicial del formulario
        const formInitialState = getFormState();
        let formModificado = false;

        // Función para obtener el estado actual del formulario
        function getFormState() {
            const formData = new FormData(form);
            const state = {};
            for(let [key, value] of formData.entries()) {
                state[key] = value;
            }
            return JSON.stringify(state);
        }

        // Detectar cambios en el formulario
        function checkFormChanges() {
            const currentState = getFormState();
            formModificado = currentState !== formInitialState;
        }

        // Agregar detectores de cambios a todos los campos
        form.querySelectorAll('input, select, textarea').forEach(element => {
            ['change', 'keyup', 'paste'].forEach(event => {
                element.addEventListener(event, () => {
                    setTimeout(checkFormChanges, 0);
                });
            });
        });

        // Función para manejar la navegación
        function handleNavigation(destination, event) {
            if (event) {
                event.preventDefault();
            }
            
            if (formModificado) {
                confirmRedirect.href = destination;
                modal.show();
            } else {
                window.location.href = destination;
            }
        }

        // Manejadores de eventos para los botones
        btnCancelar.addEventListener('click', (e) => {
            handleNavigation('ver_queja.php?id=<?php echo $id; ?>', e);
        });

        btnVolver.addEventListener('click', (e) => {
            handleNavigation('ver_queja.php?id=<?php echo $id; ?>', e);
        });

        // Prevenir navegación si hay cambios sin guardar
        window.addEventListener('beforeunload', (e) => {
            if (formModificado) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Manejo del formulario al enviar
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            } else {
                btnGuardar.disabled = true;
                btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
            }
            form.classList.add('was-validated');
        });

        // Validación de campos en tiempo real
        form.querySelectorAll('input, select, textarea').forEach(element => {
            element.addEventListener('input', function() {
                if (this.checkValidity()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            });
        });

        // Agregar confirmación al modal
        confirmRedirect.addEventListener('click', function(e) {
            e.preventDefault();
            const destination = this.getAttribute('href');
            modal.hide();
            window.location.href = destination;
        });
    });
    </script>
</body>
</html>