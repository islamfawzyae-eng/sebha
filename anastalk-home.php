<?php
/**
 * Plugin Name: Anas Talk Home
 * Description: الصفحة الرئيسية لموقع أنس توك — فيها زرار يدخل على السبحة الإلكترونية.
 * Version: 1.0.0
 * Author: Islam Fawzy
 */
if (!defined('ABSPATH')) exit;

add_action('template_redirect', function () {
    if (is_admin() || !(is_front_page() || is_home())) return;
    if (isset($_GET['wp'])) return; // anastalk.com/?wp لعرض ووردبريس العادي
    status_header(200);
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/home.html');
    exit;
}, 0);
