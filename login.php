<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

const MAX_ATTEMPTS = 5;
const LOCK_SECONDS = 60;

if (is_admin()) {
    redirect('admin.php');
}

$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $locked = ($_SESSION['login_locked_until'] ?? 0) > time();
    $loginRaw = $_POST['login'] ?? '';
    $passwordRaw = $_POST['password'] ?? '';
    $login = is_string($loginRaw) ? trim($loginRaw) : '';
    $password = is_string($passwordRaw) ? $passwordRaw : '';

    if (!csrf_valid()) {
        flash('danger', 'Сессия устарела. Обновите страницу и повторите вход.');
    } elseif ($locked) {
        flash('danger', 'Слишком много попыток. Повторите вход через минуту.');
    } elseif ($login === '' || $password === '') {
        flash('danger', 'Введите логин и пароль.');
    } else {
        $stmt = db()->prepare('SELECT id, password_hash FROM admins WHERE login = ?');
        $stmt->execute([$login]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            unset($_SESSION['login_failures'], $_SESSION['login_locked_until']);
            redirect('admin.php');
        }

        $_SESSION['login_failures'] = ($_SESSION['login_failures'] ?? 0) + 1;
        if ($_SESSION['login_failures'] >= MAX_ATTEMPTS) {
            $_SESSION['login_locked_until'] = time() + LOCK_SECONDS;
            $_SESSION['login_failures'] = 0;
        }
        flash('danger', 'Неверный логин или пароль.');
    }
}

$pageTitle = 'Вход';
require __DIR__ . '/partials/header.php';
?>
<!--Форма авторизации-->
<main>
    <div class="d-flex justify-content-between flex-wrap align-items-center">
        <h1>Вход</h1>
    </div>

    <form class="my-2" novalidate method="post" action="login.php">
        <?= csrf_field() ?>
        <div class="my-2">
            <label for="username" class="form-label">Логин</label>
            <input type="text" class="form-control" id="username" name="login" maxlength="30"
                   value="<?= e($login) ?>" required>
        </div>
        <div class="my-2">
            <label for="password" class="form-label">Пароль</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="d-grid gap-2">
            <button class="btn btn-primary" type="submit">Войти</button>
        </div>
    </form>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
