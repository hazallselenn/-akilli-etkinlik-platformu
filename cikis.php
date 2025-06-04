<?php
include 'inc/config.php';

// Tüm session'ları temizle
session_destroy();

// Giriş sayfasına yönlendir
header("Location: giris.php");
exit;
