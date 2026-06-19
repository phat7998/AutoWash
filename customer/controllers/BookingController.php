<?php

namespace customer\controllers;

use customer\components\RestController;
use common\models\Booking;
use common\models\Customer;
use common\models\Vehicle;
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
        
        $post = Yii::$app->request->post();
        $scheduledAt = isset($post['scheduled_at']) ? (int) $post['scheduled_at'] : 0;
        
        // Tier rule booking window
        $tierRule = current(array_filter([$customer->loyaltyAccount->tierRule, null])); // simple safe get
        $bookingWindowDays = $tierRule ? $tierRule->booking_window_days : 7; // default 7 days for member

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
        $booking->created_at = time();
        $booking->updated_at = time();

        if ($booking->save()) {
            Yii::$app->response->statusCode = 201;
            return [
                'data' => clone $booking,
                'message' => 'Đặt lịch thành công'
            ];
        }

        Yii::$app->response->statusCode = 422;
        return ['message' => 'Đặt lịch thất bại', 'data' => $booking->errors];
    }
}
