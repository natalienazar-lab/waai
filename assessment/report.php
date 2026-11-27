<?php
// Assessment results.
//   action "lead": a finished assessment goes to the WAAI team (info@ mailbox and the leads Google Sheet).
//   action "send": the respondent asked for the report by email.
require __DIR__ . '/../inc/assess.php';
require __DIR__ . '/../inc/mail.php';
require __DIR__ . '/../inc/report_email.php';
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
// Per visitor: generous, so a team testing from one office is not blocked. Per recipient: stops mail bombing one address.
rate_limit($action, $action === 'send' ? 20 : 30);
if ($action === 'send') rate_limit('send_to', 5, 3600, strtolower($email));

$d = assess_data();
$lv = level_of($ans);
$levelName = $d['LEVELS'][$lang][$lv];
$gaps = gaps_of($ans, $lv, $lang);
$L = 'ABCDE';
$uk = $lang === 'uk';
$site = $uk ? 'https://wisely-applied-ai.com/uk/' : 'https://wisely-applied-ai.com/';
$tx = $uk ? [
  'subj' => "Ваш рівень ШІ-зрілості: L$lv · $levelName",
  'hi' => ["Вітаємо, $name!", 'Дякуємо, що пройшли швидку оцінку зрілості ШІ за моделлю WAAI. Ось ваш результат.'],
  'level' => 'Орієнтовний рівень зрілості', 'means' => 'Що це означає', 'holds' => 'Що стримує перехід на L' . ($lv + 1),
  'top' => 'Ви на найвищому рівні моделі. Поглиблена діагностика допоможе підтвердити його й знайти місця для подальшого розвитку.',
  'ai' => 'Коментар WAAI агента', 'answers' => 'Ваші відповіді',
  'disc' => 'Це самооцінка одного представника організації без перевірки доказів. Детальний профіль за сімома вимірами дає поглиблена діагностика.',
  'ctaH' => 'Що далі', 'ctaP' => 'Обговорімо ваш результат і перші кроки на 30-хвилинному дзвінку. Можна просто відповісти на цей лист.',
  'book' => 'Записатися на дзвінок', 'sign' => "З повагою,\nкоманда WAAI",
] : [
  'subj' => "Your AI maturity level: L$lv · $levelName",
  'hi' => ["Hello $name,", 'Thank you for taking the WAAI quick AI maturity assessment. Here is your result.'],
  'level' => 'Approximate maturity level', 'means' => 'What this means', 'holds' => 'What holds you back from L' . ($lv + 1),
  'top' => "You're at the top level of the model. An in-depth diagnostic can confirm it and show where to keep improving.",
  'ai' => 'Comment from the WAAI agent', 'answers' => 'Your answers',
  'disc' => 'This is a self-assessment by one person without checking evidence. A detailed profile across the seven dimensions comes from the in-depth diagnostic.',
  'ctaH' => 'Next steps', 'ctaP' => "Let's go through your result and first steps on a 30-minute call. You can simply reply to this email.",
  'book' => 'Book a call', 'sign' => "Best regards,\nThe WAAI team",
];
$bookUrl = (assess_config()['booking_url'] ?? '') ?: $site . '#contact';

$answers = []; $answerRows = [];
foreach ($ans as $i => $a) {
  $answers[] = ($i + 1) . '. ' . $d['Q'][$lang][$i]['t'] . "\n   " . $L[$a] . '. ' . $d['Q'][$lang][$i]['o'][$a];
  $answerRows[] = [$d['Q'][$lang][$i]['t'], $L[$a], $d['Q'][$lang][$i]['o'][$a]];
}
$report = $tx['level'] . ": L$lv · $levelName\n\n"
  . $tx['means'] . ":\n" . $d['LEVEL_TEXT'][$lang][$lv] . "\n\n"
  . ($gaps ? $tx['holds'] . ":\n- " . implode("\n- ", $gaps) : $tx['top']) . "\n\n"
  . ($comment !== '' ? $tx['ai'] . ":\n" . $comment . "\n\n" : '')
  . $tx['answers'] . ":\n" . implode("\n", $answers) . "\n\n" . $tx['disc'];
