<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$categories = db()->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();
$validIds = array_map('intval', array_column($categories, 'id'));

/* Фильтр по категориям: проверяется на сервере */
$selected = [];
if (isset($_GET['category'])) {
    $raw = is_array($_GET['category']) ? $_GET['category'] : [$_GET['category']];
    foreach ($raw as $value) {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id === false || !in_array($id, $validIds, true)) {
            flash('danger', 'Выбрана несуществующая категория. Фильтр сброшен.');
            $selected = [];
            break;
        }
        $selected[] = $id;
    }
    $selected = array_values(array_unique($selected));
}

$sql = 'SELECT r.id, r.number, r.price, r.image, c.id AS category_id, c.name AS category
        FROM rooms r
        JOIN categories c ON c.id = r.category_id';
$params = [];
if ($selected) {
    $sql .= ' WHERE r.category_id IN (' . implode(',', array_fill(0, count($selected), '?')) . ')';
    $params = $selected;
}
$sql .= ' ORDER BY r.category_id, r.price';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$features = [];
$rows = db()->query(
    'SELECT cf.category_id, f.name
     FROM category_features cf
     JOIN features f ON f.id = cf.feature_id
     ORDER BY cf.category_id, cf.sort_order'
)->fetchAll();
foreach ($rows as $row) {
    $features[(int) $row['category_id']][] = $row['name'];
}

$pageTitle = 'Каталог номеров';
require __DIR__ . '/partials/header.php';
?>
<!--описание номеров-->
<main>
    <div class="d-flex justify-content-between flex-wrap">
        <h1>Каталог номеров</h1>
        <form class="plain dropdown" method="get" action="index.php">
            <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    data-bs-auto-close="outside" aria-expanded="false">
                Категории
            </button>
            <ul class="dropdown-menu">
                <?php foreach ($categories as $category): ?>
                    <li>
                        <label class="dropdown-item">
                            <input class="form-check-input me-2" type="checkbox" name="category[]"
                                   value="<?= (int) $category['id'] ?>"
                                <?= in_array((int) $category['id'], $selected, true) ? 'checked' : '' ?>>
                            <?= e($category['name']) ?>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
            <button class="btn btn-primary my-1" type="submit">Применить</button>
            <a class="btn btn-danger my-1" href="index.php">Сбросить фильтр</a>
        </form>
    </div>

    <?php if (!$rooms): ?>
        <div class="alert alert-info">Номеров выбранной категории нет. Сбросьте фильтр.</div>
    <?php endif; ?>

    <div class="d-flex justify-content-around flex-wrap align-items-center">
        <?php foreach ($rooms as $room): ?>
            <div class="card">
                <img src="img/<?= e(rawurlencode($room['image'])) ?>" class="card-img-top"
                     alt="<?= e($room['category']) ?>">
                <div class="card-body">
                    <h3>Категория: <?= e($room['category']) ?></h3>
                    <h5>Номер: <?= e($room['number']) ?></h5>
                    <h5>Цена: <?= e(format_price($room['price'])) ?> / сутки</h5>
                    <h5>Характеристики:</h5>
                    <ul class="list-group">
                        <?php foreach ($features[(int) $room['category_id']] ?? [] as $feature): ?>
                            <li class="list-group-item"><?= e($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="d-grid gap-2">
                    <a href="order.php?room=<?= (int) $room['id'] ?>" class="btn btn-success">Забронировать</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
