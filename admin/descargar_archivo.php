<?php
/**
 * Descargar Archivo Adjunto - Sistema de Quejas
 * Última modificación: 2025-04-26 03:42:05 UTC
 * @author crisgacovi
 */

session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("location: login.php");
    exit;
}

require_once "../config.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("location: quejas.php");
    exit;
}

try {
    // Obtener información del archivo
    $stmt = $conn->prepare("SELECT archivo_adjunto FROM quejas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Queja no encontrada");
    }
    
    $queja = $result->fetch_assoc();
    
    if (empty($queja['archivo_adjunto'])) {
        throw new Exception("No hay archivo adjunto");
    }
    
    $archivo_path = "../uploads/" . $queja['archivo_adjunto'];
    
    if (!file_exists($archivo_path)) {
        throw new Exception("El archivo no existe");
    }
    
    // Obtener información del archivo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $archivo_path);
    finfo_close($finfo);
    
    // Preparar la descarga
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . basename($queja['archivo_adjunto']) . '"');
    header('Content-Length: ' . filesize($archivo_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    
    // Leer y enviar el archivo
    readfile($archivo_path);
    exit;
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("location: quejas.php");
    exit;
}
?>