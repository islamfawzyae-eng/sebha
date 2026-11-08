<?php
// تشخيص الإشعارات — CLI بس
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
define('ANASTALK_PUSH', 1);
require __DIR__ . '/lib.php';
echo "php ", PHP_VERSION, " openssl ", OPENSSL_VERSION_TEXT, " curl ", (function_exists('curl_init')?'yes':'no'), "\n";
echo "push dir: ", (is_dir(PUSH_DIR)?'yes':'no'), " vapid: ", (is_file(PUSH_DIR.'/vapid.json')?'yes':'no'), "\n";
$subs = glob(SUBS_DIR . '/*.json') ?: [];
echo "subs: ", count($subs), "\n";
foreach ($subs as $f) {
    $s = json_decode(file_get_contents($f), true);
    $host = parse_url($s['endpoint'], PHP_URL_HOST);
    try {
        $body = encrypt_payload(json_encode(reminder_message(), JSON_UNESCAPED_UNICODE), $s['keys']['p256dh'], $s['keys']['auth']);
        $ch = curl_init($s['endpoint']);
        curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$body, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15,
            CURLOPT_HTTPHEADER=>['Authorization: '.vapid_header($s['endpoint']),'Content-Encoding: aes128gcm','Content-Type: application/octet-stream','TTL: 3600','Urgency: high']]);
        $resp = curl_exec($ch);
        echo basename($f), " ", $host, " tz=", $s['tz'], " -> HTTP ", curl_getinfo($ch, CURLINFO_HTTP_CODE), " ", curl_error($ch), " ", substr((string)$resp, 0, 300), "\n";
    } catch (Throwable $e) { echo basename($f), " ", $host, " ERROR ", $e->getMessage(), "\n"; }
}
foreach (array_slice(array_reverse(file_exists(__DIR__.'/../data/push/log.txt')?file(__DIR__.'/../data/push/log.txt'):[]),0,10) as $l) echo "log: ", $l;
