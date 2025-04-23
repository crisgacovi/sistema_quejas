<?php 
// confirmacion.php 
require_once "config.php"; 

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0; 
$mensaje = "Su queja ha sido registrada correctamente."; 
$queja = null;

if ($id > 0) { 
    // Obtener detalles de la queja para mostrar un resumen 
    $sql = "SELECT q.id, q.nombre_paciente, q.email, q.fecha_creacion, 
            c.nombre AS ciudad, e.nombre AS eps, t.nombre AS tipo_queja 
            FROM quejas q
            JOIN ciudades c ON q.ciudad_id = c.id
            JOIN eps e ON q.eps_id = e.id
            JOIN tipos_queja t ON q.tipo_queja_id = t.id
            WHERE q.id = ?"; 
    
    $stmt = $conn->prepare($sql); 
    $stmt->bind_param("i", $id); 
    $stmt->execute(); 
    $result = $stmt->get_result(); 
    
    if ($result->num_rows == 0) { 
        $mensaje = "No se encontró la queja con el ID proporcionado."; 
    } else {
        $queja = $result->fetch_assoc();
    }
} 
?> 

<!DOCTYPE html> 
<html lang="es"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Confirmación - Sistema de Quejas</title>
    <link rel="stylesheet" href="css/styles.css"> 
</head> 
<body> 
    <div class="container"> 
        <header> 
            <h1>Sistema de Quejas y Reclamos</h1> 
            <h2>Confirmación</h2> 
        </header> 
        
        <main> 
            <div class="form-section"> 
                <div class="success-message"> 
                    <h3>¡Gracias por su reporte!</h3> 
                    <p><?php echo $mensaje; ?></p>
                    
                    <?php if ($queja): ?>
                    <div class="queja-resumen">
                        <p><strong>Número de queja:</strong> <?php echo $id; ?></p>
                        <p><strong>Fecha de registro:</strong> <?php echo date('d/m/Y H:i', strtotime($queja['fecha_creacion'])); ?></p>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($queja['nombre_paciente']); ?></p>
                        <p><strong>Correo electrónico:</strong> <?php echo htmlspecialchars($queja['email']); ?></p>
                        <p><strong>Ciudad:</strong> <?php echo htmlspecialchars($queja['ciudad']); ?></p>
                        <p><strong>EPS:</strong> <?php echo htmlspecialchars($queja['eps']); ?></p>
                        <p><strong>Motivo:</strong> <?php echo htmlspecialchars($queja['tipo_queja']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <p>Nos pondremos en contacto con usted pronto.</p>
                </div> 
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="index.php" class="btn-submit" style="text-decoration: none; padding: 12px 24px; display: inline-block;">Volver al inicio</a> 
                </div> 
            </div> 
        </main> 
        
        <footer> 
            <p>&copy; 2025 Sistema de Quejas y Reclamos en Servicios de Salud</p>
        </footer> 
    </div> 
</body> 
</html>
