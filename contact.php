<?php
// Contact form handler: validates the POST and emails it to the WAAI inbox.
// The inbox is hosted at GoDaddy, so mail goes out through their SMTP server
// (credentials in smtp-config.php, written by the deploy from GitHub secrets).
header('Content-Type: application/json; charset=utf-8');

const TO = 'info@wisely-applied-ai.com';

function fail(int $code, string $msg) { http_response_code($code); echo json_encode(['ok' => false, 'error' => $msg]); exit; }
function field(string $k, int $max = 500): string { return trim(mb_substr(str_replace(["\r", "\0"], '', (string)($_POST[$k] ?? '')), 0, $max)); }

// Minimal SMTP client: implicit TLS on 465, STARTTLS otherwise, AUTH LOGIN.
function smtp_send(array $c, string $to, string $subject, string $body, string $replyTo): bool {
  $port = (int)$c['port'];
  $host = ($port === 465 ? 'ssl://' : 'tcp://') . $c['host'];
  $s = @stream_socket_client("$host:$port", $errno, $err, 15);
  if (!$s) return false;
  stream_set_timeout($s, 15);
  $read = function () use ($s) { $out = ''; while (($l = fgets($s, 1024)) !== false) { $out .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; } return $out; };
  $cmd = function (string $line, string $expect) use ($s, $read) { fwrite($s, $line . "\r\n"); return strpos($read(), $expect) === 0; };
  if (strpos($read(), '220') !== 0) return false;
  if (!$cmd('EHLO wisely-applied-ai.com', '250')) return false;
  if ($port !== 465) {
    if (!$cmd('STARTTLS', '220')) return false;
    if (!stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) return false;
    if (!$cmd('EHLO wisely-applied-ai.com', '250')) return false;
  }
  if (!$cmd('AUTH LOGIN', '334') || !$cmd(base64_encode($c['user']), '334') || !$cmd(base64_encode($c['pass']), '235')) return false;
  if (!$cmd('MAIL FROM:<' . $c['user'] . '>', '250') || !$cmd("RCPT TO:<$to>", '25') || !$cmd('DATA', '354')) return false;
  $msg = implode("\r\n", [
    'From: WAAI website <' . $c['user'] . '>', "To: <$to>", "Reply-To: $replyTo", "Subject: $subject",
    'Date: ' . date('r'), 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8', 'Content-Transfer-Encoding: base64', '',
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

$cfgFile = __DIR__ . '/smtp-config.php';
if (!is_file($cfgFile)) fail(500, 'config');
$cfg = require $cfgFile;
if (!smtp_send($cfg, TO, $subject, $body, $email)) fail(500, 'mail');
echo json_encode(['ok' => true]);
