<?php
/**
 * Configuración de Email - Sistema de Quejas
 * @author crisgacovi
 * @date 2025-05-06
 */

// Configuración del remitente
define('EMAIL_FROM_NAME', 'Auditorias Escamilla');
define('EMAIL_FROM_ADDRESS', 'contactenos@inspirateds.com');

// Configuración del servidor SMTP (recomendado)
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465); // Puerto común para TLS
define('SMTP_USERNAME', 'contactenos@inspirateds.com');
define('SMTP_PASSWORD', 'Inspirateds25&#');
define('SMTP_SECURE', 'ssl'); // tls o ssl