<?php

namespace backend\modules\api\controllers;

use backend\modules\api\components\RestController;
use Yii;

class AnalyticsController extends RestController
{
    public function actionSummary(): array
    {
        $db = Yii::$app->db;

        return [
            'data' => [
                'total_customers' => (int) $db->createCommand('SELECT COUNT(*) FROM {{%customer}}')->queryScalar(),
                'total_bookings' => (int) $db->createCommand('SELECT COUNT(*) FROM {{%booking}}')->queryScalar(),
                'completed_bookings' => (int) $db->createCommand("SELECT COUNT(*) FROM {{%booking}} WHERE status = 'COMPLETED'")->queryScalar(),
                'total_revenue' => (float) $db->createCommand("SELECT COALESCE(SUM(service_amount), 0) FROM {{%booking}} WHERE status = 'COMPLETED'")->queryScalar(),
                'total_points_earned' => (int) $db->createCommand("SELECT COALESCE(SUM(points), 0) FROM {{%point_transaction}} WHERE transaction_type = 'EARN'")->queryScalar(),
                'total_points_redeemed' => abs((int) $db->createCommand("SELECT COALESCE(SUM(points), 0) FROM {{%point_transaction}} WHERE transaction_type = 'REDEEM'")->queryScalar()),
            ],
        ];
    }

    public function actionTierDistribution(): array
    {
        $rows = Yii::$app->db->createCommand("
            SELECT tr.code, tr.name, tr.priority_order, COUNT(la.id) AS customer_count
            FROM {{%tier_rule}} tr
            LEFT JOIN {{%loyalty_account}} la ON la.tier_rule_id = tr.id
            GROUP BY tr.id, tr.code, tr.name, tr.priority_order
            ORDER BY tr.priority_order ASC
        ")->queryAll();

        return ['data' => $rows];
    }

    public function actionBookingByHour(): array
    {
        $rows = Yii::$app->db->createCommand("
            SELECT HOUR(FROM_UNIXTIME(scheduled_at)) AS hour_of_day,
                   COUNT(*) AS booking_count,
                   SUM(CASE WHEN status = 'COMPLETED' THEN service_amount ELSE 0 END) AS revenue
            FROM {{%booking}}
            GROUP BY HOUR(FROM_UNIXTIME(scheduled_at))
            ORDER BY hour_of_day ASC
        ")->queryAll();

        return ['data' => $rows];
    }

    public function actionRetention(): array
    {
        $db = Yii::$app->db;
        $totalCustomers = (int) $db->createCommand('SELECT COUNT(*) FROM {{%customer}}')->queryScalar();
        $repeatCustomers = (int) $db->createCommand("
            SELECT COUNT(*) FROM (
                SELECT customer_id
                FROM {{%booking}}
                WHERE status = 'COMPLETED'
                GROUP BY customer_id
                HAVING COUNT(*) >= 2
            ) repeaters
        ")->queryScalar();

        return [
            'data' => [
                'total_customers' => $totalCustomers,
                'repeat_customers' => $repeatCustomers,
                'retention_rate' => $totalCustomers > 0 ? round($repeatCustomers / $totalCustomers * 100, 2) : 0,
            ],
        ];
    }
}
