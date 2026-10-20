<?php
declare(strict_types=1);

/**
 * Sends email through SMTP when configured (Hostinger: smtp.hostinger.com, port 465, SSL),
 * otherwise through PHP's mail(). Returns null on success or an error message.
 */
function send_mail(string $to, string $subject, string $html, ?string $replyTo = null): ?string
{
    $to = trim($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid recipient address.';
    }
    $fromEmail = trim((string) setting('mail_from', '')) ?: trim((string) setting('smtp_user', '')) ?: trim((string) setting('email', ''));
    $fromName = (string) setting('mail_from_name', setting('site_name'));
    $text = trim(html_entity_decode(strip_tags(preg_replace(['/<br\s*\/?>/i', '/<\/(p|h\d|li|tr)>/i'], ["\n", "\n\n"], $html))));
    $boundary = 'b' . bin2hex(random_bytes(12));
    $headers = [
        'Date' => date('r'),
        'From' => mail_address($fromName, $fromEmail),
        'Reply-To' => $replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? $replyTo : $fromEmail,
        'Message-ID' => '<' . bin2hex(random_bytes(10)) . '@' . (explode('@', $fromEmail)[1] ?? 'localhost') . '>',
        'MIME-Version' => '1.0',
        'Content-Type' => 'multipart/alternative; boundary="' . $boundary . '"',
    ];
    $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($text))
        . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($html))
        . "--$boundary--\r\n";
    $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    if (trim((string) setting('smtp_host', '')) !== '') {
        return smtp_send($fromEmail, $to, $encSubject, $headers, $body);
    }
    $headerLines = '';
    foreach ($headers as $k => $v) {
        if ($k !== 'Date') {
            $headerLines .= "$k: $v\r\n";
        }
    }
    $ok = @mail($to, $encSubject, $body, rtrim($headerLines), $fromEmail ? '-f' . $fromEmail : '');
    return $ok ? null : 'PHP mail() failed. Add SMTP details in Settings → Email.';
}

function mail_address(string $name, string $email): string
{
    return '=?UTF-8?B?' . base64_encode($name) . '?= <' . $email . '>';
}

function smtp_send(string $from, string $to, string $subject, array $headers, string $body): ?string
{
    $host = trim((string) setting('smtp_host'));
    $port = (int) setting('smtp_port', 465);
    $secure = (string) setting('smtp_secure', 'ssl');
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
    $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        return "Could not connect to $host:$port ($errstr).";
    }
    stream_set_timeout($fp, 20);
    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = function (string $c, array $ok) use ($fp, $read): string {
        fwrite($fp, $c . "\r\n");
        $r = $read();
        if (!in_array((int) substr($r, 0, 3), $ok, true)) {
            throw new RuntimeException(trim($r) ?: 'No response');
        }
        return $r;
    };
    try {
        $greet = $read();
        if ((int) substr($greet, 0, 3) !== 220) {
            throw new RuntimeException(trim($greet));
        }
        $ehlo = 'EHLO ' . (gethostname() ?: 'localhost');
        $cmd($ehlo, [250]);
        if ($secure === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                throw new RuntimeException('TLS negotiation failed');
            }
            $cmd($ehlo, [250]);
        }
        if (setting('smtp_user', '') !== '') {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode((string) setting('smtp_user')), [334]);
            $cmd(base64_encode((string) setting('smtp_pass')), [235]);
        }
        $cmd('MAIL FROM:<' . $from . '>', [250]);
        $cmd('RCPT TO:<' . $to . '>', [250, 251]);
        $cmd('DATA', [354]);
        $msg = 'To: ' . $to . "\r\nSubject: " . $subject . "\r\n";
        foreach ($headers as $k => $v) {
            $msg .= "$k: $v\r\n";
        }
        // Dot-stuffing: a line starting with "." must be doubled.
        $msg .= "\r\n" . preg_replace('/^\./m', '..', $body);
        $cmd($msg . "\r\n.", [250]);
        $cmd('QUIT', [221]);
        fclose($fp);
        return null;
    } catch (Throwable $e) {
        @fclose($fp);
        error_log('SMTP error: ' . $e->getMessage());
        return 'SMTP error: ' . $e->getMessage();
    }
}

/** Branded email wrapper (inline styles: email clients ignore stylesheets). */
function email_template(string $heading, string $bodyHtml, ?array $button = null): string
{
    $site = e(setting('site_name'));
    $logo = e(abs_url(img(setting('logo'))));
    $btn = $button ? '<p style="margin:28px 0 8px"><a href="' . e($button['url']) . '" style="background:#FF6600;color:#fff;text-decoration:none;font-weight:700;padding:14px 24px;border-radius:999px;display:inline-block">' . e($button['label']) . '</a></p>' : '';
    $foot = e(setting('legal_name')) . ' · ' . e(setting('address')) . '<br>' . e(setting('phone')) . ' · ' . e(setting('email'));
    return '<!doctype html><html><body style="margin:0;background:#eef1fb;font-family:Arial,Helvetica,sans-serif;color:#10163a">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef1fb;padding:32px 12px"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fff;border-radius:16px;overflow:hidden">'
        . '<tr><td style="padding:28px 32px;border-bottom:4px solid #FF6600"><img src="' . $logo . '" alt="' . $site . '" width="180" style="display:block;height:auto"></td></tr>'
        . '<tr><td style="padding:32px;font-size:15px;line-height:1.6"><h1 style="font-size:22px;margin:0 0 16px;color:#001684">' . e($heading) . '</h1>' . $bodyHtml . $btn . '</td></tr>'
        . '<tr><td style="padding:20px 32px;background:#000C64;color:#c9d0f5;font-size:12px;line-height:1.6">' . $foot . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

function email_table(array $rows): string
{
    $html = '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:8px 0 0">';
    foreach ($rows as $k => $v) {
        if ($v === null || $v === '') {
            continue;
        }
        $html .= '<tr><td style="padding:8px 12px 8px 0;border-bottom:1px solid #e5e8f5;color:#5b6386;width:38%;vertical-align:top">' . e($k) . '</td>'
            . '<td style="padding:8px 0;border-bottom:1px solid #e5e8f5;font-weight:600">' . nl2br(e($v)) . '</td></tr>';
    }
    return $html . '</table>';
}
