<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

const MAX_NIGHTS = 30;
const NAME_ALLOWED = '/^[А-Яа-яЁё .\-]+$/u';
const NAME_HAS_LETTER = '/[А-Яа-яЁё]/u';
const PHONE_FORMAT = '/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/';

/** Строгий разбор даты Y-m-d: несуществующие даты (31.02) дают null. */
function parse_date(string $value): ?DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if ($date === false || $date->format('Y-m-d') !== $value) {
        return null;
    }
    return $date;
}

function text_input(array $input, string $key): string
{
    $raw = $input[$key] ?? '';
    return is_string($raw) ? trim($raw) : '';
}

/** Возвращает [очищенные данные, ошибки по полям]. */
function validate_booking(array $input, DateTimeImmutable $today): array
{
    $data = [];
    $errors = [];

    $names = ['first_name' => ['Имя', 'имя'], 'last_name' => ['Фамилия', 'фамилию']];
    foreach ($names as $key => $labels) {
        $value = text_input($input, $key);
        $data[$key] = $value;
        if ($value === '') {
            $errors[$key] = 'Введите ' . $labels[1] . '.';
        } elseif (mb_strlen($value) > 50) {
            $errors[$key] = $labels[0] . ': не больше 50 символов.';
        } elseif (!preg_match(NAME_ALLOWED, $value) || !preg_match(NAME_HAS_LETTER, $value)) {
            $errors[$key] = $labels[0] . ': допустимы кириллица, пробелы, точки и тире.';
        }
    }

    $phone = text_input($input, 'phone');
    $data['phone'] = $phone;
    if ($phone === '') {
        $errors['phone'] = 'Введите номер телефона.';
    } elseif (!preg_match(PHONE_FORMAT, $phone)) {
        $errors['phone'] = 'Телефон должен быть в формате +7(XXX)XXX-XX-XX.';
    }

    $email = text_input($input, 'email');
    $data['email'] = $email;
    if ($email === '') {
        $errors['email'] = 'Введите email.';
    } elseif (mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите email в формате name@example.ru.';
    }

    $inRaw = text_input($input, 'check_in');
    $outRaw = text_input($input, 'check_out');
    $data['check_in'] = $inRaw;
    $data['check_out'] = $outRaw;
    $checkIn = $inRaw === '' ? null : parse_date($inRaw);
    $checkOut = $outRaw === '' ? null : parse_date($outRaw);

    if ($inRaw === '') {
        $errors['check_in'] = 'Введите дату заезда.';
    } elseif ($checkIn === null) {
        $errors['check_in'] = 'Дата заезда указана неверно.';
    } elseif ($checkIn < $today) {
        $errors['check_in'] = 'Дата заезда не может быть в прошлом.';
    } elseif ($checkIn > $today->modify('+2 years')) {
        $errors['check_in'] = 'Бронирование доступно не дальше чем на 2 года вперёд.';
    }

    if ($outRaw === '') {
        $errors['check_out'] = 'Введите дату выезда.';
    } elseif ($checkOut === null) {
        $errors['check_out'] = 'Дата выезда указана неверно.';
    } elseif ($checkIn !== null && $checkOut <= $checkIn) {
        $errors['check_out'] = 'Дата выезда должна быть позже даты заезда.';
    } elseif ($checkIn !== null && (int) $checkIn->diff($checkOut)->days > MAX_NIGHTS) {
        $errors['check_out'] = 'Срок проживания не больше ' . MAX_NIGHTS . ' суток.';
    }

    return [$data, $errors];
}

function input_class(array $errors, string $key): string
{
    return 'form-control' . (isset($errors[$key]) ? ' is-invalid' : '');
}

/* Выбранный номер */
$roomId = filter_var($_GET['room'] ?? null, FILTER_VALIDATE_INT);
if ($roomId === false || $roomId === null || $roomId < 1) {
    show_error('Номер не выбран. Выберите номер в каталоге.', 404);
}
$stmt = db()->prepare(
    'SELECT r.id, r.number, r.price, c.name AS category
     FROM rooms r JOIN categories c ON c.id = r.category_id
     WHERE r.id = ?'
);
$stmt->execute([$roomId]);
$room = $stmt->fetch();
if (!$room) {
    show_error('Такого номера нет. Выберите номер в каталоге.', 404);
}

