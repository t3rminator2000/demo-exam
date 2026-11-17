<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

unset($_SESSION['admin_id']);
session_regenerate_id(true);
flash('success', 'Вы вышли из панели администратора.');
redirect('index.php');
