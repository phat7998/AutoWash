<?php

$this->title = 'AutoWash Booking';
?>
<h1>AutoWash Booking</h1>
<p>Website dat lich ban dau cho khach hang. Cua so dat lich theo hang thanh vien:</p>
<ul>
    <?php foreach ($bookingWindow as $tier => $days): ?>
        <li><?= $tier ?>: <?= $days ?> ngay</li>
    <?php endforeach; ?>
</ul>

