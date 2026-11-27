<?php
// Streams the short personal AI comment for a finished assessment as plain text.
// The prompt is built here from the answers, so this endpoint cannot be used as a general AI proxy.
// The Claude API key lives in inc/config.php (generated at deploy time from a GitHub secret).
require __DIR__ . '/../inc/assess.php';

const MODEL = 'claude-opus-5-5';

$in = read_json();
$lang = lang_of($in['lang'] ?? '');
$ans = valid_answers($in['answers'] ?? null);
if ($ans === null) json_out(422, ['ok' => false, 'error' => 'answers']);
$key = assess_config()['anthropic_key'] ?? '';
if ($key === '') json_out(503, ['ok' => false, 'error' => 'not configured']);
rate_limit('ai', 12);

$d = assess_data();
$lv = level_of($ans);
$gaps = gaps_of($ans, $lv, $lang);
$L = 'ABCDE';
$qa = [];
foreach ($ans as $i => $a) $qa[] = 'Q' . ($i + 1) . '. ' . $d['Q'][$lang][$i]['t'] . "\n" . $L[$a] . ': ' . $d['Q'][$lang][$i]['o'][$a];

$system = 'You are a WAAI (Wisely Applied AI) consultant writing a short personal comment on a company leader\'s quick AI maturity self-assessment. '
  . 'The maturity level was set by the method\'s rules: never change or dispute it. '
  . 'Write plain text in two short paragraphs, no headings, no markdown, no promised numbers, no invented facts about the company. '
  . 'Paragraph 1: one or two sentences on strengths visible in the answers, including local mature practices. '
  . 'Paragraph 2: two concrete first steps for the next 1-3 months, linked to the missing conditions.';
$langLine = $lang === 'uk'
  ? 'Write in Ukrainian, addressing the reader formally as «ви». Keep it under 80 words.'
  : 'Write in English, addressing the reader as "you". Keep it under 90 words.';
$user = "Level: L$lv \"" . $d['LEVELS'][$lang][$lv] . "\".\n"
  . 'Conditions missing for the next level: ' . ($gaps ? implode('; ', $gaps) : 'none, this is the top level') . ".\n\n"
  . "Answers:\n" . implode("\n\n", $qa) . "\n\n" . $langLine;

$body = json_encode([
  'model' => MODEL,
  'max_tokens' => 2000,
  'stream' => true,
  'output_config' => ['effort' => 'low'],
  'fallbacks' => 'default',
  'system' => $system,
  'messages' => [['role' => 'user', 'content' => $user]],
], JSON_UNESCAPED_UNICODE);

// Relay only the text deltas to the browser as they arrive
@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', '0');
while (ob_get_level()) ob_end_flush();
ignore_user_abort(false);

$status = 0; $buf = ''; $started = false; $sent = 0;
$ch = curl_init(assess_config()['api_url'] ?? 'https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => $body,
  CURLOPT_HTTPHEADER => [
    'content-type: application/json',
    'x-api-key: ' . $key,
    'anthropic-version: 2026-10-21',
    'anthropic-beta: server-side-fallback-2026-07-01',
  ],
  CURLOPT_CONNECTTIMEOUT => 10,
  CURLOPT_TIMEOUT => 90,
  CURLOPT_HEADERFUNCTION => function ($ch, $h) use (&$status) {
    if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) $status = (int)$m[1];
    return strlen($h);
  },
  CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$status, &$buf, &$started, &$sent) {
    if ($status !== 200) { $buf .= $chunk; return strlen($chunk); }
    $buf .= $chunk;
    while (($p = strpos($buf, "\n")) !== false) {
      $line = rtrim(substr($buf, 0, $p), "\r");
      $buf = substr($buf, $p + 1);
      if (strncmp($line, 'data:', 5) !== 0) continue;
      $ev = json_decode(trim(substr($line, 5)), true);
      if (($ev['type'] ?? '') === 'content_block_delta' && ($ev['delta']['type'] ?? '') === 'text_delta') {
        if (!$started) {
          $started = true;
          header('Content-Type: text/plain; charset=utf-8');
          header('Cache-Control: no-store');
          header('X-Accel-Buffering: no');
        }
        echo $ev['delta']['text'];
        $sent += strlen($ev['delta']['text']);
        @flush();
      }
    }
    return connection_aborted() ? 0 : strlen($chunk);
  },
]);
curl_exec($ch);
curl_close($ch);

if (!$started) {
  error_log('WAAI assessment AI failed: HTTP ' . $status . ' ' . substr($buf, 0, 300));
  json_out(502, ['ok' => false, 'error' => 'ai']);
}
