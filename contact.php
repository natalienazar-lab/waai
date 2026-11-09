<?php
// Contact form handler: validates the POST and emails it to the WAAI inbox.
header('Content-Type: application/json; charset=utf-8');

const TO = 'info@wisely-applied-ai.com';

function fail(int $code, string $msg) { http_response_code($code); echo json_encode(['ok' => false, 'error' => $msg]); exit; }
function field(string $k, int $max = 500): string { return trim(mb_substr(str_replace(["\r", "\0"], '', (string)($_POST[$k] ?? '')), 0, $max)); }

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
    'From: WAAI website <' . FROM . '>', "To: <$to>", "Reply-To: $replyTo", "Subject: $subject",
    'Date: ' . date('r'), 'Message-ID: <' . bin2hex(random_bytes(12)) . '@wisely-applied-ai.com>',
    'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: base64', '',
    chunk_split(base64_encode($body)),
  ]);
  if (!$cmd($msg . "\r\n.", '250')) return false;
  $cmd('QUIT', '221');
  fclose($s);
  return true;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'method');
if (field('website') !== '') { echo json_encode(['ok' => true]); exit; } // honeypot: silently drop bots

$name = field('name', 120);
$email = field('email', 200);
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) fail(422, 'invalid');

$interests = ['consult' => 'Private consultation', 'training' => 'Corporate training', 'webinar' => 'Webinars or practitioner training',
              'assess' => 'Maturity assessment', 'partner' => 'Design partner', 'other' => 'Something else'];
$interest = $interests[field('interest', 20)] ?? 'Not specified';

$body = "Name: $name\nEmail: $email\nCompany: " . field('company', 200) . "\nCompany size: " . field('size', 20)
      . "\nInterested in: $interest\n\nMessage:\n" . field('message', 5000) . "\n";
$subject = '=?UTF-8?B?' . base64_encode("WAAI website: $interest — $name") . '?=';

if (!mx_send(TO, $subject, $body, $email)) {
  // Fallback: keep the enquiry in the Hetzner webmaster mailbox so no lead is lost.
  $headers = implode("\r\n", ['From: WAAI website <' . FROM . '>', "Reply-To: $email", 'Content-Type: text/plain; charset=UTF-8']);
  $note = "[Not delivered to " . TO . ": " . $GLOBALS['smtp_err'] . "]\n\n";
  if (!mail(FROM, $subject, $note . $body, $headers, '-f' . FROM)) fail(500, 'mail: ' . $GLOBALS['smtp_err']);
}
echo json_encode(['ok' => true]);
