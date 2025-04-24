<?php
// admin/index.php - Panel de administración
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

// Función para obtener el tipo de archivo
function getFileType($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return $extension === 'pdf' ? 'pdf' : 'image';
}

// Función para formatear el tamaño del archivo
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Obtener estadísticas con manejo de errores
try {
    $stats = [
        'total' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'resolved' => 0,
        'closed' => 0
    ];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM quejas");
    if ($result) {
        $stats['total'] = $result->fetch_assoc()['count'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM quejas WHERE estado = 'pendiente'");
    if ($result) {
        $stats['pending'] = $result->fetch_assoc()['count'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM quejas WHERE estado = 'en_proceso'");
    if ($result) {
        $stats['in_progress'] = $result->fetch_assoc()['count'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM quejas WHERE estado = 'resuelto'");
    if ($result) {
        $stats['resolved'] = $result->fetch_assoc()['count'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM quejas WHERE estado = 'cerrado'");
    if ($result) {
        $stats['closed'] = $result->fetch_assoc()['count'];
    }
} catch (Exception $e) {
    error_log("Error al obtener estadísticas: " . $e->getMessage());
}

// Configuración de paginación
$registros_por_pagina = 10;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina - 1) * $registros_por_pagina;

// Consulta principal para obtener quejas
try {
    $sql = "SELECT q.*, c.nombre as ciudad, e.nombre as eps, t.nombre as tipo_queja 
            FROM quejas q
            LEFT JOIN ciudades c ON q.ciudad_id = c.id
            LEFT JOIN eps e ON q.eps_id = e.id
            LEFT JOIN tipos_queja t ON q.tipo_queja_id = t.id
            ORDER BY q.fecha_creacion DESC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $registros_por_pagina, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    // Obtener total de registros para paginación
    $total_registros = $conn->query("SELECT COUNT(*) as total FROM quejas")->fetch_assoc()['total'];
    $total_paginas = ceil($total_registros / $registros_por_pagina);
} catch (Exception $e) {
    error_log("Error en la consulta principal: " . $e->getMessage());
    $result = false;
    $total_paginas = 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Sistema de Quejas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin-styles.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToExcel()">
                                <i class="bi bi-file-earmark-excel"></i> Exportar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Quejas</h5>
                                <h2><?php echo $stats['total']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Pendientes</h5>
                                <h2><?php echo $stats['pending']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">En Proceso</h5>
                                <h2><?php echo $stats['in_progress']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Resueltas</h5>
                                <h2><?php echo $stats['resolved']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-secondary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Cerradas</h5>
                                <h2><?php echo $stats['closed']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Últimas quejas registradas -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-list-ul"></i> Últimas quejas registradas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Paciente</th>
                                        <th>EPS</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Consultar las últimas 5 quejas
                                    $sql = "SELECT q.id, q.fecha_creacion, q.nombre_paciente, 
                                                e.nombre as eps_nombre, 
                                                t.nombre as tipo_queja_nombre, 
                                                q.estado
                                        FROM quejas q
                                        LEFT JOIN eps e ON q.eps_id = e.id
                                        LEFT JOIN tipos_queja t ON q.tipo_queja_id = t.id
                                        ORDER BY q.fecha_creacion DESC
                                        LIMIT 5";
                                    
                                    $result = $conn->query($sql);
                                    
                                    if ($result && $result->num_rows > 0):
                                        while ($row = $result->fetch_assoc()):
                                            // Asignar las clases de Bootstrap según el estado
                                            $estadoClasses = [
                                                'pendiente' => 'bg-warning text-dark',
                                                'en_proceso' => 'bg-info text-white',
                                                'resuelto' => 'bg-success text-white',
                                                'cerrado' => 'bg-secondary text-white'
                                            ];
                                            
                                            $estadoClass = $estadoClasses[$row['estado']] ?? 'bg-secondary text-white';
                                            $estadoTexto = ucfirst(str_replace('_', ' ', $row['estado']));
                                    ?>
                                            <tr>
                                                <td><?php echo $row['id']; ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_creacion'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['nombre_paciente']); ?></td>
                                                <td><?php echo htmlspecialchars($row['eps_nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($row['tipo_queja_nombre']); ?></td>
                                                <td>
                                                    <span class="badge rounded-pill <?php echo $estadoClass; ?>">
                                                        <?php echo $estadoTexto; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                                    <p>No hay quejas registradas.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <a href="quejas.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-eye"></i> Ver todas las quejas
                            </a>
                        </div>
                    </div>
                </div>

<style>
/* Estilos adicionales para los badges de estado */
.badge {
    font-size: 0.875em;
    padding: 0.5em 0.75em;
    font-weight: 500;
}

.badge.rounded-pill {
    border-radius: 50rem;
}

/* Mejorar la visibilidad de los estados */
.badge.bg-warning.text-dark {
    background-color: #ffc107 !important;
}

.badge.bg-info {
    background-color: #0dcaf0 !important;
}

.badge.bg-success {
    background-color: #198754 !important;
}

.badge.bg-secondary {
    background-color: #6c757d !important;
}
</style>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <script>
        // Inicializar componentes cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // Previsualizar imagen al hacer hover sobre el enlace
            document.querySelectorAll('a[href$=".jpg"], a[href$=".jpeg"]').forEach(link => {
                let preview = document.createElement('div');
                preview.className = 'image-preview';
                preview.style.display = 'none';
                preview.style.position = 'absolute';
                preview.style.backgroundColor = 'white';
                preview.style.padding = '5px';
                preview.style.border = '1px solid #ccc';
                preview.style.borderRadius = '5px';
                preview.style.zIndex = '1000';
                
                let img = document.createElement('img');
                img.src = link.href;
                img.style.maxWidth = '200px';
                img.style.maxHeight = '200px';
                preview.appendChild(img);
                document.body.appendChild(preview);

                link.addEventListener('mouseover', (e) => {
                    preview.style.display = 'block';
                    preview.style.left = e.pageX + 10 + 'px';
                    preview.style.top = e.pageY + 10 + 'px';
                });

                link.addEventListener('mouseout', () => {
                    preview.style.display = 'none';
                });
            });
        });

        // Función para exportar a Excel
        function exportToExcel() {
            const table = document.getElementById('quejasTable');
            const wb = XLSX.utils.table_to_book(table, {sheet: "Quejas"});
            XLSX.writeFile(wb, 'quejas_' + new Date().toISOString().slice(0,10) + '.xlsx');
        }
    </script>
</body>
</html>