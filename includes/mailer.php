<?php
/**
 * Simple mail wrapper: prefers PHPMailer if available (via Composer), otherwise falls back to mail().
 * Usage: require_once __DIR__ . '/mailer.php'; send_mail($to,$subject,$body,$from,$fromName,$isHtml=false);
 */
function send_mail(string $to, string $subject, string $body, string $from = null, string $fromName = null, bool $isHtml = false): bool {
    $cfg = @include __DIR__ . '/../config/notifications.php';
    $from = $from ?? ($cfg['from_email'] ?? 'no-reply@example.com');
    $fromName = $fromName ?? ($cfg['from_name'] ?? 'SmokeTech IMS');

    // Try PHPMailer if available
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $smtp = $cfg['smtp'] ?? [];
                if (!empty($smtp['host'])) {
                    $mail->isSMTP();
                    $mail->Host = $smtp['host'];
                    $mail->Port = $smtp['port'] ?? 25;
                    if (!empty($smtp['username'])) { $mail->SMTPAuth = true; $mail->Username = $smtp['username']; $mail->Password = $smtp['password'] ?? ''; }
                    if (!empty($smtp['encryption'])) { $mail->SMTPSecure = $smtp['encryption']; }
                }
                $mail->setFrom($from, $fromName);
                foreach (explode(',', $to) as $recipient) { $mail->addAddress(trim($recipient)); }
                $mail->Subject = $subject;
                if ($isHtml) { $mail->isHTML(true); $mail->Body = $body; $mail->AltBody = strip_tags($body); }
                else { $mail->Body = $body; }
                return (bool)$mail->send();
            } catch (Throwable $e) {
                error_log('PHPMailer send failed: ' . $e->getMessage());
                // fallback to mail()
            }
        }
    }

    // Fallback: PHP mail()
    $headers = "From: {$fromName} <{$from}>\r\n";
    if ($isHtml) {
        $headers .= "MIME-Version: 1.0\r\n" . "Content-Type: text/html; charset=UTF-8\r\n";
    } else {
        $headers .= "MIME-Version: 1.0\r\n" . "Content-Type: text/plain; charset=UTF-8\r\n";
    }
    return (bool) @mail($to, $subject, $body, $headers);
}

?>