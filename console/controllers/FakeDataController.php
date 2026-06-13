```php
<?php

namespace console\controllers;

use yii\console\Controller;
use Yii;
use common\models\User;
use common\models\Customer;
use common\models\Vehicle;
use common\models\Promotion;

class FakeDataController extends Controller
{
    public function actionGenerate()
    {
        $this->stdout("=== GENERATING FAKE DATA ===\n");

        $time = time();

        /*
         * USERS + CUSTOMERS
         */
        for ($i = 1; $i <= 50; $i++) {

            $username = sprintf('user%03d', $i);

            $user = User::findOne(['username' => $username]);

            if (!$user) {

                $user = new User();
                $user->username = $username;
                $user->password_hash = Yii::$app->security
                    ->generatePasswordHash('123456');

                $user->auth_key = Yii::$app->security->generateRandomString();
                $user->role = 'CUSTOMER';
                $user->status = 'ACTIVE';

                $user->phone = '09' . str_pad((string)$i, 8, '0', STR_PAD_LEFT);
                $user->email = $username . '@gmail.com';

                $user->created_at = $time;
                $user->updated_at = $time;

                if (!$user->save()) {
                    print_r($user->errors);
                    continue;
                }
            }

            $customer = Customer::findOne([
                'user_id' => $user->id
            ]);

            if (!$customer) {

                $customer = new Customer();
                $customer->user_id = $user->id;
                $customer->full_name = 'Customer ' . $i;
                $customer->phone = $user->phone;

                $customer->created_at = $time;
                $customer->updated_at = $time;

                if (!$customer->save()) {
                    print_r($customer->errors);
                    continue;
                }
            }
        }

        $this->stdout("Users + Customers generated\n");

        /*
         * VEHICLES
         */
        $brands = [
            'Honda',
            'Yamaha',
            'Suzuki',
            'VinFast'
        ];

        $types = [
            'MOTORBIKE',
            'SCOOTER',
            'ELECTRIC'
        ];

        $customers = Customer::find()->all();

        foreach ($customers as $customer) {

            $exists = Vehicle::find()
                ->where(['customer_id' => $customer->id])
                ->exists();

            if ($exists) {
                continue;
            }

            $vehicle = new Vehicle();

            $vehicle->customer_id = $customer->id;

            $vehicle->license_plate =
                rand(50, 99)
                . chr(rand(65, 90))
                . '-'
                . rand(10000, 99999);

            $vehicle->vehicle_type =
                $types[array_rand($types)];

            $vehicle->brand_name =
                $brands[array_rand($brands)];

            $vehicle->status = 'ACTIVE';

            $vehicle->created_at = $time;
            $vehicle->updated_at = $time;

            if (!$vehicle->save()) {
                print_r($vehicle->errors);
            }
        }

        $this->stdout("Vehicles generated\n");

        /*
         * PROMOTIONS
         */
        $promotionData = [
            [
                'Welcome 10%',
                'MEMBER'
            ],
            [
                'Silver Bonus',
                'SILVER'
            ],
            [
                'Gold Weekend',
                'GOLD'
            ],
            [
                'Platinum VIP',
                'PLATINUM'
            ],
            [
                'Summer Sale',
                null
            ]
        ];

        foreach ($promotionData as $row) {

            $promotion = Promotion::findOne([
                'name' => $row[0]
            ]);

            if ($promotion) {
                continue;
            }

            $promotion = new Promotion();

            $promotion->name = $row[0];
            $promotion->target_tier = $row[1];

            $promotion->promotion_type = 'DISCOUNT';
            $promotion->value = rand(5, 20);

            $promotion->status = 'ACTIVE';

            $promotion->starts_at = strtotime('-7 days');
            $promotion->ends_at = strtotime('+30 days');

            $promotion->created_at = $time;
            $promotion->updated_at = $time;

            $promotion->save();
        }

        $this->stdout("Promotions generated\n");

        $this->stdout("=== DONE ===\n");
    }
}
```
