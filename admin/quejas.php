<?php
/**
 * Gestión de quejas - Sistema de Quejas
 * Última modificación: 2025-04-23 05:03:50 UTC
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

try {
    // Parámetros de paginación
    $registrosPorPagina = 15;
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

    // Consulta principal con JOINs
    $sql = "SELECT q.*, c.nombre as ciudad, e.nombre as eps, t.nombre as tipo_queja
            FROM quejas q
            LEFT JOIN ciudades c ON q.ciudad_id = c.id
            LEFT JOIN eps e ON q.eps_id = e.id
            LEFT JOIN tipos_queja t ON q.tipo_queja_id = t.id
            $whereSQL
            ORDER BY q.fecha_creacion DESC
            LIMIT ?, ?";

    // Preparar y ejecutar consulta principal
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }

    // Agregar parámetros de paginación
    $params[] = $offset;
    $params[] = $registrosPorPagina;
    $types .= "ii";
    
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
    }
    $result = $stmt->get_result();

    // Consulta para el total de registros
    $sqlCount = "SELECT COUNT(*) as total FROM quejas q $whereSQL";
    $stmtCount = $conn->prepare($sqlCount);
    
    if (!$stmtCount) {
        throw new Exception("Error en la consulta de conteo: " . $conn->error);
    }

    // Quitar parámetros de paginación para el conteo
    if (!empty($params)) {
        array_pop($params); // remove limit
        array_pop($params); // remove offset
        $typesCount = substr($types, 0, -2);
        if (!empty($params)) {
            $stmtCount->bind_param($typesCount, ...$params);
        }
    }
    
    $stmtCount->execute();
    $totalRegistros = $stmtCount->get_result()->fetch_assoc()['total'];
    $totalPaginas = ceil($totalRegistros / $registrosPorPagina);

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
            -webkit-box-orient: vertical;
            overflow: hidden;
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
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnExportar">
                                <i class="bi bi-file-earmark-excel"></i> Exportar
                            </button>
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

                <!-- Tabla de quejas -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Lista de Quejas</h5>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary me-2"><?php echo number_format($totalRegistros); ?> registros</span>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnExportar">
                                    <i class="bi bi-file-earmark-excel"></i> Exportar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="quejasTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">ID</th>
                                            <th scope="col">Paciente</th>
                                            <th scope="col">Ciudad / EPS</th>
                                            <th scope="col">Tipo</th>
                                            <th scope="col">Fecha</th>
                                            <th scope="col">Estado</th>
                                            <th scope="col">Archivo</th>
                                            <th scope="col">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()): 
                                                // Determinar la clase del badge según el estado
                                                $estado = strtolower($row['estado']);
                                                $badgeClass = 'bg-primary'; // valor por defecto
                                                switch ($estado) {
                                                    case 'pendiente':
                                                        $badgeClass = 'bg-warning';
                                                        break;
                                                    case 'en_proceso':
                                                        $badgeClass = 'bg-info';
                                                        break;
                                                    case 'resuelto':
                                                        $badgeClass = 'bg-success';
                                                        break;
                                                    case 'cerrado':
                                                        $badgeClass = 'bg-secondary';
                                                        break;
                                                }
                                            ?>
                                                <tr>
                                                    <td><?php echo $row['id']; ?></td>
                                                    <td>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($row['nombre_paciente']); ?></strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="bi bi-person-badge"></i> 
                                                                <?php echo htmlspecialchars($row['documento_identidad']); ?>
                                                            </small>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="bi bi-envelope"></i> 
                                                                <?php echo htmlspecialchars($row['email']); ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <i class="bi bi-geo-alt text-primary"></i> 
                                                            <?php echo htmlspecialchars($row['ciudad']); ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="bi bi-building"></i> 
                                                                <?php echo htmlspecialchars($row['eps']); ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <?php echo htmlspecialchars($row['tipo_queja']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <i class="bi bi-calendar-event"></i>
                                                            <?php echo date('d/m/Y', strtotime($row['fecha_creacion'])); ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="bi bi-clock"></i>
                                                                <?php echo date('H:i', strtotime($row['fecha_creacion'])); ?>
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $badgeClass; ?>">
                                                            <i class="bi bi-circle-fill me-1"></i>
                                                            <?php echo ucfirst($row['estado']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($row['archivo_adjunto'])): ?>
                                                            <?php
                                                            $extension = strtolower(pathinfo($row['archivo_adjunto'], PATHINFO_EXTENSION));
                                                            $icon_class = $extension === 'pdf' ? 'bi-file-pdf' : 'bi-file-image';
                                                            ?>
                                                            <a href="../<?php echo htmlspecialchars($row['archivo_adjunto']); ?>" 
                                                               class="btn btn-sm btn-outline-primary" 
                                                               target="_blank"
                                                               data-bs-toggle="tooltip" 
                                                               title="Ver archivo">
                                                                <i class="bi <?php echo $icon_class; ?>"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted">
                                                                <i class="bi bi-file-earmark-x"></i>
                                                                Sin archivo
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="ver_queja.php?id=<?php echo $row['id']; ?>" 
                                                               class="btn btn-sm btn-info" 
                                                               data-bs-toggle="tooltip" 
                                                               title="Ver detalles">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="editar_queja.php?id=<?php echo $row['id']; ?>" 
                                                               class="btn btn-sm btn-primary"
                                                               data-bs-toggle="tooltip" 
                                                               title="Editar">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <?php if (isAdmin()): ?>
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-danger"
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#deleteModal"
                                                                        data-id="<?php echo $row['id']; ?>"
                                                                        data-paciente="<?php echo htmlspecialchars($row['nombre_paciente']); ?>"
                                                                        title="Eliminar">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
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
                                                        <p>No se encontraron quejas con los criterios seleccionados.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <?php if ($totalPaginas > 1): ?>
                                <nav aria-label="Navegación de páginas" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo $paginaActual <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?pagina=1<?php echo !empty($_GET) ? '&' . http_build_query(array_filter([
                                                'filtro' => $filtro,
                                                'estado' => $estadoFiltro,
                                                'eps_id' => $epsFiltro,
                                                'tipo_queja_id' => $tipoQuejaFiltro
                                            ])) : ''; ?>">
                                                <i class="bi bi-chevron-double-left"></i>
                                            </a>
                                        </li>
                                        
                                        <?php
                                        $startPage = max(1, $paginaActual - 2);
                                        $endPage = min($totalPaginas, $paginaActual + 2);
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++):
                                            $queryParams = array_filter([
                                                'pagina' => $i,
                                                'filtro' => $filtro,
                                                'estado' => $estadoFiltro,
                                                'eps_id' => $epsFiltro,
                                                'tipo_queja_id' => $tipoQuejaFiltro
                                            ]);
                                        ?>
                                            <li class="page-item <?php echo ($i == $paginaActual) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query($queryParams); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?php echo $paginaActual >= $totalPaginas ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?pagina=<?php echo $totalPaginas; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_filter([
                                                'filtro' => $filtro,
                                                'estado' => $estadoFiltro,
                                                'eps_id' => $epsFiltro,
                                                'tipo_queja_id' => $tipoQuejaFiltro
                                            ])) : ''; ?>">
                                                <i class="bi bi-chevron-double-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
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
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Manejar modal de eliminación
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const paciente = button.getAttribute('data-paciente');
                
                document.getElementById('deleteId').value = id;
                document.getElementById('pacienteName').textContent = paciente;
            });
        }

        // Manejar formulario de eliminación
        const deleteForm = document.getElementById('deleteForm');
        if (deleteForm) {
            deleteForm.addEventListener('submit', function(e) {
                const btnConfirmDelete = document.getElementById('btnConfirmDelete');
                btnConfirmDelete.disabled = true;
                btnConfirmDelete.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Eliminando...';
            });
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

        // Activar búsqueda al presionar Enter en el campo de búsqueda
        const inputFiltro = document.querySelector('input[name="filtro"]');
        if (inputFiltro) {
            inputFiltro.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('filtrosForm').submit();
                }
            });
        }

        // Mostrar spinner al enviar formulario
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
                }
            });
        });
    });
    </script>
</body>
</html>