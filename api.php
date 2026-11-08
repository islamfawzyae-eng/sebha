<?php
// عداد الاستغفار الجماعي — sebha.anastalk.com
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$dir  = __DIR__ . '/data';
$file = $dir . '/total.txt';
if (!is_dir($dir)) { @mkdir($dir, 0755, true); }

function read_total($fp) {
  rewind($fp);
  $v = stream_get_contents($fp);
  return max(0, (int)trim($v));
}

$fp = fopen($file, 'c+');
if (!$fp) { http_response_code(500); echo '{"error":"storage"}'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $raw = file_get_contents('php://input');
  $in  = json_decode($raw, true);
  $n   = is_array($in) && isset($in['n']) ? (int)$in['n'] : (int)($_POST['n'] ?? 0);
  $n   = max(0, min($n, 40));            // أقصى حد لكل طلب

  // حد لكل عنوان IP: 300 في الدقيقة (أكتر من جهاز ممكن يشتركوا في نفس الـ IP على نت الموبايل)
  if ($n > 0) {
    $ipDir = $dir . '/ip';
    if (!is_dir($ipDir)) { @mkdir($ipDir, 0755, true); }
    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'x';
    $ipF  = $ipDir . '/' . substr(hash('sha256', $ip . 'sebha'), 0, 24);
    $min  = (int)floor(time() / 60);
    $used = 0;
    if (is_file($ipF)) {
      [$m, $u] = array_map('intval', explode(':', (string)@file_get_contents($ipF)) + [0, 0]);
      if ($m === $min) $used = $u;
    }
    $n = max(0, min($n, 300 - $used));
    @file_put_contents($ipF, $min . ':' . ($used + $n), LOCK_EX);
    if (mt_rand(1, 500) === 1) {                  // تنظيف قديم أحيانًا
      foreach (glob($ipDir . '/*') as $f) { if (filemtime($f) < time() - 600) @unlink($f); }
    }
  }

  flock($fp, LOCK_EX);
  $total = read_total($fp) + $n;
  ftruncate($fp, 0); rewind($fp); fwrite($fp, (string)$total); fflush($fp);
  flock($fp, LOCK_UN);
} else {
  flock($fp, LOCK_SH);
  $total = read_total($fp);
  flock($fp, LOCK_UN);
}
fclose($fp);
echo json_encode(['total' => $total]);
