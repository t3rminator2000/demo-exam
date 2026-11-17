<?php
declare(strict_types=1);

const STATUS_NEW = 1;
const STATUS_APPROVED = 2;
const STATUS_REJECTED = 3;

$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone']);

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

/** Экранирование вывода в HTML. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Одно подключение к БД на запрос. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $config = require __DIR__ . '/config.php';
        $dsn = getenv('SD_DSN') ?: $config['db']['dsn'];
        $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flash(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['csrf'] ?? '';
    return is_string($sent) && hash_equals(csrf_token(), $sent);
}

function is_admin(): bool
{
    return isset($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin()) {
        flash('warning', 'Войдите как администратор, чтобы открыть эту страницу.');
        redirect('login.php');
    }
}

/** Страница с сообщением об ошибке. */
function show_error(string $message, int $code = 500): void
{
    http_response_code($code);
    $pageTitle = 'Ошибка';
    require __DIR__ . '/partials/header.php';
    echo '<main><div class="alert alert-danger">' . e($message) . '</div>';
    echo '<a class="btn btn-primary" href="index.php">Вернуться в каталог</a></main>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

set_exception_handler(function ($exception): void {
    error_log((string) $exception);
    show_error('Сервис временно недоступен. Попробуйте позже.');
});

function format_price($price): string
{
    return number_format((int) $price, 0, '', ' ') . ' ₽';
}

function format_date(string $iso): string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $iso);
    return $date ? $date->format('d.m.Y') : $iso;
}

/** Номер занят, если есть одобренная заявка с пересекающимися датами. */
function room_is_busy(int $roomId, string $checkIn, string $checkOut, int $exceptBookingId = 0): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM bookings
         WHERE room_id = ? AND status_id = ? AND id <> ? AND check_in < ? AND check_out > ?'
    );
    $stmt->execute([$roomId, STATUS_APPROVED, $exceptBookingId, $checkOut, $checkIn]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Занятые ночи номера: одобренные заявки, которые ещё не закончились.
 * Возвращает список пар [заезд, выезд] в формате Y-m-d. Ночь выезда свободна.
 */
function room_busy_ranges(int $roomId): array
{
    $stmt = db()->prepare(
        'SELECT check_in, check_out FROM bookings
         WHERE room_id = ? AND status_id = ? AND check_out > ?
         ORDER BY check_in'
    );
    $stmt->execute([$roomId, STATUS_APPROVED, date('Y-m-d')]);
    return array_map(
        static fn(array $row): array => [$row['check_in'], $row['check_out']],
        $stmt->fetchAll()
    );
}
