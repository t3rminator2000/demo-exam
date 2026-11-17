<?php
/** @var string $pageTitle */
$alerts = take_flash();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/index.css">
    <title><?= e($pageTitle) ?> - Сладкие сны</title>
</head>
<body class="container">
<header class="d-flex flex-wrap justify-content-center py-3">
    <a href="index.php"
       class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none">
        <span class="fs-4 mx-2 fw-medium">Сладкие сны</span>
    </a>

    <ul class="nav">
        <li class="nav-item"><a href="index.php" class="nav-link">Приезжайте как гости, уезжайте как друзья!</a></li>
        <?php if (is_admin()): ?>
            <li class="nav-item"><a href="admin.php" class="nav-link">Заявки</a></li>
            <li class="nav-item"><a href="logout.php" class="nav-link">Выйти</a></li>
        <?php else: ?>
            <li class="nav-item"><a href="login.php" class="nav-link">Вход</a></li>
        <?php endif; ?>
    </ul>
</header>
<?php foreach ($alerts as $alert): ?>
    <div class="alert alert-<?= e($alert['type']) ?> text-center" role="alert"><?= e($alert['message']) ?></div>
<?php endforeach; ?>
