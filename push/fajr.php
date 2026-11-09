<?php
// أنس توك — بيتشغّل كل دقيقة من الـ cron: يبعت إشعار أذان الفجر في ميعاده لكل مشترك
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
define('ANASTALK_PUSH', 1);
require __DIR__ . '/lib.php';
$now = time(); $sent = 0;
@file_put_contents(PUSH_DIR . '/fajr-heartbeat.txt', date('c'));
foreach (glob(SUBS_DIR . '/*.json') ?: [] as $f) {
    $s = json_decode((string)file_get_contents($f), true);
    if (!$s || empty($s['fajr']) || empty($s['fajr_times'])) continue;
    $due = null;
    foreach ($s['fajr_times'] as $t) if ($t <= $now && $t > $now - 15 * 60 && $t > ($s['fajr_sent'] ?? 0)) $due = $t;
    if (!$due) continue;
    $place = $s['fajr_place'] ?? '';
    $code = push_send($s, ['title' => 'حان الآن موعد أذان الفجر 🕌',
        'body' => 'الصلاة خير من النوم' . ($place ? ' · ' . $place : '') . ' — دوس واسمع الأذان',
        'url' => '/?home=1&adhan=1#fajr', 'tag' => 'fajr', 'fajr' => true], 15 * 60);
    $s['fajr_sent'] = $due;
    $s['fajr_times'] = array_values(array_filter($s['fajr_times'], function ($t) use ($now) { return $t > $now - 3600; }));
    if ($code === 404 || $code === 410) { @unlink($f); continue; }
    file_put_contents($f, json_encode($s, JSON_UNESCAPED_UNICODE), LOCK_EX);
    @file_put_contents(PUSH_DIR . '/log.txt', date('c') . " fajr -> $code\n", FILE_APPEND | LOCK_EX);
    $sent++;
}
