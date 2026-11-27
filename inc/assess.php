<?php
// Shared logic for the assessment endpoints: config, question data, answer checks, level rules, rate limit.
// The level is always computed here on the server from the method's rules, never taken from the browser.

const ASSESS_TEAM = 'info@wisely-applied-ai.com';

function assess_config(): array {
  static $cfg = null;
  if ($cfg === null) {
    $f = __DIR__ . '/config.php'; // written by the deploy workflow from GitHub secrets, never committed
    $cfg = is_file($f) ? (array)(require $f) : [];
  }
  return $cfg;
}

function assess_data(): array {
  static $d = null;
  if ($d === null) $d = json_decode(file_get_contents(__DIR__ . '/../assessment/data.json'), true);
  return $d;
}

function json_out(int $code, array $body) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($body, JSON_UNESCAPED_UNICODE);
  exit;
}

function read_json(): array {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(405, ['ok' => false, 'error' => 'method']);
  $raw = file_get_contents('php://input', false, null, 0, 20000);
  $in = json_decode((string)$raw, true);
  if (!is_array($in)) json_out(400, ['ok' => false, 'error' => 'json']);
  return $in;
}

function clip($v, int $n): string { return trim(mb_substr(str_replace(["\r", "\0"], '', (string)$v), 0, $n)); }

function lang_of($v): string { return $v === 'uk' ? 'uk' : 'en'; }

// Indices of failed gate rules for a level
function fails(array $ans, int $level): array {
  $out = [];
  foreach (assess_data()['RULES'][$level] as $k => $r) if (!isset($ans[$r[0]]) || $ans[$r[0]] < $r[1]) $out[] = $k;
  return $out;
}

// Answers must follow the adaptive path: 9, then 12 only if L2+L3 pass, then 15 only if L4 passes.
function valid_answers($ans): ?array {
  if (!is_array($ans) || !in_array(count($ans), [9, 12, 15], true)) return null;
  $Q = assess_data()['Q']['en'];
  $ans = array_values($ans);
  foreach ($ans as $i => $a) {
    if (!is_int($a) || $a < 0 || $a >= count($Q[$i]['o'])) return null;
  }
  $first9 = array_slice($ans, 0, 9);
  $upTo3 = !fails($first9, 2) && !fails($first9, 3);
  if (count($ans) === 9 && $upTo3) return null;
  if (count($ans) >= 12) {
    if (!$upTo3) return null;
    $l4 = !fails(array_slice($ans, 0, 12), 4);
    if (count($ans) === 12 && $l4) return null;
    if (count($ans) === 15 && !$l4) return null;
  }
  return $ans;
}

function level_of(array $ans): int {
  $lv = 1;
  foreach ([2, 3, 4, 5] as $n) { if (fails($ans, $n)) break; $lv = $n; }
  return $lv;
}

// Up to three conditions missing for the next level, in the chosen language
function gaps_of(array $ans, int $lv, string $lang): array {
  if ($lv >= 5) return [];
  $gi = $lang === 'uk' ? 2 : 3;
  $rules = assess_data()['RULES'][$lv + 1];
  return array_map(fn($k) => $rules[$k][$gi], array_slice(fails($ans, $lv + 1), 0, 3));
}

// Simple per-IP limit, stored in the system temp folder
function rate_limit(string $bucket, int $max, int $window = 3600) {
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'x';
  $f = sys_get_temp_dir() . '/waai_' . $bucket . '_' . substr(hash('sha256', $ip . __DIR__), 0, 24);
  $now = time();
  $hits = array_filter(array_map('intval', @file($f, FILE_IGNORE_NEW_LINES) ?: []), fn($t) => $t > $now - $window);
  if (count($hits) >= $max) json_out(429, ['ok' => false, 'error' => 'limit']);
  $hits[] = $now;
  @file_put_contents($f, implode("\n", $hits), LOCK_EX);
}
