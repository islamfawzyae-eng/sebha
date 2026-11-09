<?php
// أنس توك — تسجيل الإشعارات (تذكير الاستغفار + أذان الفجر) على نفس الاشتراك
define('ANASTALK_PUSH', 1);
require __DIR__ . '/lib.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'GET') { echo json_encode(['key' => vapid()['public']]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$in = json_decode(file_get_contents('php://input'), true);
$sub = $in['sub'] ?? null;
if (!is_array($sub) || empty($sub['endpoint']) || empty($sub['keys']['p256dh']) || empty($sub['keys']['auth'])
    || !preg_match('#^https://[a-z0-9.-]+(/|$)#i', $sub['endpoint']) || strlen($sub['endpoint']) > 1024) {
    http_response_code(400); echo '{"error":"bad subscription"}'; exit;
}
vapid();
$f = sub_file($sub['endpoint']);
$old = is_file($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
$was = ['istighfar' => (bool)($old['istighfar'] ?? (!empty($old) && !isset($old['fajr']))), 'fajr' => (bool)($old['fajr'] ?? false)];

// النسخة القديمة من زرار التذكير: action=unsubscribe أو اشتراك من غير set
$set = is_array($in['set'] ?? null) ? $in['set'] : [];
if (($in['action'] ?? '') === 'unsubscribe') $set = ['istighfar' => false];
elseif (!$set) $set = ['istighfar' => true];

$rec = $old + ['created' => time()];
$rec['endpoint'] = $sub['endpoint'];
$rec['keys'] = ['p256dh' => $sub['keys']['p256dh'], 'auth' => $sub['keys']['auth']];
if (!empty($in['tz'])) $rec['tz'] = substr((string)$in['tz'], 0, 64);
$rec['istighfar'] = array_key_exists('istighfar', $set) ? (bool)$set['istighfar'] : $was['istighfar'];
$rec['fajr'] = array_key_exists('fajr', $set) ? (bool)$set['fajr'] : $was['fajr'];

if (isset($in['fajr']['times']) && is_array($in['fajr']['times'])) {
    $now = time();
    $ts = array_values(array_filter(array_map('intval', array_slice($in['fajr']['times'], 0, 40)),
        function ($t) use ($now) { return $t > $now - 3600 && $t < $now + 45 * 86400; }));
    sort($ts);
    $rec['fajr_times'] = $ts;
    $rec['fajr_place'] = mb_substr((string)($in['fajr']['place'] ?? ''), 0, 40);
}
if (!$rec['fajr']) unset($rec['fajr_times']);

if (!$rec['istighfar'] && !$rec['fajr']) {
    @unlink($f);
    echo json_encode(['ok' => true, 'removed' => true]); exit;
}
file_put_contents($f, json_encode($rec, JSON_UNESCAPED_UNICODE), LOCK_EX);

// إشعار تأكيد لما حاجة تتفعّل لأول مرة، علشان الشخص يشوف شكل الإشعار
$test = null;
if (empty($in['quiet'])) {
    if ($rec['fajr'] && !$was['fajr']) {
        $label = (string)($in['fajr']['time_label'] ?? '');
        $place = $rec['fajr_place'] ?? '';
        $test = push_send($rec, ['title' => 'أنس توك · أذان الفجر 🕌',
            'body' => 'تمام ✅ هنبّهك لأذان الفجر' . ($label ? ' الساعة ' . $label : '') . ($place ? ' في ' . $place : '') . ' إن شاء الله',
            'url' => '/fajr/', 'tag' => 'fajr-welcome']);
    } elseif ($rec['istighfar'] && !$was['istighfar']) {
        $test = push_send($rec, reminder_message() + ['tag' => 'welcome']);
    }
    if ($test !== null) @file_put_contents(PUSH_DIR . '/log.txt', date('c') . ' welcome ' . parse_url($rec['endpoint'], PHP_URL_HOST) . ' -> ' . $test . "\n", FILE_APPEND | LOCK_EX);
}
echo json_encode(['ok' => true, 'test' => $test, 'istighfar' => $rec['istighfar'], 'fajr' => $rec['fajr']]);
