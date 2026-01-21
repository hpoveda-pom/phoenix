<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

function class_sentMail($to, $cc, $bcc, $subject, $body, $attachmentPath) {
    $mail = new PHPMailer(true);

    try {
        // Activar la depuración para ver errores detallados (Desactivar en producción)
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Cambia a DEBUG_SERVER si quieres logs detallados
        $mail->Debugoutput = function($str, $level) { error_log("PHPMailer Debug [$level]: $str"); };

        // Configuración del servidor SMTP de Outlook
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'hpoveda@test.com';
        $mail->Password = 'test';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587; // Puerto para TLS

        // Configuración de remitente y destinatarios
        $mail->setFrom('hpoveda@test.com', 'Phoenix');
        $mail->addAddress($to); // Destinatario principal

        if (!empty($cc)) {
            $mail->addCC($cc);
        }
        if (!empty($bcc)) {
            $mail->addBCC($bcc);
        }

        // Adjuntar archivo si existe
        if (!empty($attachmentPath) && file_exists($attachmentPath)) {
            $mail->addAttachment($attachmentPath);
        } else if (!empty($attachmentPath)) {
            error_log("Error: El archivo adjunto no existe en la ruta: $attachmentPath");
            return "Error: Archivo adjunto no encontrado.";
        }

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = !empty($body) ? $body : 'Contenido no disponible.';
        $mail->AltBody = !empty($body) ? htmlspecialchars_decode($body) : 'Contenido no disponible.';

        // Intento de envío
        if ($mail->send()) {
            return "Correo enviado exitosamente a: $to";
        }
    } catch (Exception $e) {
        $errorMsg = "Error al enviar correo: " . $mail->ErrorInfo;
        error_log($errorMsg);
        return $errorMsg;
    }
}
