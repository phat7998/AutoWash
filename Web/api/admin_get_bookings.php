<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$seed = [
  ['id' => 'BK-1001', 'customer' => 'Nguyễn An', 'vehicle' => 'Sedan', 'package' => 'Rửa nhanh', 'status' => 'pending', 'points' => 120],
  ['id' => 'BK-1002', 'customer' => 'Trần Minh', 'vehicle' => 'SUV', 'package' => 'Rửa cao cấp', 'status' => 'washing', 'points' => 240],
  ['id' => 'BK-1003', 'customer' => 'Lê Hòa', 'vehicle' => 'Xe bán tải', 'package' => 'Rửa + bảo dưỡng', 'status' => 'done', 'points' => 300],
  ['id' => 'BK-1004', 'customer' => 'Phạm Thu', 'vehicle' => 'City', 'package' => 'Rửa vip', 'status' => 'pending', 'points' => 180],
];

if (!isset($_SESSION['admin_bookings'])) {
  $_SESSION['admin_bookings'] = $seed;
}

echo json_encode($_SESSION['admin_bookings']);
