<?php
/**
 * Funciones de Email - Sistema de Quejas
 * @author crisgacovi
 * @date 2025-05-06
 */

require_once __DIR__ . "/../../config/email_config.php";

// Incluir PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/../../vendor/autoload.php';

function enviarEmailRespuestaQueja($queja_id, $mensaje_adicional = '') {
    require_once __DIR__ . "/../../config/config.php";
    
    try {
        // Obtener los datos de la queja
        $sql = "SELECT q.*, c.nombre AS ciudad_nombre, e.nombre AS eps_nombre, 
                t.nombre AS tipo_queja_nombre
                FROM quejas q
                JOIN ciudades c ON q.ciudad_id = c.id
                JOIN eps e ON q.eps_id = e.id
                JOIN tipos_queja t ON q.tipo_queja_id = t.id
                WHERE q.id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $queja_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Queja no encontrada.");
        }
        
        $queja = $result->fetch_assoc();
        
        // Verificar si hay email y respuesta
        if (empty($queja['email'])) {
            throw new Exception("La queja no tiene email asociado.");
        }
        
        if (empty($queja['respuesta'])) {
            throw new Exception("La queja no tiene una respuesta registrada.");
        }

        // Crear instancia de PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            // Remitente
            $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
            $mail->addReplyTo(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);

            // Destinatario
            $mail->addAddress($queja['email'], $queja['nombre_paciente']);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = "Respuesta a su Queja #" . $queja_id;

            // Construir el cuerpo del email
            $cuerpo_email = '
            <html>
            <head>
                <title>Respuesta a su Queja</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { padding: 20px; }
                    .header { background-color: #f8f9fa; padding: 10px; }
                    .content { padding: 15px 0; }
                    .footer { color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>Respuesta a su Queja #' . $queja_id . '</h2>
                    </div>
                    <div class="content">
                        <p>Estimado/a ' . htmlspecialchars($queja['nombre_paciente']) . ',</p>
                        <p>Su queja ha sido atendida. A continuación, encontrará la respuesta:</p>
                        <hr>
                        <p><strong>Respuesta:</strong></p>
                        ' . nl2br(htmlspecialchars($queja['respuesta'])) . '
                        ' . (!empty($mensaje_adicional) ? '<p><strong>Mensaje adicional:</strong><br>' . nl2br(htmlspecialchars($mensaje_adicional)) . '</p>' : '') . '
                        <hr>
                        <p><strong>Detalles de la queja:</strong></p>
                        <ul>
                            <li>Fecha de creación: ' . date('Y-m-d', strtotime($queja['fecha_creacion'])) . '</li>
                            <li>Ciudad: ' . htmlspecialchars($queja['ciudad_nombre']) . '</li>
                            <li>EPS: ' . htmlspecialchars($queja['eps_nombre']) . '</li>
                            <li>Tipo de queja: ' . htmlspecialchars($queja['tipo_queja_nombre']) . '</li>
                        </ul>
                    </div>
                    <div class="footer">
                        <p>Este es un mensaje automático, por favor no responda a este correo.</p>
                    </div>
                </div>
            </body>
            </html>';

            $mail->Body = $cuerpo_email;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $cuerpo_email));

            // Adjuntar archivo de respuesta si existe
            if (!empty($queja['archivo_respuesta'])) {
                $archivo_path = __DIR__ . '/../../' . $queja['archivo_respuesta'];
                if (file_exists($archivo_path)) {
                    $mail->addAttachment($archivo_path);
                }
            }

            // Enviar el email
            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("Error al enviar email: " . $mail->ErrorInfo);
            throw new Exception("Error al enviar el email: " . $mail->ErrorInfo);
        }

    } catch (Exception $e) {
        error_log("Error en enviarEmailRespuestaQueja: " . $e->getMessage());
        throw $e;
    }
}
?>