$old = ['first_name' => '', 'last_name' => '', 'phone' => '', 'email' => '', 'check_in' => '', 'check_out' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('danger', 'Сессия устарела. Обновите страницу и отправьте форму ещё раз.');
        redirect('order.php?room=' . $roomId);
    }

    $today = new DateTimeImmutable('today');
    [$old, $errors] = validate_booking($_POST, $today);

    if (!$errors && room_is_busy((int) $room['id'], $old['check_in'], $old['check_out'])) {
        $errors['check_in'] = 'Номер уже занят на эти даты. Выберите другие даты или другой номер.';
    }

    if (!$errors) {
        $insert = db()->prepare(
            'INSERT INTO bookings (room_id, first_name, last_name, phone, email, check_in, check_out, status_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([
            $room['id'], $old['first_name'], $old['last_name'], $old['phone'],
            $old['email'], $old['check_in'], $old['check_out'], STATUS_NEW,
        ]);
        flash('success', 'Заявка успешно отправлена!');
        redirect('order.php?room=' . $roomId);
    }

    flash('danger', 'Заявка не отправлена. Исправьте ошибки в форме.');
}

$pageTitle = 'Бронирование номера';
$withMask = true;
require __DIR__ . '/partials/header.php';
?>
<!--Форма бронирования-->
<main>
    <div class="d-flex justify-content-between flex-wrap align-items-center">
        <h1>Бронирование номера</h1>
    </div>
    <p class="lead">
        Номер <?= e($room['number']) ?>, <?= e(mb_strtolower($room['category'])) ?>,
        <?= e(format_price($room['price'])) ?> / сутки
    </p>

    <form class="row g-3 needs-validation my-2" novalidate method="post"
          action="order.php?room=<?= (int) $room['id'] ?>">
        <?= csrf_field() ?>
        <div class="col-md-4">
            <label for="validationCustom01" class="form-label">Имя</label>
            <input type="text" class="<?= input_class($errors, 'first_name') ?>" id="validationCustom01"
                   name="first_name" maxlength="50" value="<?= e($old['first_name']) ?>" required>
            <div class="invalid-feedback"><?= e($errors['first_name'] ?? '') ?></div>
        </div>
        <div class="col-md-4">
            <label for="validationCustom02" class="form-label">Фамилия</label>
            <input type="text" class="<?= input_class($errors, 'last_name') ?>" id="validationCustom02"
                   name="last_name" maxlength="50" value="<?= e($old['last_name']) ?>" required>
            <div class="invalid-feedback"><?= e($errors['last_name'] ?? '') ?></div>
        </div>
        <div class="col-md-4">
            <label for="validationCustomPhone" class="form-label">Телефон</label>
            <input type="text" class="<?= input_class($errors, 'phone') ?>" id="validationCustomPhone"
                   name="phone" maxlength="16" value="<?= e($old['phone']) ?>"
                   aria-describedby="inputGroupPrepend" required>
            <div class="invalid-feedback"><?= e($errors['phone'] ?? '') ?></div>
        </div>
        <div class="col-md-6">
            <label for="validationCustom03" class="form-label">Почта</label>
            <input type="text" class="<?= input_class($errors, 'email') ?>" id="validationCustom03"
                   name="email" maxlength="100" value="<?= e($old['email']) ?>" required>
            <div class="invalid-feedback"><?= e($errors['email'] ?? '') ?></div>
        </div>
        <div class="col-md-3">
            <label for="validationCustom04" class="form-label">Дата заезда</label>
            <input type="date" class="<?= input_class($errors, 'check_in') ?>" id="validationCustom04"
                   name="check_in" value="<?= e($old['check_in']) ?>" required>
            <div class="invalid-feedback"><?= e($errors['check_in'] ?? '') ?></div>
        </div>
        <div class="col-md-3">
            <label for="validationCustom05" class="form-label">Дата выезда</label>
            <input type="date" class="<?= input_class($errors, 'check_out') ?>" id="validationCustom05"
                   name="check_out" value="<?= e($old['check_out']) ?>" required>
            <div class="invalid-feedback"><?= e($errors['check_out'] ?? '') ?></div>
        </div>
        <div class="d-grid gap-2">
            <button class="btn btn-primary" type="submit">Отправить заявку</button>
        </div>
    </form>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
