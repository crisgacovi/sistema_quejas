<?php
/**
 * Gestión de Usuarios - Sistema de Quejas
 * Última modificación: 2025-04-23 20:51:18 UTC
 * @author crisgacovi
 */

session_start();

// Definir constante para acceso seguro al sidebar
define('IN_ADMIN', true);

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['admin_role'] !== 'admin') {
    header("location: index.php");
    exit;
}

require_once "../config.php";

// Procesar formulario de creación/edición
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['action'])) {
            $username = trim($_POST['username']);
            $nombre_completo = trim($_POST['nombre_completo']);
            $email = trim($_POST['email']);
            $role = trim($_POST['role']);
            $estado = isset($_POST['estado']) ? 1 : 0;

            // Validaciones
            if (empty($username) || empty($nombre_completo) || empty($email)) {
                throw new Exception("Todos los campos son requeridos.");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("El formato del email no es válido.");
            }

            if ($_POST['action'] == 'create') {
                // Verificar si el usuario ya existe
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ? OR email = ?");
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("El nombre de usuario o email ya está en uso.");
                }

                // Verificar que se haya proporcionado una contraseña
                if (empty($_POST['password'])) {
                    throw new Exception("La contraseña es requerida para nuevos usuarios.");
                }

                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO usuarios (username, password, nombre_completo, email, role, estado) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssi", $username, $password, $nombre_completo, $email, $role, $estado);
                $mensaje = "Usuario creado exitosamente.";
            } else if ($_POST['action'] == 'edit' && isset($_POST['id'])) {
                $id = (int)$_POST['id'];
                
                // Verificar si el usuario existe
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows == 0) {
                    throw new Exception("Usuario no encontrado.");
                }

                // Verificar duplicados excepto para el usuario actual
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->bind_param("ssi", $username, $email, $id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("El nombre de usuario o email ya está en uso por otro usuario.");
                }

                if (!empty($_POST['password'])) {
                    // Si se proporciona una nueva contraseña, actualizarla
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE usuarios SET username = ?, password = ?, nombre_completo = ?, email = ?, role = ?, estado = ? WHERE id = ?");
                    $stmt->bind_param("sssssii", $username, $password, $nombre_completo, $email, $role, $estado, $id);
                } else {
                    // Si no hay nueva contraseña, actualizar sin cambiar la contraseña
                    $stmt = $conn->prepare("UPDATE usuarios SET username = ?, nombre_completo = ?, email = ?, role = ?, estado = ? WHERE id = ?");
                    $stmt->bind_param("ssssii", $username, $nombre_completo, $email, $role, $estado, $id);
                }
                $mensaje = "Usuario actualizado exitosamente.";
            }

            if ($stmt->execute()) {
                $success = true;
            } else {
                throw new Exception("Error al procesar la operación: " . $stmt->error);
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Eliminar usuario
if (isset($_GET['delete'])) {
    try {
        $id = (int)$_GET['delete'];
        
        // No permitir eliminar el propio usuario
        if ($id == $_SESSION['admin_id']) {
            throw new Exception("No puedes eliminar tu propio usuario.");
        }

        // Verificar si es el último administrador
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE role = 'admin' AND id != ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $admins_count = $result->fetch_assoc()['total'];

        if ($admins_count == 0) {
            throw new Exception("No se puede eliminar el último usuario administrador.");
        }

        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = true;
            $mensaje = "Usuario eliminado exitosamente.";
        } else {
            throw new Exception("Error al eliminar el usuario: " . $stmt->error);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener lista de usuarios
try {
    $result = $conn->query("SELECT * FROM usuarios ORDER BY username");
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Sistema de Quejas</title>
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
                    <h1 class="h2">Gestión de Usuarios</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#usuarioModal">
                        <i class="bi bi-plus-circle"></i> Nuevo Usuario
                    </button>
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
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Último acceso</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['id']; ?></td>
                                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                                <td><?php echo htmlspecialchars($row['nombre_completo']); ?></td>
                                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $row['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                                        <?php echo ucfirst($row['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $row['estado'] ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo $row['estado'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo $row['ultimo_login'] ? date('d/m/Y H:i', strtotime($row['ultimo_login'])) : 'Nunca'; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" 
                                                                class="btn btn-sm btn-primary"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#usuarioModal"
                                                                data-id="<?php echo $row['id']; ?>"
                                                                data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                                                data-nombre="<?php echo htmlspecialchars($row['nombre_completo']); ?>"
                                                                data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                                                data-role="<?php echo $row['role']; ?>"
                                                                data-estado="<?php echo $row['estado']; ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <?php if ($row['id'] != $_SESSION['admin_id']): ?>
                                                            <a href="usuarios.php?delete=<?php echo $row['id']; ?>" 
                                                               class="btn btn-sm btn-danger"
                                                               onclick="return confirm('¿Está seguro de que desea eliminar este usuario?');">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                                    <p>No hay usuarios registrados.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para crear/editar usuario -->
    <div class="modal fade" id="usuarioModal" tabindex="-1" aria-labelledby="usuarioModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="usuarioModalLabel">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="usuarioForm" action="usuarios.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="id" id="usuario_id">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Nombre de Usuario</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="form-text" id="passwordHelp">Dejar en blanco para mantener la contraseña actual (solo en edición).</div>
                        </div>

                        <div class="mb-3">
                            <label for="nombre_completo" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Rol</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="editor">Editor</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="estado" name="estado" value="1">
                                <label class="form-check-label" for="estado">
                                    Usuario activo
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const usuarioModal = document.getElementById('usuarioModal');
        if (usuarioModal) {
            usuarioModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const modal = this;
                const form = modal.querySelector('form');
                const modalTitle = modal.querySelector('.modal-title');
                const passwordField = form.querySelector('#password');
                const passwordHelp = form.querySelector('#passwordHelp');
                
                if (button.hasAttribute('data-id')) {
                    // Modo edición
                    const id = button.getAttribute('data-id');
                    const username = button.getAttribute('data-username');
                    const nombre = button.getAttribute('data-nombre');
                    const email = button.getAttribute('data-email');
                    const role = button.getAttribute('data-role');
                    const estado = button.getAttribute('data-estado');
                    
                    modalTitle.textContent = 'Editar Usuario';
                    form.querySelector('input[name="action"]').value = 'edit';
                    form.querySelector('#usuario_id').value = id;
                    form.querySelector('#username').value = username;
                    form.querySelector('#nombre_completo').value = nombre;
                    form.querySelector('#email').value = email;
                    form.querySelector('#role').value = role;
                    form.querySelector('#estado').checked = estado === '1';
                    
                    passwordField.required = false;
                    passwordHelp.style.display = 'block';
                } else {
                    // Modo creación
                    modalTitle.textContent = 'Nuevo Usuario';
                    form.querySelector('input[name="action"]').value = 'create';
                    form.querySelector('#usuario_id').value = '';
                    form.querySelector('#username').value = '';
                    form.querySelector('#password').value = '';
                    form.querySelector('#nombre_completo').value = '';
                    form.querySelector('#email').value = '';
                    form.querySelector('#role').value = 'editor';
                    form.querySelector('#estado').checked = true;
                    
                    passwordField.required = true;
                    passwordHelp.style.display = 'none';
                }
            });
        }
    });
    </script>
</body>
</html>