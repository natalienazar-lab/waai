<?php
// HTML version of the assessment report email, styled like the report card on the site.
// Email-safe: tables, inline styles, web-safe font fallbacks, no scripts or external CSS.

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/**
 * $r keys: lang, lv, levelName, company, means, gaps (array), holdsTitle, top, aiTitle, comment,
 *          answersTitle, answers (array of [question, letter, option]), disc, hi (array of paragraphs),
 *          ctaTitle, ctaText, ctaButton, ctaUrl, sign, siteUrl, levelLabel, meansTitle,
 *          team (optional array of [label, value] rows shown at the top for the WAAI copy)
 */
function report_email_html(array $r): string {
  $ink = '#0F1B2B'; $muted = '#56616F'; $line = '#E3E6EA'; $paper = '#F6F7F4';
  $font = "font-family:'Segoe UI',Helvetica,Arial,sans-serif;";
  $head = "font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-weight:700;color:$ink;";
  $out = '<!doctype html><html lang="' . h($r['lang']) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
    . '<meta name="color-scheme" content="light"><title>' . h($r['levelLabel']) . '</title></head>'
    . '<body style="margin:0;padding:0;background:' . $paper . ';">'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:' . $paper . ';"><tr><td align="center" style="padding:24px 12px;">'
    . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;">';

  // Logo row
  $out .= '<tr><td style="padding:0 4px 14px;' . $head . 'font-size:18px;letter-spacing:2px;">'
    . '<span style="display:inline-block;width:12px;height:12px;border-radius:6px;background:#2F8A64;"></span>'
    . '<span style="display:inline-block;width:12px;height:12px;border-radius:6px;background:#3C69CF;margin-left:-4px;"></span>'
    . '<span style="display:inline-block;width:12px;height:12px;border-radius:6px;background:#D98E1F;margin-left:-4px;margin-right:8px;"></span>WAAI</td></tr>';

  // Team copy: contact details first
  if (!empty($r['team'])) {
    $out .= '<tr><td style="padding:0 0 14px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border:1px solid ' . $line . ';border-radius:12px;">';
    foreach ($r['team'] as [$k, $v]) {
      $out .= '<tr><td style="padding:8px 16px;' . $font . 'font-size:13px;color:' . $muted . ';width:110px;vertical-align:top;">' . h($k) . '</td>'
        . '<td style="padding:8px 16px;' . $font . 'font-size:14px;color:' . $ink . ';">' . $v . '</td></tr>';
    }
    $out .= '</table></td></tr>';
  }

  // Greeting
  if (!empty($r['hi'])) {
    $out .= '<tr><td style="padding:0 4px 16px;' . $font . 'font-size:15px;line-height:1.55;color:' . $ink . ';">';
    foreach ($r['hi'] as $p) $out .= '<p style="margin:0 0 8px;">' . h($p) . '</p>';
    $out .= '</td></tr>';
  }

  // Report card
  $out .= '<tr><td><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border:1px solid ' . $line . ';border-radius:16px;overflow:hidden;">';
  // Dark header with level badge
  $out .= '<tr><td style="background:#0F1B2B;background-image:linear-gradient(135deg,#1B2E52 0%,#0F1B2B 55%,#123B2E 100%);padding:22px 20px;border-radius:16px 16px 0 0;">'
    . '<table role="presentation" cellpadding="0" cellspacing="0"><tr>'
    . '<td style="width:72px;height:72px;border-radius:36px;background:#F4C25B;text-align:center;vertical-align:middle;' . $head . 'font-size:28px;color:#0F1B2B;">L' . (int)$r['lv'] . '</td>'
    . '<td style="padding-left:16px;vertical-align:middle;">'
    . '<div style="' . $font . 'font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:#A5B1BF;">' . h($r['levelLabel']) . ' · ' . h($r['company']) . '</div>'
    . '<div style="' . $head . 'font-size:22px;line-height:1.2;color:#EEF2F6;margin-top:4px;">' . h($r['levelName']) . '</div>'
    . '</td></tr></table></td></tr>';

  $sec = fn($title, $inner) => '<tr><td style="padding:18px 20px 0;"><div style="' . $head . 'font-size:15px;margin:0 0 8px;">' . h($title) . '</div>' . $inner . '</td></tr>';
  $para = fn($t) => '<div style="' . $font . 'font-size:15px;line-height:1.55;color:' . $ink . ';">' . nl2br(h($t)) . '</div>';

  $out .= $sec($r['meansTitle'], $para($r['means']));
  if ($r['gaps']) {
    $g = '';
    foreach ($r['gaps'] as $x) $g .= '<div style="background:#FBF0DE;border-radius:10px;padding:10px 12px;margin:0 0 8px;' . $font . 'font-size:14px;line-height:1.5;color:' . $ink . ';">' . h($x) . '</div>';
    $out .= $sec($r['holdsTitle'], $g);
  } else {
    $out .= $sec($r['ctaTitle'], $para($r['top']));
  }
  if ($r['comment'] !== '') {
    $out .= $sec($r['aiTitle'], '<div style="border-left:3px solid #2F8A64;padding-left:14px;' . $font . 'font-size:15px;line-height:1.55;color:' . $ink . ';">' . nl2br(h($r['comment'])) . '</div>');
  }

  // CTA
  $out .= '<tr><td style="padding:20px 20px 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#E6F1EC;border-radius:14px;"><tr><td style="padding:16px;">'
    . '<div style="' . $head . 'font-size:15px;margin:0 0 6px;">' . h($r['ctaTitle']) . '</div>'
    . '<div style="' . $font . 'font-size:14px;line-height:1.5;color:' . $ink . ';margin:0 0 12px;">' . h($r['ctaText']) . '</div>'
    . '<a href="' . h($r['ctaUrl']) . '" style="display:inline-block;background:#2F8A64;color:#FFFFFF;text-decoration:none;' . $font . 'font-weight:600;font-size:14px;padding:11px 20px;border-radius:999px;">' . h($r['ctaButton']) . '</a>'
    . '</td></tr></table></td></tr>';

  // Answers
  $a = '';
  foreach ($r['answers'] as $i => [$q, $letter, $opt]) {
    $a .= '<tr><td style="padding:0 0 10px;' . $font . 'font-size:13px;line-height:1.45;color:' . $muted . ';">'
      . '<div style="color:' . $ink . ';font-weight:600;">' . ($i + 1) . '. ' . h($q) . '</div>'
      . '<span style="display:inline-block;min-width:18px;text-align:center;border-radius:9px;background:#E4EBF8;color:#3C69CF;font-weight:600;font-size:12px;padding:0 4px;margin-right:6px;">' . h($letter) . '</span>' . h($opt) . '</td></tr>';
  }
  $out .= $sec($r['answersTitle'], '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $a . '</table>');
  $out .= '<tr><td style="padding:6px 20px 20px;' . $font . 'font-size:12px;line-height:1.5;color:' . $muted . ';">' . h($r['disc']) . '</td></tr>';
  $out .= '</table></td></tr>';

  // Footer
  $out .= '<tr><td style="padding:16px 4px 0;' . $font . 'font-size:13px;line-height:1.5;color:' . $muted . ';">' . nl2br(h($r['sign']))
    . '<br><a href="' . h($r['siteUrl']) . '" style="color:#3C69CF;text-decoration:none;">wisely-applied-ai.com</a></td></tr>';
  $out .= '</table></td></tr></table></body></html>';
  return $out;
}
