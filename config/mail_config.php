<?php
/**
 * Configuración para el envío de correos
 * Última modificación: 2025-05-05 13:59:25
 */

// Prevenir acceso directo al archivo
if (!defined('IN_ADMIN') && !defined('IN_SYSTEM')) {
    define('IN_SYSTEM', true);
}

// Definir ambiente
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development'); // Cambiar a 'production' en producción
}

// Configuraciones de correo según el entorno
if (ENVIRONMENT === 'production') {
    define('MAIL_HOST', 'smtp-mail.outlook.com');
    define('MAIL_USERNAME', 'crisgacovi@hotmail.com'); // Tu correo de Outlook
    define('MAIL_PASSWORD', 'Cris790711$'); // Usar contraseña de aplicación
    define('MAIL_FROM_NAME', 'Sistema de Quejas');
    define('MAIL_PORT', 587);
    define('MAIL_ENCRYPTION', 'tls');
} else {
    // Configuración de desarrollo
    define('MAIL_HOST', 'localhost');
    define('MAIL_USERNAME', 'test@localhost');
    define('MAIL_PASSWORD', 'test');
    define('MAIL_FROM_NAME', 'Sistema de Quejas (Dev)');
    define('MAIL_PORT', 1025);
    define('MAIL_ENCRYPTION', '');
}

// Verificar configuración
if (empty(MAIL_PASSWORD)) {
    error_log('ADVERTENCIA: La contraseña del correo no está configurada');
}