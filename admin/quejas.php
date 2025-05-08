<?php
/**
 * Gestión de Quejas - Sistema de Quejas
 * Última modificación: 2025-05-06 04:19:51 UTC
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

require_once "../config/config.php";

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

// Configuración de la paginación
$quejas_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $quejas_por_pagina;

// Obtener el total de quejas para la paginación
try {
    $sql_total = "SELECT COUNT(*) as total FROM quejas";
    $result_total = $conn->query($sql_total);
    $row_total = $result_total->fetch_assoc();
    $total_quejas = $row_total['total'];
    $total_paginas = ceil($total_quejas / $quejas_por_pagina);
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Procesar la generación del reporte Excel si se solicita
if (isset($_POST['generar_reporte'])) {
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    
    // Validar fechas
    if (empty($fecha_inicio) || empty($fecha_fin)) {
        $error = "Por favor seleccione ambas fechas para generar el reporte.";
    } else {
        // Consulta para el reporte incluyendo fecha_respuesta
        $sql = "SELECT q.id, q.fecha_creacion, q.nombre_paciente, q.documento_identidad, 
                       q.email, q.telefono, c.nombre AS ciudad_nombre, 
                       e.nombre AS eps_nombre, t.nombre AS tipo_queja_nombre, 
                       q.descripcion, q.respuesta, q.fecha_respuesta, q.estado
                FROM quejas q
                JOIN ciudades c ON q.ciudad_id = c.id
                JOIN eps e ON q.eps_id = e.id
                JOIN tipos_queja t ON q.tipo_queja_id = t.id
                WHERE DATE(q.fecha_creacion) BETWEEN ? AND ?
                ORDER BY q.fecha_creacion DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Nombre del archivo
            $filename = "reporte_quejas_" . date('Y-m-d') . ".xls";
            
            // Configurar headers para descarga de Excel
            header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
            header("Content-Disposition: attachment; filename=\"$filename\"");
            
            // Crear archivo Excel
            echo "\xEF\xBB\xBF"; // BOM para UTF-8
            echo "<table border='1'>";
            echo "<tr>
                    <th>ID</th>
                    <th>Fecha Creación</th>
                    <th>Paciente</th>
                    <th>Documento</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Ciudad</th>
                    <th>EPS</th>
                    <th>Tipo Queja</th>
                    <th>Descripción</th>
                    <th>Respuesta</th>
                    <th>Fecha Respuesta</th>
                    <th>Estado</th>
                  </tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            
            echo "</table>";
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Quejas - Sistema de Quejas</title>
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
                    <h1 class="h2">Gestión de Quejas</h1>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Sección de Generación de Reportes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Generar Reporte de Quejas
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3" onsubmit="return validarFechas()">
                            <div class="col-md-4">
                                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                            </div>
                            <div class="col-md-4">
                                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" name="generar_reporte" class="btn btn-primary">
                                    <i class="bi bi-download"></i> Generar Reporte Excel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de quejas -->
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Paciente</th>
                                <th>Ciudad</th>
                                <th>EPS</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $sql = "SELECT q.*, c.nombre AS ciudad_nombre, e.nombre AS eps_nombre, 
                                              t.nombre AS tipo_queja_nombre
                                       FROM quejas q
                                       JOIN ciudades c ON q.ciudad_id = c.id
                                       JOIN eps e ON q.eps_id = e.id
                                       JOIN tipos_queja t ON q.tipo_queja_id = t.id
                                       ORDER BY q.fecha_creacion DESC
                                       LIMIT ? OFFSET ?";
                                
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("ii", $quejas_por_pagina, $offset);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                while ($queja = $result->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><?php echo $queja['id']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($queja['fecha_creacion'])); ?></td>
                                    <td><?php echo htmlspecialchars($queja['nombre_paciente']); ?></td>
                                    <td><?php echo htmlspecialchars($queja['ciudad_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($queja['eps_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($queja['tipo_queja_nombre']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getBadgeClass($queja['estado']); ?>">
                                            <?php echo $queja['estado']; ?>
                                        </span>
                                    </td>
                                    <td class="text-start">
                                        
                                        <a href="ver_queja.php?id=<?php echo $queja['id']; ?>" 
                                           class="btn btn-primary btn-sm" title="Ver Detalles">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="editar_queja.php?id=<?php echo $queja['id']; ?>" 
                                           class="btn btn-warning btn-sm" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($queja['estado'] === 'Resuelto' && !empty($queja['respuesta'])): ?>
                                            <button type="button" class="btn btn-info btn-sm enviar-email" 
                                                    data-queja-id="<?php echo $queja['id']; ?>"
                                                    title="Enviar Email de Respuesta">
                                                <i class="bi bi-envelope"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($_SESSION['admin_role'] === 'admin'): ?>
                                            <a href="eliminar_queja.php?id=<?php echo $queja['id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('¿Está seguro de eliminar esta queja?')"
                                               title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            } catch (Exception $e) {
                                echo '<tr><td colspan="8" class="text-center text-danger">Error al cargar las quejas</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                <nav aria-label="Navegación de páginas">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>">Anterior</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?php echo $i === $pagina_actual ? 'active' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Modal para envío de email -->
    <div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emailModalLabel">Enviar Email de Respuesta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="emailForm">
                        <input type="hidden" id="queja_id" name="queja_id">
                        <div class="mb-3">
                            <label for="mensaje" class="form-label">Mensaje Adicional (opcional):</label>
                            <textarea class="form-control" id="mensaje" name="mensaje" rows="4" 
                                    placeholder="Escriba un mensaje adicional si lo desea..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="enviarEmailBtn">Enviar Email</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Manejar clic en botón de email
        document.querySelectorAll('.enviar-email').forEach(button => {
            button.addEventListener('click', function() {
                const quejaId = this.getAttribute('data-queja-id');
                document.getElementById('queja_id').value = quejaId;
                new bootstrap.Modal(document.getElementById('emailModal')).show();
            });
        });

        // Manejar envío de email
        document.getElementById('enviarEmailBtn').addEventListener('click', function() {
            const form = document.getElementById('emailForm');
            const formData = new FormData(form);

            // Deshabilitar botón mientras se envía
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';

            fetch('procesar_email_queja.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Email enviado exitosamente');
                    bootstrap.Modal.getInstance(document.getElementById('emailModal')).hide();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error al enviar el email: ' + error);
            })
            .finally(() => {
                // Restaurar botón
                this.disabled = false;
                this.innerHTML = 'Enviar Email';
            });
        });
    });
    </script>
</body>
</html>