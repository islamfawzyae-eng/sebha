<?php
// أنس توك — إرسال Web Push من غير مكتبات (VAPID + تشفير aes128gcm حسب RFC 8291 / RFC 8292)
if (!defined('ANASTALK_PUSH')) { http_response_code(403); exit; }

const PUSH_DIR = __DIR__ . '/../data/push';
const SUBS_DIR = PUSH_DIR . '/subs';
const VAPID_SUB = 'https://anastalk.com';

function b64u_enc(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function b64u_dec(string $s): string { return base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4)); }

function ec_new() {
    return openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
}
function ec_point($key): string {
    $d = openssl_pkey_get_details($key)['ec'];
    return "\x04" . str_pad($d['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['y'], 32, "\0", STR_PAD_LEFT);
}
function ec_pub_from_point(string $point) {
    $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $point;
    $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    return openssl_pkey_get_public($pem);
}

// مفاتيح VAPID بتتعمل مرة واحدة على السيرفر وتتحفظ في data/ (مش في الريبو)
function vapid(): array {
    $f = PUSH_DIR . '/vapid.json';
    if (is_file($f)) return json_decode(file_get_contents($f), true);
    if (!is_dir(SUBS_DIR)) mkdir(SUBS_DIR, 0755, true);
    $k = ec_new();
    openssl_pkey_export($k, $pem);
    $v = ['private_pem' => $pem, 'public' => b64u_enc(ec_point($k))];
    file_put_contents($f, json_encode($v), LOCK_EX);
    @chmod($f, 0600);
    return $v;
}

function der_to_raw(string $der): string {
    // ECDSA DER (SEQUENCE{INTEGER r, INTEGER s}) -> r||s (64 بايت)
    $off = 2; if (ord($der[1]) & 0x80) $off += ord($der[1]) & 0x7f;
    $out = '';
    for ($i = 0; $i < 2; $i++) {
        $len = ord($der[$off + 1]);
        $int = substr($der, $off + 2, $len);
        $out .= str_pad(ltrim($int, "\0"), 32, "\0", STR_PAD_LEFT);
        $off += 2 + $len;
    }
    return $out;
}

function vapid_header(string $endpoint): string {
    $v = vapid();
    $u = parse_url($endpoint);
    $aud = $u['scheme'] . '://' . $u['host'] . (isset($u['port']) ? ':' . $u['port'] : '');
    $h = b64u_enc(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $c = b64u_enc(json_encode(['aud' => $aud, 'exp' => time() + 12 * 3600, 'sub' => VAPID_SUB]));
    openssl_sign("$h.$c", $sig, openssl_pkey_get_private($v['private_pem']), OPENSSL_ALGO_SHA256);
    return 'vapid t=' . "$h.$c." . b64u_enc(der_to_raw($sig)) . ', k=' . $v['public'];
}

function encrypt_payload(string $payload, string $p256dh_b64, string $auth_b64): string {
    $ua_pub = b64u_dec($p256dh_b64);
    $auth = b64u_dec($auth_b64);
    $eph = ec_new();
    $as_pub = ec_point($eph);
    $ecdh = openssl_pkey_derive(ec_pub_from_point($ua_pub), $eph, 32);
    $ikm = hash_hkdf('sha256', $ecdh, 32, "WebPush: info\0" . $ua_pub . $as_pub, $auth);
    $salt = random_bytes(16);
    $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
    $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);
    $ct = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
    return $salt . pack('N', 4096) . chr(65) . $as_pub . $ct . $tag;
}

/** يبعت إشعار لاشتراك واحد. يرجّع كود HTTP (201 = تمام، 404/410 = الاشتراك انتهى) */
function push_send(array $sub, array $msg, int $ttl = 3600): int {
    $body = encrypt_payload(json_encode($msg, JSON_UNESCAPED_UNICODE), $sub['keys']['p256dh'], $sub['keys']['auth']);
    $ch = curl_init($sub['endpoint']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . vapid_header($sub['endpoint']),
            'Content-Encoding: aes128gcm',
            'Content-Type: application/octet-stream',
            'TTL: ' . $ttl,
            'Urgency: normal',
        ],
    ]);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code;
}

function sub_file(string $endpoint): string { return SUBS_DIR . '/' . substr(hash('sha256', $endpoint), 0, 32) . '.json'; }

function reminder_message(): array {
    $lines = [
        'أستغفر الله العظيم وأتوب إليه 🤍',
        'دقيقة استغفار تغيّر يومك… دوس وكمّل سبحتك',
        '﴿اسْتَغْفِرُوا رَبَّكُمْ إِنَّهُ كَانَ غَفَّارًا﴾',
        'قول معانا: أستغفر الله… 100 مرة',
        'اللهم اغفر لنا ولوالدينا وللمسلمين أجمعين',
    ];
    return ['title' => 'أنس توك · وقت الاستغفار', 'body' => $lines[array_rand($lines)], 'url' => './?source=push'];
}
