<?php
// Assessment results.
//   action "lead": a finished assessment goes to the WAAI team (info@ mailbox and the leads Google Sheet).
//   action "send": the respondent asked for the report by email.
require __DIR__ . '/../inc/assess.php';
require __DIR__ . '/../inc/mail.php';
$GLOBALS['mail_from_name'] = 'WAAI';

$in = read_json();
if (clip($in['website'] ?? '', 10) !== '') json_out(200, ['ok' => true]); // honeypot
$action = ($in['action'] ?? '') === 'send' ? 'send' : 'lead';
$lang = lang_of($in['lang'] ?? '');
$ans = valid_answers($in['answers'] ?? null);
$name = clip($in['name'] ?? '', 120);
$email = clip($in['email'] ?? '', 200);
$company = clip($in['company'] ?? '', 200);
$role = clip($in['role'] ?? '', 120);
$comment = clip($in['comment'] ?? '', 3000);
if ($ans === null || $name === '' || $company === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($in['consent'])) {
  json_out(422, ['ok' => false, 'error' => 'invalid']);
}
rate_limit($action, $action === 'send' ? 5 : 10);

$d = assess_data();
$lv = level_of($ans);
$levelName = $d['LEVELS'][$lang][$lv];
$gaps = gaps_of($ans, $lv, $lang);
$L = 'ABCDE';
$uk = $lang === 'uk';
$tx = $uk ? [
  'subj' => "Ваш рівень ШІ-зрілості: L$lv · $levelName",
  'hi' => "Вітаємо, $name!\n\nДякуємо, що пройшли швидку оцінку зрілості ШІ за моделлю WAAI. Ось ваш результат.",
  'level' => 'Орієнтовний рівень зрілості', 'means' => 'Що це означає', 'holds' => 'Що стримує перехід на L' . ($lv + 1),
  'top' => 'Ви на найвищому рівні моделі. Поглиблена діагностика допоможе підтвердити його й знайти місця для подальшого розвитку.',
  'ai' => 'Коментар WAAI агента', 'answers' => 'Ваші відповіді',
  'disc' => 'Це самооцінка одного представника організації без перевірки доказів. Детальний профіль за сімома вимірами дає поглиблена діагностика.',
  'next' => "Обговорімо ваш результат і перші кроки на 30-хвилинному дзвінку: просто відповідайте на цей лист або напишіть на " . ASSESS_TEAM . ".\n\nКоманда WAAI\nhttps://wisely-applied-ai.com/uk/",
] : [
  'subj' => "Your AI maturity level: L$lv · $levelName",
  'hi' => "Hello $name,\n\nThank you for taking the WAAI quick AI maturity assessment. Here is your result.",
  'level' => 'Approximate maturity level', 'means' => 'What this means', 'holds' => 'What holds you back from L' . ($lv + 1),
  'top' => "You're at the top level of the model. An in-depth diagnostic can confirm it and show where to keep improving.",
  'ai' => 'Comment from the WAAI agent', 'answers' => 'Your answers',
  'disc' => 'This is a self-assessment by one person without checking evidence. A detailed profile across the seven dimensions comes from the in-depth diagnostic.',
  'next' => "Let's go through your result and first steps on a 30-minute call: just reply to this email or write to " . ASSESS_TEAM . ".\n\nThe WAAI team\nhttps://wisely-applied-ai.com/",
];

$answers = [];
foreach ($ans as $i => $a) $answers[] = ($i + 1) . '. ' . $d['Q'][$lang][$i]['t'] . "\n   " . $L[$a] . '. ' . $d['Q'][$lang][$i]['o'][$a];
$report = $tx['level'] . ": L$lv · $levelName\n\n"
  . $tx['means'] . ":\n" . $d['LEVEL_TEXT'][$lang][$lv] . "\n\n"
  . ($gaps ? $tx['holds'] . ":\n- " . implode("\n- ", $gaps) : $tx['top']) . "\n\n"
  . ($comment !== '' ? $tx['ai'] . ":\n" . $comment . "\n\n" : '')
  . $tx['answers'] . ":\n" . implode("\n", $answers) . "\n\n" . $tx['disc'];
$enc = fn($s) => '=?UTF-8?B?' . base64_encode($s) . '?=';

if ($action === 'send') {
  $ok = mx_send($email, $enc($tx['subj']), $tx['hi'] . "\n\n" . $report . "\n\n" . $tx['next'] . "\n", ASSESS_TEAM);
  if (!$ok) error_log('WAAI assessment report not delivered to respondent: ' . $GLOBALS['smtp_err']);
  json_out($ok ? 200 : 502, ['ok' => $ok]);
}

// Team copy: info@ mailbox (fallback webmaster@) and the Google Sheet
$teamBody = "Name: $name\nEmail: $email\nCompany: $company\nRole: $role\nLanguage: $lang\nConsent: yes\n\n" . $report . "\n";
$subject = $enc("WAAI assessment: L$lv · $company · $name");
if (!mx_send(ASSESS_TEAM, $subject, $teamBody, $email)) {
  $headers = implode("\r\n", ['From: WAAI website <' . FROM . '>', "Reply-To: $email", 'Content-Type: text/plain; charset=UTF-8']);
  @mail(FROM, $subject, '[Not delivered to ' . ASSESS_TEAM . ': ' . $GLOBALS['smtp_err'] . "]\n\n" . $teamBody, $headers, '-f' . FROM);
}
$sheet = assess_config()['sheet_url'] ?? '';
if ($sheet !== '') {
  $ch = curl_init($sheet);
  curl_setopt_array($ch, [
    CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => ['content-type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
      'lang' => $lang, 'name' => $name, 'email' => $email, 'company' => $company, 'role' => $role, 'consent' => true,
      'level' => (string)$lv, 'levelName' => $levelName, 'gaps' => $gaps, 'answers' => implode("\n", $answers), 'comment' => $comment,
    ], JSON_UNESCAPED_UNICODE),
  ]);
  curl_exec($ch);
  curl_close($ch);
}
json_out(200, ['ok' => true]);
