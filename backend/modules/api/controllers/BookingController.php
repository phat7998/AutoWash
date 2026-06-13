<?php

namespace backend\modules\api\controllers;

use backend\modules\api\components\RestController;
use common\models\Booking;
use common\models\PointTransaction;
use common\models\LoyaltyAccount;
use Yii;

class BookingController extends RestController
{
    public function actionIndex(): array
    {
        return ['data' => Booking::find()->with(['customer', 'vehicle'])->orderBy(['scheduled_at' => SORT_DESC])->all()];
    }

    public function actionComplete($id): array
    {
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
            
            // Basic point logic: 10% of service amount
            $pointsEarned = (int)($booking->service_amount * 0.1);
            $booking->reward_point_earned = $pointsEarned;

            if (!$booking->save()) {
                throw new \Exception('Failed to update booking status');
            }

            $loyalty = LoyaltyAccount::findOne(['customer_id' => $booking->customer_id]);
            if ($loyalty) {
                // Add points
                $pt = new PointTransaction();
                $pt->loyalty_account_id = $loyalty->id;
                $pt->transaction_type = 'EARN';
                $pt->points = $pointsEarned;
                $pt->available_points = $pointsEarned;
                $pt->reference_id = $booking->id;
                $pt->description = 'Hoàn thành dịch vụ rửa xe: ' . $booking->booking_code;
                $pt->created_at = time();
                $pt->expired_at = strtotime('+1 year'); // expire in 12 months
                
                if (!$pt->save()) {
                    throw new \Exception('Failed to add point transaction');
                }

                // Update loyalty account totals
                $loyalty->point_balance += $pointsEarned;
                $loyalty->wash_count += 1;
                $loyalty->lifetime_spend += $booking->service_amount;
                $loyalty->save(false);
            }

            $transaction->commit();
            return ['data' => clone $booking, 'message' => 'Hoàn thành lịch đặt và tích điểm thành công'];
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->response->statusCode = 500;
            return ['message' => 'Có lỗi xảy ra: ' . $e->getMessage()];
        }
    }
}
