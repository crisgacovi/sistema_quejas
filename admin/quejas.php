<?php
/**
 * Gestión de Quejas - Sistema de Quejas
 * Última modificación: 2025-04-27 02:23:41 UTC
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
            header("Cache-Control: no-cache");
            header("Pragma: no-cache");
            
            // Escribir BOM UTF-8
            echo chr(0xEF) . chr(0xBB) . chr(0xBF);
            
            // Crear el archivo Excel usando HTML
            echo "<table border='1'>";
            
            // Encabezados
            echo "<tr style='background-color: #4CAF50; color: white; font-weight: bold;'>";
            echo "<td>ID</td>";
            echo "<td>Fecha Creación</td>";
            echo "<td>Paciente</td>";
            echo "<td>Documento</td>";
            echo "<td>Email</td>";
            echo "<td>Teléfono</td>";
            echo "<td>Ciudad</td>";
            echo "<td>EPS</td>";
            echo "<td>Tipo de Queja</td>";
            echo "<td>Descripción</td>";
            echo "<td>Respuesta</td>";
            echo "<td>Fecha Respuesta</td>";
            echo "<td>Estado</td>";
            echo "</tr>";
            
            // Datos
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td style='mso-number-format:\"\\@\";'>" . $row['id'] . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($row['fecha_creacion'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['nombre_paciente']) . "</td>";
                echo "<td style='mso-number-format:\"\\@\";'>" . htmlspecialchars($row['documento_identidad']) . "</td>";
                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                echo "<td style='mso-number-format:\"\\@\";'>" . htmlspecialchars($row['telefono']) . "</td>";
                echo "<td>" . htmlspecialchars($row['ciudad_nombre']) . "</td>";
                echo "<td>" . htmlspecialchars($row['eps_nombre']) . "</td>";
                echo "<td>" . htmlspecialchars($row['tipo_queja_nombre']) . "</td>";
                echo "<td style='white-space: pre-wrap;'>" . htmlspecialchars($row['descripcion']) . "</td>";
                echo "<td style='white-space: pre-wrap;'>" . htmlspecialchars($row['respuesta']) . "</td>";
                echo "<td>" . ($row['fecha_respuesta'] ? date('d/m/Y', strtotime($row['fecha_respuesta'])) : '') . "</td>";
                echo "<td>" . htmlspecialchars($row['estado']) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            exit;
        } else {
            $error = "No se encontraron quejas en el período seleccionado.";
        }
    }
}

// Obtener lista de quejas para la tabla principal con paginación
try {
    $sql = "SELECT q.*, c.nombre AS ciudad_nombre, e.nombre AS eps_nombre, 
            t.nombre AS tipo_queja_nombre, q.fecha_creacion
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
} catch (Exception $e) {
    $error = $e->getMessage();
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

                <!-- Tabla de Quejas -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Paciente</th>
                                <th>Ciudad</th>
                                <th>EPS</th>
                                <th>Tipo de Queja</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['nombre_paciente']); ?></td>
                                        <td><?php echo htmlspecialchars($row['ciudad_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($row['eps_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($row['tipo_queja_nombre']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getBadgeClass($row['estado']); ?>">
                                                <?php echo $row['estado']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_creacion'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="ver_queja.php?id=<?php echo $row['id']; ?>" 
                                                   class="btn btn-sm btn-info" title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="editar_queja.php?id=<?php echo $row['id']; ?>" 
                                                   class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($_SESSION['admin_role'] === 'admin'): ?>
                                                    <a href="eliminar_queja.php?id=<?php echo $row['id']; ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('¿Está seguro de que desea eliminar esta queja?');"
                                                       title="Eliminar">
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
                                            <p>No hay quejas registradas.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Navegación de páginas">
                        <ul class="pagination justify-content-center">
                            <!-- Botón Anterior -->
                            <li class="page-item <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>" 
                                   <?php echo $pagina_actual <= 1 ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>
                                    <i class="bi bi-chevron-left"></i> Anterior
                                </a>
                            </li>
                            
                            <!-- Números de página -->
                            <?php
                            $rango = 2;
                            $inicio_rango = max(1, $pagina_actual - $rango);
                            $fin_rango = min($total_paginas, $pagina_actual + $rango);
                            
                            if ($inicio_rango > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?pagina=1">1</a></li>';
                                if ($inicio_rango > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $inicio_rango; $i <= $fin_rango; $i++) {
                                echo '<li class="page-item ' . ($pagina_actual == $i ? 'active' : '') . '">';
                                echo '<a class="page-link" href="?pagina=' . $i . '">' . $i . '</a>';
                                echo '</li>';
                            }
                            
                            if ($fin_rango < $total_paginas) {
                                if ($fin_rango < $total_paginas - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?pagina=' . $total_paginas . '">' . $total_paginas . '</a></li>';
                            }
                            ?>
                            
                            <!-- Botón Siguiente -->
                            <li class="page-item <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>"
                                   <?php echo $pagina_actual >= $total_paginas ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>
                                    Siguiente <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    
                    <!-- Información de paginación -->
                    <div class="text-center text-muted small mt-2">
                        Mostrando <?php echo ($offset + 1); ?> a 
                        <?php echo min($offset + $quejas_por_pagina, $total_quejas); ?> de 
                        <?php echo $total_quejas; ?> quejas
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function validarFechas() {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;
        
        if (!fechaInicio || !fechaFin) {
            alert('Por favor seleccione ambas fechas.');
            return false;
        }
        
        if (fechaInicio > fechaFin) {
            alert('La fecha de inicio no puede ser posterior a la fecha fin.');
            return false;
        }
        
        return true;
    }

    // Establecer fechas máximas y mínimas
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        const fechaInicio = document.getElementById('fecha_inicio');
        const fechaFin = document.getElementById('fecha_fin');
        
        // Establecer fecha máxima como hoy
        fechaInicio.max = today;
        fechaFin.max = today;
        
        // Actualizar fecha mínima de fin cuando cambia inicio
        fechaInicio.addEventListener('change', function() {
            fechaFin.min = this.value;
        });
        
        // Actualizar fecha máxima de inicio cuando cambia fin
        fechaFin.addEventListener('change', function() {
            fechaInicio.max = this.value;
        });
    });
    </script>
</body>
</html>