<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Asegúrate de tener PHPMailer instalado vía Composer

class MailHandler {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        
        // Configuración del servidor
        $this->mailer->isSMTP();
        $this->mailer->Host = MAIL_HOST;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = MAIL_USERNAME;
        $this->mailer->Password = MAIL_PASSWORD;
        $this->mailer->SMTPSecure = MAIL_ENCRYPTION;
        $this->mailer->Port = MAIL_PORT;
        $this->mailer->CharSet = 'UTF-8';
        
        // Configuración del remitente
        $this->mailer->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
    }

    public function enviarNotificacionResolucion($destinatario, $nombreDestinatario, $numeroQueja, $respuesta, $archivoRespuesta = null) {
        try {
            $this->mailer->addAddress($destinatario, $nombreDestinatario);
            
            // Asunto del correo
            $this->mailer->Subject = "Su queja #$numeroQueja ha sido resuelta";
            
            // Cuerpo del correo en HTML
            $cuerpoHTML = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { padding: 20px; }
                    .header { color: #2c3e50; margin-bottom: 20px; }
                    .content { line-height: 1.6; }
                    .footer { margin-top: 30px; font-size: 12px; color: #7f8c8d; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2 class='header'>Respuesta a su Queja</h2>
                    <div class='content'>
                        <p>Estimado(a) $nombreDestinatario,</p>
                        <p>Nos complace informarle que su queja con número #$numeroQueja ha sido resuelta.</p>
                        <p><strong>Respuesta:</strong></p>
                        <p>$respuesta</p>
                        " . ($archivoRespuesta ? "<p>Se adjunta documento con información adicional.</p>" : "") . "
                    </div>
                    <div class='footer'>
                        <p>Este es un mensaje automático, por favor no responda a este correo.</p>
                        <p>Sistema de Quejas y Reclamos</p>
                    </div>
                </div>
            </body>
            </html>";
            
            $this->mailer->isHTML(true);
            $this->mailer->Body = $cuerpoHTML;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $respuesta));
            
            // Adjuntar archivo si existe
            if ($archivoRespuesta && file_exists("../" . $archivoRespuesta)) {
                $this->mailer->addAttachment("../" . $archivoRespuesta);
            }
            
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Error al enviar correo: " . $e->getMessage());
            throw new Exception("No se pudo enviar el correo de notificación: " . $e->getMessage());
        }
    }
}
?>