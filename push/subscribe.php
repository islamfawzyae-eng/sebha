<?php
// أنس توك — تسجيل/إلغاء اشتراك الإشعارات
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

if (($in['action'] ?? '') === 'unsubscribe') { @unlink($f); echo '{"ok":true}'; exit; }

$isNew = !is_file($f);
$rec = ['endpoint' => $sub['endpoint'], 'keys' => ['p256dh' => $sub['keys']['p256dh'], 'auth' => $sub['keys']['auth']],
        'tz' => substr((string)($in['tz'] ?? ''), 0, 64), 'plan' => substr((string)($in['plan'] ?? 'twice'), 0, 16),
        'created' => $isNew ? time() : (json_decode((string)@file_get_contents($f), true)['created'] ?? time())];
file_put_contents($f, json_encode($rec), LOCK_EX);

// إشعار تجربة على طول علشان الشخص يشوف شكله
$code = push_send($rec, reminder_message() + ['tag' => 'welcome']);
@file_put_contents(PUSH_DIR . '/log.txt', date('c') . ' subscribe ' . parse_url($rec['endpoint'], PHP_URL_HOST) . ' -> ' . $code . "\n", FILE_APPEND | LOCK_EX);
echo json_encode(['ok' => true, 'test' => $code]);
