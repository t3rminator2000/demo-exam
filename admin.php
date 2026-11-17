<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

require_admin();

$actions = ['approve', 'delete'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if (!csrf_valid()) {
        flash('danger', 'Сессия устарела. Обновите страницу и повторите действие.');
    } elseif ($id === false || $id === null || !is_string($action) || !in_array($action, $actions, true)) {
        flash('danger', 'Некорректный запрос.');
    } else {
        $stmt = db()->prepare('SELECT id, room_id, check_in, check_out FROM bookings WHERE id = ?');
        $stmt->execute([$id]);
        $booking = $stmt->fetch();

        if (!$booking) {
            flash('danger', 'Заявка не найдена.');
        } elseif ($action === 'approve'
            && room_is_busy((int) $booking['room_id'], $booking['check_in'], $booking['check_out'], (int) $booking['id'])
        ) {
            flash('danger', 'Нельзя одобрить заявку №' . $id . ': номер уже занят на эти даты.');
        } elseif ($action === 'delete') {
            $delete = db()->prepare('DELETE FROM bookings WHERE id = ?');
            $delete->execute([$id]);
            flash('success', 'Заявка №' . $id . ' удалена.');
        } else {
            $update = db()->prepare('UPDATE bookings SET status_id = ? WHERE id = ?');
            $update->execute([STATUS_APPROVED, $id]);
            flash('success', 'Заявка №' . $id . ' одобрена.');
        }
    }
    redirect('admin.php');
}

$bookings = db()->query(
    'SELECT b.id, b.first_name, b.last_name, b.phone, b.email, b.check_in, b.check_out,
            b.status_id, s.name AS status, r.number, c.name AS category
     FROM bookings b
     JOIN statuses s ON s.id = b.status_id
     JOIN rooms r ON r.id = b.room_id
     JOIN categories c ON c.id = r.category_id
     ORDER BY b.id DESC'
)->fetchAll();

$badges = [STATUS_NEW => 'secondary', STATUS_APPROVED => 'success'];

$pageTitle = 'Панель администратора';
require __DIR__ . '/partials/header.php';
?>
<main>
    <div class="d-flex justify-content-between flex-wrap align-items-center">
        <h1>Панель администратора</h1>
    </div>

    <?php if (!$bookings): ?>
        <div class="alert alert-info">Заявок пока нет.</div>
    <?php endif; ?>

    <div class="d-flex justify-content-around flex-wrap align-items-center">
        <?php foreach ($bookings as $booking): ?>
            <div class="card">
                <div class="card-body">
                    <h5>Заявка №<?= (int) $booking['id'] ?>
                        <span class="badge text-bg-<?= e($badges[(int) $booking['status_id']] ?? 'secondary') ?>">
                            <?= e($booking['status']) ?>
                        </span>
                    </h5>
                    <h5>Фамилия: <?= e($booking['last_name']) ?></h5>
                    <h5>Имя: <?= e($booking['first_name']) ?></h5>
                    <h5>Телефон: <?= e($booking['phone']) ?></h5>
                    <h5>Почта: <?= e($booking['email']) ?></h5>
                    <h5>Номер: <?= e($booking['number']) ?>, <?= e(mb_strtolower($booking['category'])) ?></h5>
                    <ul class="list-group">
                        <li class="list-group-item">Дата заезда: <?= e(format_date($booking['check_in'])) ?></li>
                        <li class="list-group-item">Дата выезда: <?= e(format_date($booking['check_out'])) ?></li>
                    </ul>
                </div>
                <div class="d-grid gap-2">
                    <?php if ((int) $booking['status_id'] !== STATUS_APPROVED): ?>
                        <form class="plain d-grid" method="post" action="admin.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                            <button class="btn btn-success" type="submit" name="action" value="approve">Одобрить</button>
                        </form>
                    <?php endif; ?>
                    <form class="plain d-grid" method="post" action="admin.php"
                          onsubmit="return confirm('Удалить заявку №<?= (int) $booking['id'] ?>?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                        <button class="btn btn-danger" type="submit" name="action" value="delete">Удалить</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