$card = [
  'lang' => $lang, 'lv' => $lv, 'levelName' => $levelName, 'company' => $company, 'levelLabel' => $tx['level'],
  'meansTitle' => $tx['means'], 'means' => $d['LEVEL_TEXT'][$lang][$lv], 'gaps' => $gaps, 'holdsTitle' => $tx['holds'], 'top' => $tx['top'],
  'aiTitle' => $tx['ai'], 'comment' => $comment, 'answersTitle' => $tx['answers'], 'answers' => $answerRows, 'disc' => $tx['disc'],
  'ctaTitle' => $tx['ctaH'], 'ctaText' => $tx['ctaP'], 'ctaButton' => $tx['book'], 'ctaUrl' => $bookUrl,
  'sign' => $tx['sign'], 'siteUrl' => $site, 'hi' => $tx['hi'],
];
$enc = fn($s) => '=?UTF-8?B?' . base64_encode($s) . '?=';

if ($action === 'send') {
  $text = implode("\n\n", $tx['hi']) . "\n\n" . $report . "\n\n" . $tx['ctaP'] . "\n" . $bookUrl . "\n\n" . $tx['sign'] . "\n" . $site . "\n";
  $html = report_email_html($card);
  $ok = mx_send($email, $enc($tx['subj']), $text, ASSESS_TEAM, $html);
  $detail = $ok ? '' : 'mx: ' . $GLOBALS['smtp_err'];
  if (!$ok) {
    // Direct delivery refused (Hetzner blocks port 25 to Gmail): hand the message to the hosting's own mail server.
    $ok = local_send($email, $enc($tx['subj']), $text, $html, ASSESS_TEAM);
    if (!$ok) $detail .= ' | mail() failed';
  }
  if ($detail !== '') error_log('WAAI assessment report to respondent: ' . $detail . ($ok ? ' (sent via mail())' : ''));
  json_out($ok ? 200 : 502, ['ok' => $ok, 'via' => $detail === '' ? 'mx' : 'local', 'detail' => substr($detail, 0, 300)]);
}

// Team copy: info@ mailbox (fallback webmaster@) and the Google Sheet
$teamBody = "Name: $name\nEmail: $email\nCompany: $company\nRole: $role\nLanguage: $lang\nConsent: yes\n\n" . $report . "\n";
$teamCard = $card;
$teamCard['hi'] = [];
$teamCard['team'] = [
  ["Ім'я / Name", h($name)], ['Email', '<a href="mailto:' . h($email) . '" style="color:#3C69CF;">' . h($email) . '</a>'],
  ['Компанія', h($company)], ['Посада', h($role)], ['Мова', h($lang)],
];
$teamCard['ctaText'] = $uk ? "Відповідь на цей лист піде клієнту ($email)." : "Replying to this email goes to the respondent ($email).";
$teamCard['ctaButton'] = $uk ? 'Написати клієнту' : 'Email the respondent';
$teamCard['ctaUrl'] = 'mailto:' . $email;
$teamCard['answersTitle'] = $uk ? 'Відповіді' : 'Answers';
$teamCard['sign'] = $uk ? 'Копія для команди WAAI' : 'Copy for the WAAI team';
$subject = $enc("WAAI assessment: L$lv · $company · $name");
$teamHtml = report_email_html($teamCard);
if (!mx_send(ASSESS_TEAM, $subject, $teamBody, $email, $teamHtml)) {
  $GLOBALS['mail_from_name'] = 'WAAI website';
  @local_send(FROM, $subject, '[Not delivered to ' . ASSESS_TEAM . ': ' . $GLOBALS['smtp_err'] . "]\n\n" . $teamBody, '', $email);
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
