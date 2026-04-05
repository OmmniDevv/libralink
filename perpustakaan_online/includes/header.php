<?php
require_once __DIR__ . '/../config/Helper.php';
startSession();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Perpustakaan Online' : 'Perpustakaan Online'; ?></title>
    <link rel="stylesheet" href="/perpustakaan_online/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/perpustakaan_online/assets/css/style.css">
</head>
<body>