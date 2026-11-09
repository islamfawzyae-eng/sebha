<?php
/**
 * Plugin Name: Anas Talk Home
 * Description: الصفحة الرئيسية لموقع أنس توك — فيها زرار يدخل على السبحة الإلكترونية.
 * Version: 1.1.0
 * Author: Islam Fawzy
 */
if (!defined('ABSPATH')) exit;

// صفحة صلاة الفجر: anastalk.com/fajr
add_action('init', function () {
    $path = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if ($path !== 'fajr') return;
    status_header(200);
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/fajr.html');
    exit;
}, 0);

add_action('template_redirect', function () {
    if (is_admin() || !(is_front_page() || is_home())) return;
    if (isset($_GET['wp'])) return; // anastalk.com/?wp لعرض ووردبريس العادي
    status_header(200);
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/home.html');
    exit;
}, 0);
