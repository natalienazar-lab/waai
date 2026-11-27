<?php
// Shared mail delivery for the site's PHP endpoints (contact form, assessment report).
// Hetzner treats wisely-applied-ai.com as a local domain, so PHP mail() never reaches the real
// inbox (Microsoft 365, set up through GoDaddy). Instead we hand the message straight to the
// domain's MX server, the same way any outside mail server would deliver it.
const FROM = 'webmaster@wisely-applied-ai.com';
$GLOBALS['smtp_err'] = '';

function mx_send(string $to, string $subject, string $body, string $replyTo): bool {
  $domain = substr(strrchr($to, '@'), 1);
  $hosts = []; $weights = [];
  if (getmxrr($domain, $hosts, $weights)) array_multisort($weights, $hosts); else $hosts = [$domain];
  foreach ($hosts as $mx) { if (smtp_deliver($mx, $to, $subject, $body, $replyTo)) return true; }
  return false;
}

function smtp_deliver(string $mx, string $to, string $subject, string $body, string $replyTo): bool {
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
  $msg = implode("\r\n", [
    'From: ' . ($GLOBALS['mail_from_name'] ?? 'WAAI website') . ' <' . FROM . '>', "To: <$to>", "Reply-To: $replyTo", "Subject: $subject",
    'Date: ' . date('r'), 'Message-ID: <' . bin2hex(random_bytes(12)) . '@wisely-applied-ai.com>',
    'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: base64', '',
    chunk_split(base64_encode($body)),
  ]);
  if (!$cmd($msg . "\r\n.", '250')) return false;
  $cmd('QUIT', '221');
  fclose($s);
  return true;
}

