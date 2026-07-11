<?php

namespace customer\controllers;

use customer\components\RestController;
use common\models\Booking;
use common\models\Customer;
use common\models\Vehicle;
use common\services\LoyaltyService;
use Yii;

class BookingController extends RestController
{
    public function actionIndex(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            Yii::$app->response->statusCode = 401;
            return ['message' => 'Unauthorized'];
        }

        $customer = Customer::findOne(['user_id' => $user->getId()]);
        if (!$customer) {
            return ['data' => []];
        }

        $bookings = Booking::find()->where(['customer_id' => $customer->id])->orderBy(['scheduled_at' => SORT_DESC])->all();

        return [
            'data' => array_map(function($b) {
                return [
                    'id' => $b->id,
                    'booking_code' => $b->booking_code,
                    'scheduled_at' => $b->scheduled_at,
                    'status' => $b->status,
                    'service_amount' => $b->service_amount,
                    'reward_point_earned' => $b->reward_point_earned,
                    'reward_point_redeemed' => $b->reward_point_redeemed,
                ];
            }, $bookings)
        ];
    }

    public function actionCreate(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            Yii::$app->response->statusCode = 401;
            return ['message' => 'Unauthorized'];
        }

        $customer = Customer::find()->where(['user_id' => $user->getId()])->with('loyaltyAccount.tierRule')->one();
        if (!$customer || !$customer->loyaltyAccount) {
            Yii::$app->response->statusCode = 404;
            return ['message' => 'Không tìm thấy thông tin khách hàng.'];
        }
        
        $post = Yii::$app->request->post();
        $scheduledAt = isset($post['scheduled_at']) ? (int) $post['scheduled_at'] : 0;
        
        // Tier rule booking window
        $tierRule = $customer->loyaltyAccount->tierRule;
        $bookingWindowDays = $tierRule ? (int) $tierRule->booking_window_days : 7; // default 7 days for member

        $currentTime = time();
        $maxAllowedTime = $currentTime + ($bookingWindowDays * 86400);

        if ($scheduledAt <= $currentTime || $scheduledAt > $maxAllowedTime) {
            Yii::$app->response->statusCode = 400;
            return [
                'message' => "Bạn chỉ được đặt trước tối đa {$bookingWindowDays} ngày theo hạng thẻ hiện tại."
            ];
        }

        $vehicleId = isset($post['vehicle_id']) ? (int) $post['vehicle_id'] : null;
        $vehicle = Vehicle::findOne(['id' => $vehicleId, 'customer_id' => $customer->id]);
        if (!$vehicle) {
            Yii::$app->response->statusCode = 400;
            return ['message' => 'Xe không hợp lệ.'];
        }

        $booking = new Booking();
        $booking->customer_id = $customer->id;
        $booking->vehicle_id = $vehicle->id;
        $booking->booking_code = 'AW' . strtoupper(uniqid());
        $booking->scheduled_at = $scheduledAt;
        $booking->status = 'PENDING';
        $booking->service_amount = isset($post['service_amount']) ? (float)$post['service_amount'] : 50000;
        $booking->promotion_id = isset($post['promotion_id']) ? (int) $post['promotion_id'] : null;
        $booking->created_at = time();
        $booking->updated_at = time();

        if ($booking->save()) {
            Yii::$app->response->statusCode = 201;
            return [
                'data' => [
                    'id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'scheduled_at' => $booking->scheduled_at,
                    'status' => $booking->status,
                    'service_amount' => $booking->service_amount,
                    'booking_window_days' => $bookingWindowDays,
                    'tier' => $tierRule ? $tierRule->name : 'Member',
                ],
                'message' => 'Đặt lịch thành công'
            ];
        }

        Yii::$app->response->statusCode = 422;
        return ['message' => 'Đặt lịch thất bại', 'data' => $booking->errors];
    }

    public function actionComplete($id): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            Yii::$app->response->statusCode = 401;
            return ['message' => 'Unauthorized'];
        }

        $customer = Customer::findOne(['user_id' => $user->getId()]);
        $booking = $customer ? Booking::findOne(['id' => (int) $id, 'customer_id' => $customer->id]) : null;
        if (!$booking) {
            Yii::$app->response->statusCode = 404;
            return ['message' => 'Không tìm thấy booking.'];
        }

        if ($booking->status === 'COMPLETED') {
            return ['message' => 'Booking đã hoàn thành trước đó.', 'data' => $booking];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $booking->status = 'COMPLETED';
            $booking->updated_at = time();
            $booking->reward_point_earned = LoyaltyService::calculateEarnedPoints((float) $booking->service_amount);
            if (!$booking->save()) {
                throw new \RuntimeException(json_encode($booking->errors));
            }

            $points = LoyaltyService::earnForCompletedBooking($booking);
            $transaction->commit();

            return [
                'message' => "Hoàn thành booking và cộng {$points} điểm.",
                'data' => $booking,
            ];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::$app->response->statusCode = 500;
            return ['message' => 'Không thể hoàn thành booking: ' . $e->getMessage()];
        }
    }
}
