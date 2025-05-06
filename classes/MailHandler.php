<?php
/**
 * Clase MailHandler para el envío de correos
 * Última modificación: 2025-05-05 15:10:10 UTC
 * @author crisgacovi
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail_config.php';

class MailHandler 
{
    /**
     * Instancia de PHPMailer
     * @var PHPMailer
     */
    protected $mailer;

    /**
     * Constructor de la clase
     * @throws PHPMailerException
     */
    public function __construct() 
    {
        try {
            $this->mailer = new PHPMailer(true);
            
            // Configuración del servidor
            $this->mailer->SMTPDebug = SMTP::DEBUG_OFF;
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
        } catch (PHPMailerException $e) {
            error_log("Error al inicializar PHPMailer: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envía una notificación cuando una queja ha sido resuelta
     * 
     * @param string $destinatario Correo del destinatario
     * @param string $nombreDestinatario Nombre del destinatario
     * @param int $numeroQueja Número de la queja
     * @param string $respuesta Respuesta a la queja
     * @param string|null $archivoRespuesta Ruta al archivo adjunto (opcional)
     * @return bool
     * @throws PHPMailerException
     */
    public function enviarNotificacionResolucion($destinatario, $nombreDestinatario, $numeroQueja, $respuesta, $archivoRespuesta = null) 
    {
        try {
            // Limpiar destinatarios y adjuntos anteriores
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Agregar destinatario
            $this->mailer->addAddress($destinatario, $nombreDestinatario);
            
            // Asunto del correo
            $this->mailer->Subject = "Su queja #$numeroQueja ha sido resuelta";
            
            // Cuerpo del correo en HTML
            $cuerpoHTML = $this->generarCuerpoHTML($nombreDestinatario, $numeroQueja, $respuesta, $archivoRespuesta);
            
            $this->mailer->isHTML(true);
            $this->mailer->Body = $cuerpoHTML;
            $this->mailer->AltBody = $this->generarTextoPlano($respuesta);
            
            // Adjuntar archivo si existe
            if ($archivoRespuesta && file_exists(__DIR__ . "/../" . $archivoRespuesta)) {
                $this->mailer->addAttachment(__DIR__ . "/../" . $archivoRespuesta);
            }
            
            return $this->mailer->send();
        } catch (PHPMailerException $e) {
            error_log("Error al enviar correo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Genera el cuerpo HTML del correo
     * 
     * @param string $nombreDestinatario
     * @param int $numeroQueja
     * @param string $respuesta
     * @param string|null $archivoRespuesta
     * @return string
     */
    protected function generarCuerpoHTML($nombreDestinatario, $numeroQueja, $respuesta, $archivoRespuesta) 
    {
        $nombreSeguro = htmlspecialchars($nombreDestinatario, ENT_QUOTES, 'UTF-8');
        $quejaSegura = htmlspecialchars($numeroQueja, ENT_QUOTES, 'UTF-8');
        $respuestaSegura = nl2br(htmlspecialchars($respuesta, ENT_QUOTES, 'UTF-8'));
        $archivoMensaje = $archivoRespuesta ? "<p>Se adjunta documento con información adicional.</p>" : "";

        return '
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
            <div class="container">
                <h2 class="header">Respuesta a su Queja</h2>
                <div class="content">
                    <p>Estimado(a) ' . $nombreSeguro . ',</p>
                    <p>Nos complace informarle que su queja con número #' . $quejaSegura . ' ha sido resuelta.</p>
                    <p><strong>Respuesta:</strong></p>
                    <p>' . $respuestaSegura . '</p>
                    ' . $archivoMensaje . '
                </div>
                <div class="footer">
                    <p>Este es un mensaje automático, por favor no responda a este correo.</p>
                    <p>Sistema de Quejas y Reclamos</p>
                </div>
            </div>
        </body>
        </html>';
    }

    /**
     * Genera la versión en texto plano del correo
     * 
     * @param string $respuesta
     * @return string
     */
    protected function generarTextoPlano($respuesta) 
    {
        return strip_tags(str_replace(
            array('<br>', '</p>'), 
            array("\n", "\n\n"), 
            $respuesta
        ));
    }
}