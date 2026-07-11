<?php

namespace backend\modules\api\controllers;

use backend\modules\api\components\RestController;
use common\models\Booking;
use common\services\LoyaltyService;
use Yii;

class BookingController extends RestController
{
    public function actionIndex(): array
    {
        return ['data' => Booking::find()->with(['customer', 'vehicle'])->orderBy(['scheduled_at' => SORT_DESC])->all()];
    }

    public function actionComplete(): array
    {
        $id = Yii::$app->request->get('id');
        $booking = Booking::findOne($id);
        if (!$booking) {
            Yii::$app->response->statusCode = 404;
            return ['message' => 'Không tìm thấy'];
        }

        if ($booking->status === 'COMPLETED') {
            Yii::$app->response->statusCode = 400;
            return ['message' => 'Lịch đặt đã được hoàn thành trước đó'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $booking->status = 'COMPLETED';
            $booking->updated_at = time();
            
            // Loyalty rule: 10,000 VND = 1 point.
            $pointsEarned = LoyaltyService::calculateEarnedPoints((float) $booking->service_amount);
            $booking->reward_point_earned = $pointsEarned;

            if (!$booking->save()) {
                throw new \Exception('Failed to update booking status');
            }

            LoyaltyService::earnForCompletedBooking($booking);

            $transaction->commit();
            return ['data' => clone $booking, 'message' => 'Hoàn thành lịch đặt và tích điểm thành công'];
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->response->statusCode = 500;
            return ['message' => 'Có lỗi xảy ra: ' . $e->getMessage()];
        }
    }

    public function actionDelete($id): array
    {
        $booking = Booking::findOne($id);
        if (!$booking) {
            Yii::$app->response->statusCode = 404;
            return ['message' => 'Không tìm thấy'];
        }
        $booking->delete();
        return ['message' => 'Xóa booking thành công'];
    }

    public function actionQueue(): array
    {
        $date = Yii::$app->request->get('date', date('Y-m-d'));
        $start = strtotime($date . ' 00:00:00');
        $end = strtotime($date . ' 23:59:59');

        if (!$start || !$end) {
            Yii::$app->response->statusCode = 400;
            return ['message' => 'Ngày không hợp lệ. Dùng format YYYY-MM-DD.'];
        }

        $bookings = Booking::find()
            ->with(['customer.loyaltyAccount.tierRule', 'vehicle'])
            ->where(['between', 'b.scheduled_at', $start, $end])
            ->andWhere(['not in', 'b.status', ['CANCELLED', 'COMPLETED']])
            ->alias('b')
            ->orderBy(['b.scheduled_at' => SORT_ASC])
            ->all();

        usort($bookings, static function (Booking $a, Booking $b): int {
            $aTier = $a->customer && $a->customer->loyaltyAccount ? $a->customer->loyaltyAccount->tierRule : null;
            $bTier = $b->customer && $b->customer->loyaltyAccount ? $b->customer->loyaltyAccount->tierRule : null;
            $aPriority = (int) ($aTier ? $aTier->priority_order : 0);
            $bPriority = (int) ($bTier ? $bTier->priority_order : 0);
            return $bPriority <=> $aPriority ?: $a->scheduled_at <=> $b->scheduled_at;
        });

        return [
            'data' => array_map(static function (Booking $booking): array {
                $tier = $booking->customer && $booking->customer->loyaltyAccount ? $booking->customer->loyaltyAccount->tierRule : null;
                return [
                    'id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'scheduled_at' => $booking->scheduled_at,
                    'status' => $booking->status,
                    'customer_name' => $booking->customer ? $booking->customer->full_name : 'Khách hàng',
                    'license_plate' => $booking->vehicle ? $booking->vehicle->license_plate : '',
                    'tier' => $tier ? $tier->name : 'Member',
                    'tier_code' => $tier ? $tier->code : 'MEMBER',
                    'priority_score' => $tier ? (int) $tier->priority_order : 0,
                ];
            }, $bookings),
        ];
    }
}
