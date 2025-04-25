<?php
/**
 * Gestión de quejas - Sistema de Quejas
 * Última modificación: 2025-04-23 05:16:33 UTC
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


try {
    // Parámetros de paginación
    $registrosPorPagina = 3;
    $paginaActual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $offset = ($paginaActual - 1) * $registrosPorPagina;

    // Filtros
    $filtro = trim($_GET['filtro'] ?? '');
    $estadoFiltro = trim($_GET['estado'] ?? '');
    $epsFiltro = (int)($_GET['eps_id'] ?? 0);
    $tipoQuejaFiltro = (int)($_GET['tipo_queja_id'] ?? 0);

    // Construir consulta SQL con filtros
    $whereClauses = [];
    $params = [];
    $types = "";

    if (!empty($filtro)) {
        $whereClauses[] = "(q.nombre_paciente LIKE ? OR q.documento_identidad LIKE ? OR q.descripcion LIKE ?)";
        $filtroParam = "%$filtro%";
        array_push($params, $filtroParam, $filtroParam, $filtroParam);
        $types .= "sss";
    }

    if (!empty($estadoFiltro)) {
        $whereClauses[] = "q.estado = ?";
        $params[] = $estadoFiltro;
        $types .= "s";
    }

    if ($epsFiltro > 0) {
        $whereClauses[] = "q.eps_id = ?";
        $params[] = $epsFiltro;
        $types .= "i";
    }

    if ($tipoQuejaFiltro > 0) {
        $whereClauses[] = "q.tipo_queja_id = ?";
        $params[] = $tipoQuejaFiltro;
        $types .= "i";
    }

    $whereSQL = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

    // Configuración de paginación
$registros_por_pagina = 5;
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

    // Obtener listas para filtros
    $eps_list = $conn->query("SELECT id, nombre FROM eps ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
    $tipos_queja_list = $conn->query("SELECT id, nombre FROM tipos_queja ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

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
    <style>
        .table td { 
            vertical-align: middle; 
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .badge { 
            font-size: 0.9em;
            padding: 0.4em 0.6em;
        }
        .btn-group .btn { 
            margin-right: 2px; 
        }
        .filters .form-control, 
        .filters .form-select {
            border-radius: 0.375rem;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,.075);
        }
        .modal-confirm .modal-content {
            padding: 20px;
            border-radius: 5px;
            border: none;
        }
        .btn-outline-danger:hover {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .loading {
            position: relative;
            pointer-events: none;
        }
        .loading:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            z-index: 1;
        }
        .tooltip {
            font-size: 0.85rem;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,.125);
        }
        .display-4 {
            font-size: 2.5rem;
            font-weight: 300;
        }
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .btn-delete:not(:disabled):not(.disabled):hover {
            background-color: #dc3545;
            color: white;
        }
        .btn-delete:not(:disabled):not(.disabled):active {
            background-color: #bd2130;
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
                    <h1 class="h2">Gestión de Quejas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="../index.php" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="bi bi-eye"></i> Ver Sitio
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Filtrar Quejas</h5>
                    </div>
                    <div class="card-body">
                        <form action="quejas.php" method="get" class="row g-3" id="filtrosForm">
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" name="filtro" 
                                           placeholder="Buscar..." 
                                           value="<?php echo htmlspecialchars($filtro); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select name="estado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="pendiente" <?php echo $estadoFiltro === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="en_proceso" <?php echo $estadoFiltro === 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                                    <option value="resuelto" <?php echo $estadoFiltro === 'resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                                    <option value="cerrado" <?php echo $estadoFiltro === 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="eps_id" class="form-select">
                                    <option value="">Todas las EPS</option>
                                    <?php foreach ($eps_list as $eps): ?>
                                        <option value="<?php echo $eps['id']; ?>" 
                                                <?php echo $epsFiltro == $eps['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($eps['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="tipo_queja_id" class="form-select">
                                    <option value="">Todos los tipos</option>
                                    <?php foreach ($tipos_queja_list as $tipo): ?>
                                        <option value="<?php echo $tipo['id']; ?>" 
                                                <?php echo $tipoQuejaFiltro == $tipo['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tipo['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                                <a href="quejas.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Quejas -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Lista de Quejas</h5>
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

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="bi bi-exclamation-triangle-fill"></i> Confirmar eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar la queja de <strong id="pacienteName"></strong>?</p>
                    <p class="text-danger mb-0"><small><i class="bi bi-exclamation-circle"></i> Esta acción no se puede deshacer.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Cancelar
                    </button>
                    <form id="deleteForm" action="eliminar_queja.php" method="POST" class="d-inline">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="submit" class="btn btn-danger" id="btnConfirmDelete">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltips = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        // Manejar modal de eliminación
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            const modal = new bootstrap.Modal(deleteModal);
            
            // Manejar botones de eliminación
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const paciente = this.getAttribute('data-paciente');
                    
                    document.getElementById('deleteId').value = id;
                    document.getElementById('pacienteName').textContent = paciente;
                    modal.show();
                });
            });

            // Manejar formulario de eliminación
            const deleteForm = document.getElementById('deleteForm');
            if (deleteForm) {
                deleteForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const btnConfirmDelete = document.getElementById('btnConfirmDelete');
                    const formData = new FormData(this);

                    btnConfirmDelete.disabled = true;
                    btnConfirmDelete.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Eliminando...';

                    fetch('eliminar_queja.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message || 'Error al eliminar la queja');
                            btnConfirmDelete.disabled = false;
                            btnConfirmDelete.innerHTML = '<i class="bi bi-trash"></i> Eliminar';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al procesar la solicitud');
                        btnConfirmDelete.disabled = false;
                        btnConfirmDelete.innerHTML = '<i class="bi bi-trash"></i> Eliminar';
                    });
                });
            }
        }

        // Exportar a Excel
        const btnExportar = document.getElementById('btnExportar');
        if (btnExportar) {
            btnExportar.addEventListener('click', function() {
                const table = document.getElementById('quejasTable');
                const wb = XLSX.utils.table_to_book(table, {
                    sheet: "Quejas",
                    raw: true
                });
                const fileName = 'quejas_' + new Date().toISOString().split('T')[0] + '.xlsx';
                XLSX.writeFile(wb, fileName);
            });
        }

        // Activar búsqueda al presionar Enter
        const inputFiltro = document.querySelector('input[name="filtro"]');
        if (inputFiltro) {
            inputFiltro.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('filtrosForm').submit();
                }
            });
        }

        // Mostrar spinner en formularios
        document.querySelectorAll('form').forEach(form => {
            if (form.id !== 'deleteForm') {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
                    }
                });
            }
        });
    });
    </script>
</body>
</html>