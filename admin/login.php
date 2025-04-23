<?php 
// admin/login.php 
session_start(); 

// Si ya está logueado, redirigir al panel 
if(isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) { 
    header("location: index.php");
    exit; 
} 

// Incluir archivo de configuración
require_once "../config.php";

$error = ""; 

// Procesar formulario 
if($_SERVER["REQUEST_METHOD"] == "POST") { 
    $username = trim($_POST['username']); 
    $password = trim($_POST['password']); 
    
    // Consultar en la base de datos (método seguro)
    $sql = "SELECT id, username, password, nombre, email, role FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        // Verificar contraseña (asumiendo que está hasheada con password_hash)
        if (password_verify($password, $usuario['password'])) {
            // Login exitoso
            $_SESSION['admin_loggedin'] = true; 
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_id'] = $usuario['id'];
            $_SESSION['admin_nombre'] = $usuario['nombre'];
            $_SESSION['admin_email'] = $usuario['email'];
            $_SESSION['admin_role'] = $usuario['role'];
            
            header("location: index.php"); 
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos";
        }
    } else {
        // Fallback para credenciales básicas (solo para desarrollo)
        $admin_user = "admin";
        $admin_pass = "admin123";
        
        if ($username === $admin_user && $password === $admin_pass) { 
            // Login exitoso con credenciales fallback
            $_SESSION['admin_loggedin'] = true; 
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_id'] = 1; // ID predeterminado para admin
            $_SESSION['admin_nombre'] = "Administrador";
            $_SESSION['admin_email'] = "admin@example.com";
            $_SESSION['admin_role'] = "admin";
            
            header("location: index.php"); 
            exit;
        } else { 
            $error = "Usuario o contraseña incorrectos"; 
        }
    }
} 
?>

<!DOCTYPE html> 
<html lang="es"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Administración - Login</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/admin-styles.css">
</head> 
<body class="bg-light"> 
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-lg-5 col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="my-0">HealthComplaints</h4>
                        <p class="mb-0">Panel de Administración</p>
                    </div>
                    <div class="card-body p-4">
                        <h5 class="card-title text-center mb-4">Iniciar Sesión</h5>

                        <?php if (!empty($error)): ?> 
                        <div class="alert alert-danger"> 
                            <?php echo $error; ?>
                        </div> 
                        <?php endif; ?> 
                        
                        <form action="login.php" method="post"> 
                            <div class="mb-3"> 
                                <label for="username" class="form-label">Usuario</label> 
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required> 
                                </div>
                            </div> 
                            
                            <div class="mb-4"> 
                                <label for="password" class="form-label">Contraseña</label> 
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required> 
                                </div>
                            </div> 
                            
                            <div class="d-grid gap-2"> 
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
                                </button> 
                            </div> 
                        </form> 
                        
                        <div class="text-center mt-3">
                            <a href="../index.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left me-1"></i>Volver al sitio principal
                            </a>
                        </div>
                    </div>
                    <div class="card-footer text-center text-muted">
                        &copy; 2025 Sistema de Quejas y Reclamos en Servicios de Salud
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body> 
</html>
