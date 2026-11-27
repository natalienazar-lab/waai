<?php
// Contact form handler: validates the POST and emails it to the WAAI inbox.
header('Content-Type: application/json; charset=utf-8');

const TO = 'info@wisely-applied-ai.com';

function fail(int $code, string $msg) { http_response_code($code); echo json_encode(['ok' => false, 'error' => $msg]); exit; }
function field(string $k, int $max = 500): string { return trim(mb_substr(str_replace(["\r", "\0"], '', (string)($_POST[$k] ?? '')), 0, $max)); }

require __DIR__ . '/inc/mail.php';

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
