<?php
// أنس توك — يبعت تذكير لكل المشتركين. بيتشغّل من الـ cron بس: php send.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
define('ANASTALK_PUSH', 1);
require __DIR__ . '/lib.php';
$sent = 0; $gone = 0; $fail = 0;
foreach (glob(SUBS_DIR . '/*.json') ?: [] as $f) {
    $s = json_decode((string)file_get_contents($f), true);
    if (!$s || !($s['istighfar'] ?? !isset($s['fajr']))) continue;
    $c = push_send($s, reminder_message());
    if ($c >= 200 && $c < 300) $sent++;
    elseif ($c === 404 || $c === 410) { @unlink($f); $gone++; }
    else $fail++;
}
echo date('c') . " sent=$sent removed=$gone failed=$fail\n";
