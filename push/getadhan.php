<?php
// بينزّل ملف الأذان (Aaqib Azeez · Wikimedia Commons · CC BY-SA 4.0) مرة واحدة على السيرفر
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$dst = __DIR__ . '/../adhan.mp3';
if (is_file($dst) && filesize($dst) > 100000) exit;
$ch = curl_init('https://upload.wikimedia.org/wikipedia/commons/7/7d/The_Adhan_-_Muslim_Call_to_Prayer_-_Aaqib_Azeez.mp3');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 60,
    CURLOPT_USERAGENT => 'AnasTalk/1.0 (https://anastalk.com)']);
$data = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
@file_put_contents(__DIR__ . '/../data/push/log.txt', date('c') . " adhan download -> $code " . strlen((string)$data) . "\n", FILE_APPEND);
if ($code === 200 && strlen($data) > 100000 && substr($data, 0, 3) === 'ID3' || ($code === 200 && strlen($data) > 100000 && (ord($data[0]) === 0xFF))) file_put_contents($dst, $data);
