<?php
// Verificar que el script no sea accedido directamente
if (!defined('IN_ADMIN') && !isset($_SESSION['admin_loggedin'])) {
    header("location: ../login.php");
    exit;
}
?>

<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky pt-3">
        <!-- Header del Sidebar -->
        <div class="text-center mb-4">
            <h5 class="text-white">Sistema PQRS</h5>
            <p class="text-white-50">Panel de Administración</p>
        </div>

        <!-- Menú Principal -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" 
                   href="index.php">
                    <i class="bi bi-house-door me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'quejas.php' ? 'active' : ''; ?>" 
                   href="quejas.php">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Gestión de Quejas
                </a>
            </li>
            <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'eps.php' ? 'active' : ''; ?>" 
                       href="eps.php">
                        <i class="bi bi-building me-2"></i>
                        Gestión de EPS
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ciudades.php' ? 'active' : ''; ?>" 
                       href="ciudades.php">
                        <i class="bi bi-geo-alt me-2"></i>
                        Gestión de Ciudades
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tipos_queja.php' ? 'active' : ''; ?>" 
                       href="tipos_queja.php">
                        <i class="bi bi-tags me-2"></i>
                        Tipos de Queja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>" 
                       href="usuarios.php">
                        <i class="bi bi-people me-2"></i>
                        Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>" 
                       href="reportes.php">
                        <i class="bi bi-graph-up me-2"></i>
                        Reportes
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Divider -->
        <hr class="text-white-50 my-3">

        <!-- Menú de Usuario -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : ''; ?>" 
                   href="perfil.php">
                    <i class="bi bi-person me-2"></i>
                    Mi Perfil
                    <small class="ms-2 text-muted"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? ''); ?></small>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="bi bi-box-arrow-up-right me-2"></i>
                    Ver Sitio
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Cerrar Sesión
                </a>
            </li>
        </ul>

        <!-- Footer del Sidebar -->
        <div class="text-center text-white-50 mt-4 small">
            <p>Versión 1.0</p>
            <p>&copy; <?php echo date('Y'); ?> PQRS</p>
        </div>
    </div>
</nav>

<style>
/* Estilos adicionales para el sidebar */
.sidebar {
    min-height: 100vh;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

.sidebar .nav-link {
    color: #fff;
    font-weight: 500;
    padding: .5rem 1rem;
    opacity: 0.75;
    transition: all 0.3s ease;
}

.sidebar .nav-link:hover {
    opacity: 1;
    background: rgba(255, 255, 255, 0.1);
}

.sidebar .nav-link.active {
    opacity: 1;
    background: rgba(255, 255, 255, 0.1);
    border-left: 3px solid #0d6efd;
}

.sidebar .nav-link i {
    margin-right: 4px;
}

.sidebar hr {
    margin: 1rem 0;
    border-color: rgba(255, 255, 255, 0.1);
}

@media (max-width: 767.98px) {
    .sidebar {
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        z-index: 100;
        padding: 0;
    }
    
    .sidebar.collapse {
        display: none;
    }
    
    .sidebar.show {
        display: block;
    }
}
</style>

<!-- Toggle Button para móviles -->
<button class="navbar-toggler position-fixed d-md-none" type="button" 
        data-bs-toggle="collapse" data-bs-target="#sidebar" 
        style="top: 10px; left: 10px; z-index: 101;">
    <span class="navbar-toggler-icon"></span>
</button>