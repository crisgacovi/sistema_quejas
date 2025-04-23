<?php
session_start();

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || $_SESSION['admin_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once "../config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $id = (int)$_POST['id'];
        
        // Iniciar transacción
        $conn->begin_transaction();

        // Eliminar registros relacionados primero
        $stmt = $conn->prepare("DELETE FROM seguimientos WHERE queja_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Eliminar la queja
        $stmt = $conn->prepare("DELETE FROM quejas WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception("No se encontró la queja especificada.");
        }

        // Confirmar transacción
        $conn->commit();

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        // Revertir cambios en caso de error
        $conn->rollback();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
}