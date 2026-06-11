<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? '';
$status = $input['status'] ?? '';

$seed = [
  ['id' => 'BK-1001', 'customer' => 'Nguyễn An', 'vehicle' => 'Sedan', 'package' => 'Rửa nhanh', 'status' => 'pending', 'points' => 120],
  ['id' => 'BK-1002', 'customer' => 'Trần Minh', 'vehicle' => 'SUV', 'package' => 'Rửa cao cấp', 'status' => 'washing', 'points' => 240],
  ['id' => 'BK-1003', 'customer' => 'Lê Hòa', 'vehicle' => 'Xe bán tải', 'package' => 'Rửa + bảo dưỡng', 'status' => 'done', 'points' => 300],
  ['id' => 'BK-1004', 'customer' => 'Phạm Thu', 'vehicle' => 'City', 'package' => 'Rửa vip', 'status' => 'pending', 'points' => 180],
];

$bookings = $_SESSION['admin_bookings'] ?? $seed;

$updated = false;
foreach ($bookings as &$booking) {
  if ($booking['id'] === $id) {
    $booking['status'] = $status;
    $updated = true;
    break;
  }
}

if (!$updated) {
  echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
  exit;
}

$_SESSION['admin_bookings'] = $bookings;

echo json_encode([
  'success' => true,
  'message' => 'Đã cập nhật trạng thái đơn ' . $id,
  'bookings' => $bookings,
]);
