<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    http_response_code(403);
    exit('Acceso no autorizado');
}

require_once "../config/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['queja_id'])) {
    $queja_id = (int)$_POST['queja_id'];
    $email_enviado = (int)$_POST['email_enviado'];

    try {
        $sql = "UPDATE quejas SET email_enviado = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $email_enviado, $queja_id);
        
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Error al actualizar el estado del email");
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
}