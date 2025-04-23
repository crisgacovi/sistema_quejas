<?php
// procesar_queja.php - Procesa el envío del formulario

// Incluir archivo de configuración
require_once "config.php";

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger y validar los datos del formulario
    $nombre = trim($_POST['nombre']);
    $documento = trim($_POST['documento']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $ciudad_id = (int)$_POST['ciudad'];
    $eps_id = (int)$_POST['eps'];
    $tipo_queja_id = (int)$_POST['tipo_queja'];
    $descripcion = trim($_POST['descripcion']);
    
    // Validación básica
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }
    
    if (empty($documento)) {
        $errores[] = "El documento de identidad es obligatorio";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico es inválido";
    }
    
    if ($ciudad_id <= 0) {
        $errores[] = "Debe seleccionar una ciudad";
    }
    
    if ($eps_id <= 0) {
        $errores[] = "Debe seleccionar una EPS";
    }
    
    if ($tipo_queja_id <= 0) {
        $errores[] = "Debe seleccionar un motivo de queja";
    }
    
    if (empty($descripcion)) {
        $errores[] = "La descripción es obligatoria";
    }
    
    // Si no hay errores, guardar en la base de datos
    if (empty($errores)) {
        // Preparar la consulta SQL
        $sql = "INSERT INTO quejas (nombre_paciente, documento_identidad, email, telefono, 
                ciudad_id, eps_id, tipo_queja_id, descripcion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssssiiis", $nombre, $documento, $email, $telefono, 
                            $ciudad_id, $eps_id, $tipo_queja_id, $descripcion);
            
            if ($stmt->execute()) {
                // Redirigir a página de éxito
                header("location: confirmacion.php?id=" . $conn->insert_id);
                exit();
            } else {
                $errores[] = "Error al registrar la queja: " . $stmt->error;
            }
            
            $stmt->close();
        } else {
            $errores[] = "Error en la preparación de la consulta: " . $conn->error;
        }
    }
    
    // Si hay errores, volver al formulario y mostrarlos
    if (!empty($errores)) {
        session_start();
        $_SESSION['errores'] = $errores;
        $_SESSION['form_data'] = $_POST; // Conservar los datos del formulario
        header("location: index.php");
        exit();
    }
}

// Si se accede directamente a este script, redirigir al formulario
header("location: index.php");
exit();
?>
