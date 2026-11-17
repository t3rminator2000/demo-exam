<!--Контактная информация-->
<footer class="py-2 my-2">
    <ul class="nav justify-content-between align-items-center">
        <li class="nav-item"><a href="#" class="nav-link text-body-secondary">ул. г.Москва, ул. Ивовая, 48</a></li>
        <li class="nav-item"><a href="#" class="nav-link text-body-secondary">Время работы: Пн-Пт, с 8:00-17:00</a></li>
        <li class="nav-item"><a href="tel:88005553535" class="nav-link text-body-secondary">тел. 8 (800) 555 - 35 -
            35</a></li>
        <li class="nav-item"><a href="mailto:обращения@СладкиеСны.рф" class="nav-link text-body-secondary">Email: обращения@СладкиеСны.рф</a>
        </li>
    </ul>
</footer>
<script src="js/bootstrap.bundle.min.js"></script>
<?php if (!empty($withMask)): ?>
    <script src="js/jquery-3.7.1.slim.min.js"></script>
    <script src="js/jquery.inputmask.min.js"></script>
    <script src="js/index.js"></script>
<?php endif; ?>
<?php foreach ($scripts ?? [] as $script): ?>
    <script src="<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
