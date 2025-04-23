<?php
// admin/index.php - Panel de administración
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
$registrosPorPagina = 10;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;

// Filtros
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '';
$estadoFiltro = isset($_GET['estado']) ? $_GET['estado'] : '';

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

// Contar quejas por estado con manejo de errores
$estadosConteo = [
    'Pendiente' => 0,
    'En revisión' => 0,
    'Resuelto' => 0,
    'Rechazado' => 0
];

$sqlEstados = "SELECT estado, COUNT(*) as cantidad FROM quejas GROUP BY estado";
$resultEstados = $conn->query($sqlEstados);
if ($resultEstados) {
    while ($rowEstado = $resultEstados->fetch_assoc()) {
        if (isset($estadosConteo[$rowEstado['estado']])) {
            $estadosConteo[$rowEstado['estado']] = $rowEstado['cantidad'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Sistema de Quejas</title>
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
                            <a class="nav-link active" href="index.php">
                                <i class="bi bi-house-door me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="quejas.php">
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
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="../index.php" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="bi bi-eye"></i> Ver Sitio
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Resumen -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pendientes</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $estadosConteo['Pendiente']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-clock fs-2 text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            En Revisión</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $estadosConteo['En revisión']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-search fs-2 text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Resueltas</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $estadosConteo['Resuelto']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-check-circle fs-2 text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Rechazadas</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $estadosConteo['Rechazado']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-x-circle fs-2 text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buscador y filtros -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Filtrar Quejas</h5>
                    </div>
                    <div class="card-body">
                        <form action="index.php" method="get" class="row g-3">
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="filtro" placeholder="Buscar por nombre, documento o descripción" value="<?php echo htmlspecialchars($filtro); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="estado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="Pendiente" <?php echo $estadoFiltro === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="En revisión" <?php echo $estadoFiltro === 'En revisión' ? 'selected' : ''; ?>>En revisión</option>
                                    <option value="Resuelto" <?php echo $estadoFiltro === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                                    <option value="Rechazado" <?php echo $estadoFiltro === 'Rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-repeat"></i> Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de quejas recientes -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Quejas Recientes</h5>
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
                                                <a class="page-link" href="?pagina=1<?php echo (!empty($filtro) ? '&filtro=' . urlencode($filtro) : '') . (!empty($estadoFiltro) ? '&estado=' . urlencode($estadoFiltro) : ''); ?>">
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
                                                <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo (!empty($filtro) ? '&filtro=' . urlencode($filtro) : '') . (!empty($estadoFiltro) ? '&estado=' . urlencode($estadoFiltro) : ''); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($paginaActual < $totalPaginas): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?pagina=<?php echo $totalPaginas; ?><?php echo (!empty($filtro) ? '&filtro=' . urlencode($filtro) : '') . (!empty($estadoFiltro) ? '&estado=' . urlencode($estadoFiltro) : ''); ?>">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../js/admin-scripts.js"></script>
</body>
</html>