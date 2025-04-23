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
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Quejas</h5>
                                <h2><?php echo $stats['total']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Pendientes</h5>
                                <h2><?php echo $stats['pending']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">En Proceso</h5>
                                <h2><?php echo $stats['in_progress']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Resueltas</h5>
                                <h2><?php echo $stats['resolved']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Quejas -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Últimas Quejas Registradas</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="quejasTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Paciente</th>
                                            <th>EPS</th>
                                            <th>Tipo</th>
                                            <th>Ciudad</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th>Archivo</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($queja = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $queja['id']; ?></td>
                                            <td><?php echo htmlspecialchars($queja['nombre_paciente']); ?></td>
                                            <td><?php echo htmlspecialchars($queja['eps']); ?></td>
                                            <td><?php echo htmlspecialchars($queja['tipo_queja']); ?></td>
                                            <td><?php echo htmlspecialchars($queja['ciudad']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $queja['estado'] == 'pendiente' ? 'warning' : 
                                                        ($queja['estado'] == 'en_proceso' ? 'info' : 
                                                        ($queja['estado'] == 'resuelto' ? 'success' : 'secondary')); 
                                                ?>">
                                                    <?php echo ucfirst($queja['estado']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($queja['fecha_creacion'])); ?></td>
                                            <td>
                                                <?php if (!empty($queja['archivo_adjunto'])): ?>
                                                    <?php
                                                    $file_type = getFileType($queja['archivo_adjunto']);
                                                    $file_size = file_exists("../" . $queja['archivo_adjunto']) ? 
                                                        formatFileSize(filesize("../" . $queja['archivo_adjunto'])) : 'N/A';
                                                    $icon_class = $file_type === 'pdf' ? 'bi-file-pdf' : 'bi-file-image';
                                                    ?>
                                                    <a href="../<?php echo htmlspecialchars($queja['archivo_adjunto']); ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       target="_blank"
                                                       data-bs-toggle="tooltip" 
                                                       title="Ver archivo (<?php echo $file_size; ?>)">
                                                        <i class="bi <?php echo $icon_class; ?>"></i>
                                                        <?php echo $file_type === 'pdf' ? 'PDF' : 'Imagen'; ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin archivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="#" 
                                                       class="btn btn-sm btn-info"
                                                       data-bs-toggle="modal"
                                                       data-bs-target="#verQueja<?php echo $queja['id']; ?>"
                                                       title="Ver detalles">
                                                        <i class="bi bi-eye"></i> Ver
                                                    </a>
                                                    <a href="editar_queja.php?id=<?php echo $queja['id']; ?>" 
                                                       class="btn btn-sm btn-warning" 
                                                       title="Editar">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </a>
                                                    <?php if (!empty($queja['archivo_adjunto'])): ?>
                                                        <a href="../<?php echo htmlspecialchars($queja['archivo_adjunto']); ?>" 
                                                           class="btn btn-sm btn-success" 
                                                           download 
                                                           title="Descargar archivo">
                                                            <i class="bi bi-download"></i> Descargar
                                                        </a>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Modal para ver detalles -->
                                                <div class="modal fade" id="verQueja<?php echo $queja['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Detalles de la Queja #<?php echo $queja['id']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <p><strong>Paciente:</strong> <?php echo htmlspecialchars($queja['nombre_paciente']); ?></p>
                                                                        <p><strong>Documento:</strong> <?php echo htmlspecialchars($queja['documento_identidad']); ?></p>
                                                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($queja['email']); ?></p>
                                                                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($queja['telefono'] ?? 'No especificado'); ?></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p><strong>EPS:</strong> <?php echo htmlspecialchars($queja['eps']); ?></p>
                                                                        <p><strong>Ciudad:</strong> <?php echo htmlspecialchars($queja['ciudad']); ?></p>
                                                                        <p><strong>Tipo de Queja:</strong> <?php echo htmlspecialchars($queja['tipo_queja']); ?></p>
                                                                        <p><strong>Estado:</strong> 
                                                                            <span class="badge bg-<?php 
                                                                                echo $queja['estado'] == 'pendiente' ? 'warning' : 
                                                                                    ($queja['estado'] == 'en_proceso' ? 'info' : 
                                                                                    ($queja['estado'] == 'resuelto' ? 'success' : 'secondary')); 
                                                                            ?>">
                                                                                <?php echo ucfirst($queja['estado']); ?>
                                                                            </span>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                                <div class="row mt-3">
                                                                    <div class="col-12">
                                                                        <h6>Descripción:</h6>
                                                                        <p class="border p-3 bg-light">
                                                                            <?php echo nl2br(htmlspecialchars($queja['descripcion'])); ?>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                                <?php if (!empty($queja['archivo_adjunto'])): ?>
                                                                    <div class="row mt-3">
                                                                        <div class="col-12">
                                                                            <h6>Archivo Adjunto:</h6>
                                                                            <div class="border p-3 bg-light">
                                                                                <?php
                                                                                $file_type = getFileType($queja['archivo_adjunto']);
                                                                                $file_size = file_exists("../" . $queja['archivo_adjunto']) ? 
                                                                                    formatFileSize(filesize("../" . $queja['archivo_adjunto'])) : 'N/A';
                                                                                ?>
                                                                                <p class="mb-2">
                                                                                    <i class="bi <?php echo $file_type === 'pdf' ? 'bi-file-pdf' : 'bi-file-image'; ?>"></i>
                                                                                    <?php echo basename($queja['archivo_adjunto']); ?>
                                                                                    (<?php echo $file_size; ?>)
                                                                                </p>
                                                                                <?php if ($file_type === 'image'): ?>
                                                                                    <img src="../<?php echo htmlspecialchars($queja['archivo_adjunto']); ?>" 
                                                                                         class="img-fluid mb-2" 
                                                                                         style="max-height: 200px;" 
                                                                                         alt="Vista previa">
                                                                                <?php endif; ?>
                                                                                <div>
                                                                                    <a href="../<?php echo htmlspecialchars($queja['archivo_adjunto']); ?>" 
                                                                                       class="btn btn-sm btn-primary" 
                                                                                       target="_blank">
                                                                                        <i class="bi bi-eye"></i> Ver
                                                                                    </a>
                                                                                    <a href="../<?php echo htmlspecialchars($queja['archivo_adjunto']); ?>" 
                                                                                       class="btn btn-sm btn-success" 
                                                                                       download>
                                                                                        <i class="bi bi-download"></i> Descargar
                                                                                    </a>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                                <a href="editar_queja.php?id=<?php echo $queja['id']; ?>" class="btn btn-primary">
                                                                    <i class="bi bi-pencil"></i> Editar
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <?php if ($total_paginas > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?pagina=<?php echo $pagina-1; ?>">Anterior</a>
                                    </li>
                                    
                                    <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                                        <li class="page-item <?php echo $pagina == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?pagina=<?php echo $pagina+1; ?>">Siguiente</a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No hay quejas registradas.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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