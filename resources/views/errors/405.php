<?php declare(strict_types=1); ?>
<section class="error-state" aria-labelledby="error-title">
    <p class="error-code">405</p>
    <h1 id="error-title"><?= $e($title) ?></h1>
    <p><?= $e($message) ?></p>
    <a class="button button-primary" href="/">Về trang chủ</a>
</section>
