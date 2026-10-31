<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Recibir y sanitizar entrada
$name = trim($_POST['nombre'] ?? '');
$phone = trim($_POST['telefono'] ?? '');
$email = trim($_POST['correo'] ?? '');
$message = trim($_POST['mensaje'] ?? '');

if (!$name || !$email || !$message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Por favor complete los campos requeridos (Nombre, Correo, Mensaje).']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Correo inválido.']);
    exit;
}

$to = 'beluchicharroneria@gmail.com';
$subject = "Contacto web: " . substr($name, 0, 64);
$body = "Nuevo mensaje desde el formulario de contacto:\n\n";
$body .= "Nombre: " . $name . "\n";
$body .= "Teléfono: " . $phone . "\n";
$body .= "Correo: " . $email . "\n\n";
$body .= "Mensaje:\n" . $message . "\n";

// Para mejorar entregabilidad: usar como From una dirección del propio dominio
$serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
$serverName = preg_replace('/[^a-z0-9.\-]/i', '', $serverName);
$fromEmail = 'no-reply@' . $serverName;

$headers = [];
$headers[] = 'From: "BELU Chicharroneria" <' . $fromEmail . '>';
$headers[] = 'Reply-To: ' . $email;
$headers[] = 'Return-Path: ' . $fromEmail;
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'X-Mailer: PHP/' . phpversion();

// -----------------------------------------------------------------------------
// Intentar envío con PHPMailer via SMTP (recomendado)
// Requiere instalar: composer require phpmailer/phpmailer
// Configurar variables de entorno: SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS,
// SMTP_FROM, SMTP_FROM_NAME, SMTP_SECURE (tls|ssl|auto)
// -----------------------------------------------------------------------------

$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/mail.log';

$sent = false;

// Intentar cargar autoload si existe
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Si PHPMailer no está disponible, devolver error instructivo
if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    $entry = [
        'time' => date('c'),
        'success' => false,
        'reason' => 'PHPMailer not installed',
        'note' => 'Run "composer require phpmailer/phpmailer" in the project root.'
    ];
    @file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'PHPMailer no está instalado en el servidor. Instala dependencias con Composer (ver logs/README_SMTP.md).'
    ]);
    exit;
}

use PHPMailer\\PHPMailer\\PHPMailer;
use PHPMailer\\PHPMailer\\Exception as PHPMailerException;

// Leer configuración SMTP desde variables de entorno
$smtpHost = getenv('SMTP_HOST') ?: null;
$smtpPort = getenv('SMTP_PORT') ?: null;
$smtpUser = getenv('SMTP_USER') ?: null;
$smtpPass = getenv('SMTP_PASS') ?: null;
$smtpFrom = getenv('SMTP_FROM') ?: $fromEmail;
$smtpFromName = getenv('SMTP_FROM_NAME') ?: 'BELU Chicharroneria';
$smtpSecure = getenv('SMTP_SECURE') ?: 'auto'; // tls, ssl, or auto

$missing = [];
if (!$smtpHost) $missing[] = 'SMTP_HOST';
if (!$smtpPort) $missing[] = 'SMTP_PORT';
if (!$smtpUser) $missing[] = 'SMTP_USER';
if (!$smtpPass) $missing[] = 'SMTP_PASS';

if (!empty($missing)) {
    $entry = [
        'time' => date('c'),
        'success' => false,
        'reason' => 'Missing SMTP env vars',
        'missing' => $missing
    ];
    @file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Faltan variables SMTP en el entorno: ' . implode(', ', $missing) . '. Revisa README_SMTP.md.'
    ]);
    exit;
}

// Enviar con PHPMailer
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->Port = (int)$smtpPort;

    // Determinar tipo de cifrado
    if ($smtpSecure === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->SMTPAutoTLS = false;
    } elseif ($smtpSecure === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPAutoTLS = true;
    } else {
        // auto: elegir STARTTLS si puerto 587, SSL si 465
        if ((int)$smtpPort === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->SMTPAutoTLS = false;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAutoTLS = true;
        }
    }

    $mail->setFrom($smtpFrom, $smtpFromName);
    $mail->addAddress($to);
    $mail->addReplyTo($email, $name);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = strip_tags($body);
    $mail->CharSet = 'UTF-8';

    $mail->send();
    $sent = true;

    $entry = [
        'time' => date('c'),
        'success' => true,
        'to' => $to,
        'subject' => $subject,
        'method' => 'PHPMailer SMTP',
    ];
    @file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);

    echo json_encode(['success' => true, 'message' => 'Mensaje enviado correctamente. Gracias.']);
    exit;

} catch (PHPMailerException $e) {
    $entry = [
        'time' => date('c'),
        'success' => false,
        'to' => $to,
        'subject' => $subject,
        'error' => $e->getMessage(),
    ];
    @file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al enviar el mensaje (SMTP). Revisa logs/mail.log para más detalles.']);
    exit;
}

?>
