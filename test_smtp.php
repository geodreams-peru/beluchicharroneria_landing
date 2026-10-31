<?php
// test_smtp.php
// Página simple para probar conexión SMTP con PHPMailer.

// Ruta del log
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/test_smtp.log';

function write_log($entry) {
    global $logFile;
    @file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cfg = [];
    $cfg['host'] = trim($_POST['smtp_host'] ?? getenv('SMTP_HOST') ?? '');
    $cfg['port'] = trim($_POST['smtp_port'] ?? getenv('SMTP_PORT') ?? '587');
    $cfg['user'] = trim($_POST['smtp_user'] ?? getenv('SMTP_USER') ?? '');
    $cfg['pass'] = trim($_POST['smtp_pass'] ?? getenv('SMTP_PASS') ?? '');
    $cfg['from'] = trim($_POST['smtp_from'] ?? getenv('SMTP_FROM') ?? $cfg['user']);
    $cfg['from_name'] = trim($_POST['smtp_from_name'] ?? getenv('SMTP_FROM_NAME') ?? 'Test SMTP');
    $cfg['secure'] = trim($_POST['smtp_secure'] ?? getenv('SMTP_SECURE') ?? 'tls');
    $to = trim($_POST['to_email'] ?? 'administracion@beluchicharroneria.com');
    $subject = trim($_POST['subject'] ?? 'Test SMTP desde test_smtp.php');
    $message = trim($_POST['message'] ?? 'Este es un correo de prueba enviado desde test_smtp.php');

    // Verificar PHPMailer
    if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
        $result = ['ok' => false, 'error' => 'PHPMailer no instalado. Ejecuta: composer require phpmailer/phpmailer'];
        write_log(['time' => date('c'), 'ok' => false, 'reason' => 'no_composer', 'cfg' => $cfg]);
    } else {
        require_once __DIR__ . '/vendor/autoload.php';
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $cfg['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $cfg['user'];
            $mail->Password = $cfg['pass'];
            $mail->Port = (int)$cfg['port'];

            if ($cfg['secure'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $mail->SMTPAutoTLS = false;
            } elseif ($cfg['secure'] === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPAutoTLS = true;
            } else {
                // auto: elegir STARTTLS si puerto 587, SSL si 465
                if ((int)$cfg['port'] === 465) {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    $mail->SMTPAutoTLS = false;
                } else {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->SMTPAutoTLS = true;
                }
            }

            $mail->setFrom($cfg['from'], $cfg['from_name']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);
            $mail->CharSet = 'UTF-8';

            $mail->send();
            $result = ['ok' => true, 'message' => 'Correo enviado correctamente a ' . $to];
            write_log(['time' => date('c'), 'ok' => true, 'to' => $to, 'cfg' => $cfg]);

        } catch (Exception $e) {
            $result = ['ok' => false, 'error' => $e->getMessage()];
            write_log(['time' => date('c'), 'ok' => false, 'error' => $e->getMessage(), 'cfg' => $cfg]);
        }
    }
}

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Test SMTP - Belú Chicharroneria</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f7f7f9; color:#111; padding:20px }
        .card { background:#fff; border-radius:8px; padding:18px; max-width:800px; margin:18px auto; box-shadow:0 6px 22px rgba(0,0,0,0.08) }
        label { display:block; margin:8px 0 4px; font-weight:600 }
        input[type=text], input[type=password], textarea { width:100%; padding:8px; border-radius:6px; border:1px solid #ddd }
        .row { display:grid; grid-template-columns:1fr 1fr; gap:12px }
        .btn { background:#FF6B00; color:#fff; padding:10px 14px; border-radius:8px; border:none; cursor:pointer }
        .ok { background:#e6ffed; border-left:4px solid #28a745; padding:12px }
        .err { background:#ffecec; border-left:4px solid #d9534f; padding:12px }
    </style>
</head>
<body>
<div class="card">
    <h2>Prueba rápida SMTP</h2>
    <p>Usa este formulario para probar tus credenciales SMTP. Si PHPMailer no está instalado, sigue las instrucciones en <code>README_SMTP.md</code>.</p>

    <?php if ($result !== null): ?>
        <?php if ($result['ok']): ?>
            <div class="ok"><strong>Éxito:</strong> <?php echo htmlspecialchars($result['message']); ?></div>
        <?php else: ?>
            <div class="err"><strong>Error:</strong> <?php echo htmlspecialchars($result['error']); ?></div>
        <?php endif; ?>
        <hr>
    <?php endif; ?>

    <form method="post">
        <div class="row">
            <div>
                <label>SMTP Host</label>
                <input type="text" name="smtp_host" value="<?php echo htmlspecialchars(getenv('SMTP_HOST') ?: 'mail.beluchicharroneria.com'); ?>">
            </div>
            <div>
                <label>SMTP Port</label>
                <input type="text" name="smtp_port" value="<?php echo htmlspecialchars(getenv('SMTP_PORT') ?: '587'); ?>">
            </div>
        </div>

        <div class="row">
            <div>
                <label>SMTP User</label>
                <input type="text" name="smtp_user" value="<?php echo htmlspecialchars(getenv('SMTP_USER') ?: 'administracion@beluchicharroneria.com'); ?>">
            </div>
            <div>
                <label>SMTP Pass</label>
                <input type="password" name="smtp_pass" value="">
            </div>
        </div>

        <div class="row">
            <div>
                <label>From (email)</label>
                <input type="text" name="smtp_from" value="<?php echo htmlspecialchars(getenv('SMTP_FROM') ?: 'administracion@beluchicharroneria.com'); ?>">
            </div>
            <div>
                <label>From name</label>
                <input type="text" name="smtp_from_name" value="Belú Chicharroneria">
            </div>
        </div>

        <div class="row">
            <div>
                <label>Secure (tls|ssl|auto)</label>
                <input type="text" name="smtp_secure" value="<?php echo htmlspecialchars(getenv('SMTP_SECURE') ?: 'tls'); ?>">
            </div>
            <div>
                <label>To (destino del test)</label>
                <input type="text" name="to_email" value="administracion@beluchicharroneria.com">
            </div>
        </div>

        <label>Asunto</label>
        <input type="text" name="subject" value="Test SMTP desde test_smtp.php">

        <label>Mensaje</label>
        <textarea name="message" rows="4">Este es un mensaje de prueba enviado desde test_smtp.php</textarea>

        <div style="margin-top:12px">
            <button class="btn" type="submit">Enviar correo de prueba</button>
        </div>
    </form>

    <hr>
    <p style="font-size:13px;color:#666">Logs: <code><?php echo htmlspecialchars($logFile); ?></code></p>
</div>
</body>
</html>
