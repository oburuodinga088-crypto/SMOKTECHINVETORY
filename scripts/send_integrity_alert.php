<?php
function send_integrity_alert(array $issues) {
    $cfg = @include __DIR__ . '/../config/notifications.php';
    $admin = $cfg['admin_email'] ?? 'admin@example.com';
    $from = $cfg['from_email'] ?? 'no-reply@example.com';
    $fromName = $cfg['from_name'] ?? 'SmokeTech IMS';

    $subject = "Integrity sweep detected issues on SmokeTech IMS";
    $body = "Integrity sweep found the following issues:\n\n";
    foreach ($issues as $k => $rows) {
        $body .= strtoupper($k) . " (" . count($rows) . " rows)\n";
        foreach ($rows as $r) {
            $body .= implode(' | ', array_map(function($v){ return is_null($v)?'NULL':$v; }, $r)) . "\n";
        }
        $body .= "\n";
    }

    // write JSON and text log
    $outDir = __DIR__ . '/logs';
    if (!is_dir($outDir)) @mkdir($outDir, 0755, true);
    $ts = date('Ymd_His');
    file_put_contents($outDir . "/integrity_{$ts}.json", json_encode($issues, JSON_PRETTY_PRINT));
    file_put_contents($outDir . "/integrity_{$ts}.txt", $body);

    // Try to email using unified wrapper (PHPMailer if available, else mail())
    require_once __DIR__ . '/../includes/mailer.php';
    $ok = send_mail($admin, $subject, $body, $from, $fromName, false);
    if (!$ok) {
        file_put_contents($outDir . "/integrity_{$ts}_email_failed.txt", "Mail failed to send to {$admin}\n");
    }

    return $ok;
}
