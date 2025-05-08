<?php
/**
 * Procesador de Email de Queja - Sistema de Quejas
 * @author crisgacovi
 * @date 2025-05-06
 */

session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    die(json_encode(['error' => 'No autorizado']));
}

require_once "includes/email_functions.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $queja_id = isset($_POST['queja_id']) ? (int)$_POST['queja_id'] : 0;
        $mensaje_adicional = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';
        
        if ($queja_id <= 0) {
            throw new Exception("ID de queja inválido.");
        }
        
        if (enviarEmailRespuestaQueja($queja_id, $mensaje_adicional)) {
            $response = [
                'success' => true,
                'message' => 'Email enviado exitosamente.'
            ];
        }
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>