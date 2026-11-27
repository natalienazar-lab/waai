<?php
// Shared mail delivery for the site's PHP endpoints (contact form, assessment report).
// Hetzner treats wisely-applied-ai.com as a local domain, so PHP mail() never reaches the real
// inbox (Microsoft 365, set up through GoDaddy). Instead we hand the message straight to the
// domain's MX server, the same way any outside mail server would deliver it.
const FROM = 'webmaster@wisely-applied-ai.com';
$GLOBALS['smtp_err'] = '';

// Text-only, or multipart/alternative (plain text + HTML) when $html is given. Returns [Content-Type header, body].
function mime_parts(string $text, string $html = ''): array {
  if ($html === '') return ['Content-Type: text/plain; charset=UTF-8' . "\r\n" . 'Content-Transfer-Encoding: base64', chunk_split(base64_encode($text))];
  $b = 'waai_' . bin2hex(random_bytes(8));
  $body = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text))
        . "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
        . "--$b--\r\n";
  return ["Content-Type: multipart/alternative; boundary=\"$b\"", $body];
}

// Through the hosting's own mail server (used when direct delivery is refused, e.g. Hetzner blocks port 25 to Gmail)
function local_send(string $to, string $subject, string $text, string $html, string $replyTo): bool {
  [$ctype, $body] = mime_parts($text, $html);
  $headers = 'From: ' . ($GLOBALS['mail_from_name'] ?? 'WAAI website') . ' <' . FROM . ">\r\nReply-To: $replyTo\r\nMIME-Version: 1.0\r\n" . $ctype;
  return mail($to, $subject, $body, $headers, '-f' . FROM);
}

function mx_send(string $to, string $subject, string $body, string $replyTo, string $html = ''): bool {
  $domain = substr(strrchr($to, '@'), 1);
  $hosts = []; $weights = [];
  if (getmxrr($domain, $hosts, $weights)) array_multisort($weights, $hosts); else $hosts = [$domain];
  foreach ($hosts as $mx) { if (smtp_deliver($mx, $to, $subject, $body, $replyTo, $html)) return true; }
  return false;
}

function smtp_deliver(string $mx, string $to, string $subject, string $body, string $replyTo, string $html = ''): bool {
  $s = @stream_socket_client("tcp://$mx:25", $errno, $err, 15);
  if (!$s) { $GLOBALS['smtp_err'] = "connect $mx: $err"; return false; }
  stream_set_timeout($s, 20);
  $read = function () use ($s) { $out = ''; while (($l = fgets($s, 1024)) !== false) { $out .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; } return $out; };
  $cmd = function (string $line, string $expect) use ($s, $read) {
    fwrite($s, $line . "\r\n"); $r = $read();
    if (strpos($r, $expect) !== 0) { $GLOBALS['smtp_err'] = trim(substr($line, 0, 12) . ' -> ' . $r); return false; }
    return true;
  };
  $greet = $read();
  if (strpos($greet, '220') !== 0) { $GLOBALS['smtp_err'] = 'greeting: ' . trim($greet); return false; }
  if (!$cmd('EHLO wisely-applied-ai.com', '250')) return false;
  if ($cmd('STARTTLS', '220')) {
    if (!stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { $GLOBALS['smtp_err'] = 'tls'; return false; }
    if (!$cmd('EHLO wisely-applied-ai.com', '250')) return false;
  }
  if (!$cmd('MAIL FROM:<' . FROM . '>', '250') || !$cmd("RCPT TO:<$to>", '25') || !$cmd('DATA', '354')) return false;
  [$ctype, $mimeBody] = mime_parts($body, $html);
  $msg = implode("\r\n", [
    'From: ' . ($GLOBALS['mail_from_name'] ?? 'WAAI website') . ' <' . FROM . '>', "To: <$to>", "Reply-To: $replyTo", "Subject: $subject",
    'Date: ' . date('r'), 'Message-ID: <' . bin2hex(random_bytes(12)) . '@wisely-applied-ai.com>',
    'MIME-Version: 1.0', $ctype, '', $mimeBody,
  ]);
  if (!$cmd($msg . "\r\n.", '250')) return false;
  $cmd('QUIT', '221');
  fclose($s);
  return true;
}

