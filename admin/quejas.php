<?php
// quejas.php - Gestión de quejas
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

// Parámetros de paginación
$registrosPorPagina = 15;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;

// Filtros
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '';
$estadoFiltro = isset($_GET['estado']) ? $_GET['estado'] : '';
$epsFiltro = isset($_GET['eps_id']) ? (int)$_GET['eps_id'] : 0;
$tipoQuejaFiltro = isset($_GET['tipo_queja_id']) ? (int)$_GET['tipo_queja_id'] : 0;

// Construir consulta SQL con filtros
$whereClauses = [];
$params = [];
$types = "";

if (!empty($filtro)) {
    $whereClauses[] = "(q.nombre_paciente LIKE ? OR q.documento_identidad LIKE ? OR q.descripcion LIKE ?)";
    $filtroParam = "%$filtro%";
    $params[] = $filtroParam;
    $params[] = $filtroParam;
    $params[] = $filtroParam;
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

$whereSQL = "";
if (!empty($whereClauses)) {
    $whereSQL = "WHERE " . implode(" AND ", $whereClauses);
}

// Consulta para obtener quejas con paginación
$sql = "SELECT q.id, q.nombre_paciente, q.documento_identidad, q.email, 
        c.nombre as ciudad, e.nombre as eps, t.nombre as tipo_queja,
        q.descripcion, q.fecha_creacion, q.estado
        FROM quejas q
        JOIN ciudades c ON q.ciudad_id = c.id
        JOIN eps e ON q.eps_id = e.id
        JOIN tipos_queja t ON q.tipo_queja_id = t.id
        $whereSQL
        ORDER BY q.fecha_creacion DESC
        LIMIT ?, ?";

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($sql);
if ($stmt) {
    // Agregar parámetros de paginación
    $params[] = $offset;
    $params[] = $registrosPorPagina;
    $types .= "ii";
    
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Manejar error en la preparación de la consulta
    $error = $conn->error;
    $result = null;
}

// Consulta para contar el total de registros (para la paginación)
$sqlCount = "SELECT COUNT(*) as total FROM quejas q $whereSQL";
$totalRegistros = 0;

$stmtCount = $conn->prepare($sqlCount);
if ($stmtCount) {
    if ($params) {
        // Quitar los dos últimos parámetros (offset y limit)
        array_pop($params);
        array_pop($params);
        $typesCount = substr($types, 0, -2);
        if (!empty($typesCount) && !empty($params)) {
            $stmtCount->bind_param($typesCount, ...$params);
        }
    }
    $stmtCount->execute();
    $resultCount = $stmtCount->get_result();
    if ($rowCount = $resultCount->fetch_assoc()) {
        $totalRegistros = $rowCount['total'];
    }
}

// Calcular total de páginas
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Obtener lista de EPS para el filtro
$eps_list = [];
$sql_eps = "SELECT id, nombre FROM eps ORDER BY nombre";
$result_eps = $conn->query($sql_eps);
if ($result_eps) {
    while ($row = $result_eps->fetch_assoc()) {
        $eps_list[] = $row;
    }
}

// Obtener lista de tipos de queja para el filtro
$tipos_queja_list = [];
$sql_tipos = "SELECT id, nombre FROM tipos_queja ORDER BY nombre";
$result_tipos = $conn->query($sql_tipos);
if ($result_tipos) {
    while ($row = $result_tipos->fetch_assoc()) {
        $tipos_queja_list[] = $row;
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
                            <a class="nav-link active" href="quejas.php">
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
                            <a class="nav-link" href="perfil.php">
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
                    <h1 class="h2">Gestión de Quejas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="../index.php" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="bi bi-eye"></i> Ver Sitio
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Buscador y filtros avanzados -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Filtrar Quejas</h5>
                    </div>
                    <div class="card-body">
                        <form action="quejas.php" method="get" class="row g-3">
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="filtro" placeholder="Buscar por nombre, documento o descripción" value="<?php echo htmlspecialchars($filtro); ?>">
                            </div>
                            <div class="col-md-2">
                                <select name="estado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="Pendiente" <?php echo $estadoFiltro === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="En revisión" <?php echo $estadoFiltro === 'En revisión' ? 'selected' : ''; ?>>En revisión</option>
                                    <option value="Resuelto" <?php echo $estadoFiltro === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                                    <option value="Rechazado" <?php echo $estadoFiltro === 'Rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="eps_id" class="form-select">
                                    <option value="">Todas las EPS</option>
                                    <?php foreach ($eps_list as $eps): ?>
                                        <option value="<?php echo $eps['id']; ?>" <?php echo $epsFiltro == $eps['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($eps['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="tipo_queja_id" class="form-select">
                                    <option value="">Todos los tipos</option>
                                    <?php foreach ($tipos_queja_list as $tipo): ?>
                                        <option value="<?php echo $tipo['id']; ?>" <?php echo $tipoQuejaFiltro == $tipo['id'] ? 'selected' : ''; ?>>
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
                                    <i class="bi bi-arrow-repeat"></i> Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de quejas -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white">
                        <h5 class="mb-0">Lista de Quejas</h5>
                        <span class="badge bg-primary"><?php echo $totalRegistros; ?> registros encontrados</span>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                Error en la consulta: <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Paciente</th>
                                            <th>Ciudad / EPS</th>
                                            <th>Tipo</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $row['id']; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($row['nombre_paciente']); ?></strong><br>
                                                        <small><?php echo htmlspecialchars($row['documento_identidad']); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($row['ciudad']); ?><br>
                                                        <small><?php echo htmlspecialchars($row['eps']); ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['tipo_queja']); ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_creacion'])); ?></td>
                                                    <td>
                                                        <?php 
                                                        $badgeClass = '';
                                                        switch ($row['estado']) {
                                                            case 'Pendiente':
                                                                $badgeClass = 'bg-warning';
                                                                break;
                                                            case 'En revisión':
                                                                $badgeClass = 'bg-primary';
                                                                break;
                                                            case 'Resuelto':
                                                                $badgeClass = 'bg-success';
                                                                break;
                                                            case 'Rechazado':
                                                                $badgeClass = 'bg-danger';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $row['estado']; ?></span>
                                                    </td>
                                                    <td>
                                                        <a href="ver_queja.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="editar_queja.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                                data-id="<?php echo $row['id']; ?>">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No se encontraron quejas con los criterios seleccionados.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginación -->
                            <?php if ($totalPaginas > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($paginaActual > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?pagina=1<?php echo (!empty($filtro) ? '&filtro=' . urlencode($filtro) : '') . (!empty($estadoFiltro) ? '&estado=' . urlencode($estadoFiltro) : '') . ($epsFiltro > 0 ? '&eps_id=' . $epsFiltro : '') . ($tipoQuejaFiltro > 0 ? '&tipo_queja_id=' . $tipoQuejaFiltro : ''); ?>">
                                                    &laquo;
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">&laquo;</span>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // Mostrar 5 páginas alrededor de la página actual
                                        $startPage = max(1, $paginaActual - 2);
                                        $endPage = min($totalPaginas, $paginaActual + 2);
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++):
                                        ?>
                                            <li class="page-item <?php echo ($i == $paginaActual) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo (!empty($filtro) ? '&filtro=' . urlencode($filtro) : '') . (!empty($estadoFiltro) ? '&estado=' . urlencode($estadoFiltro) : '') . ($epsFiltro > 0 ? '&eps_id=' . $epsFiltro : '') . ($tipoQuejaFiltro > 0 ? '&tipo_queja_id=' . $tipoQuejaFiltro : ''); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($paginaActual < $totalPaginas): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?pagina=<?php echo $totalPaginas; ?><?php echo (!empty($filtro) ? '&filtro=' . urlencode($filtro) : '') . (!empty($estadoFiltro) ? '&estado=' . urlencode($estadoFiltro) : '') . ($epsFiltro > 0 ? '&eps_id=' . $epsFiltro : '') . ($tipoQuejaFiltro > 0 ? '&tipo_queja_id=' . $tipoQuejaFiltro : ''); ?>">
                                                    &raquo;
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">&raquo;</span>
                                            </li>
                                        <?php endif; ?>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Está seguro de que desea eliminar esta queja? Esta acción no se puede deshacer.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" action="eliminar_queja.php" method="POST">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para manejar modal de eliminación
        document.addEventListener('DOMContentLoaded', function() {
            const deleteModal = document.getElementById('deleteModal');
            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    const deleteIdInput = document.getElementById('deleteId');
                    deleteIdInput.value = id;
                });
            }
        });
    </script>
</body>
</html>
