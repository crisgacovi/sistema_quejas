<?php 
// admin/login.php 
session_start(); 

// Si ya está logueado, redirigir al panel 
if(isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) { 
    header("location: index.php");
    exit; 
} 
// Credenciales básicas (en un entorno real deberías usar un sistema más seguro) 
$admin_user = "admin";
$admin_pass = "admin123"; // En producción usar password_hash 
 
$error = ""; 

// Procesar formulario 
if($_SERVER["REQUEST_METHOD"] == "POST") { 
    $username = trim($_POST['username']); 
    $password =trim($_POST['password']); 
    
    if ($username === $admin_user && $password === $admin_pass) { 
        // Login exitoso
        $_SESSION['admin_loggedin'] = true; 
        $_SESSION['admin_username'] = $username; 
        
        header("location: index.php"); 
        exit;
    } else { 
        $error = "Usuario o contraseña incorrectos"; 
    } 
} 
?>

<!DOCTYPE html> 
<html lang="es"> 
<head> 
    <metacharset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Administración - Login</title> 
    <link rel="stylesheet" href="../css/styles.css"> 
</head> 
<body> 
    <div class="container"> 
        <header>
            <h1>Sistema de Quejas y Reclamos</h1> 
            <h2>Acceso de Administración</h2> 
        </header> 
        
        <main> 
            <div class="form-section"> 
                <h3>Iniciar Sesión</h3>

                <?php if (!empty($error)): ?> 
                <div class="error-message"> 
                    <?php echo $error; ?>
                </div> 
                <?php endif; ?> 
                
                <form action="login.php" method="post"> 
                    <div class="form-group"> 
                        <label for="username">Usuario:</label> 
                        <input type="text" id="username" name="username" required> 
                    </div> 
                    
                    <div class="form-group"> 
                        <label for="password">Contraseña:</label> 
                        <input type="password" id="password"name="password" required> 
                    </div> 
                    
                    <div class="form-actions"> 
                        <button type="submit" class="btn-submit">Ingresar</button> 
                    </div> 
                </form> 
            </div> 
        </main> 
        
        <footer> <p>&copy; 2025 Sistema de Quejas y Reclamos en Servicios de Salud</p> 
    
        </footer> 
    </div> 
</body> 
</html> 


