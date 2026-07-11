<?php

namespace customer\models;

use yii\base\Model;
use common\models\User;
use common\models\Customer;
use common\models\LoyaltyAccount;
use common\models\TierRule;
use common\models\Vehicle;
use Yii;

class RegisterForm extends Model
{
    public $username;
    public $password;
    public $full_name;
    public $phone;
    public $license_plate;
    public $device_token;

    public function rules()
    {
        return [
            [['username', 'password', 'full_name', 'phone'], 'required'],
            [['username'], 'unique', 'targetClass' => User::class, 'targetAttribute' => 'username'],
            [['username', 'full_name', 'phone', 'license_plate', 'device_token'], 'string'],
            [['password'], 'string', 'min' => 6],
        ];
    }

    public function register()
    {
        if (!$this->validate()) {
            return null;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $user = new User();
            $user->username = $this->username;
            $user->password_hash = Yii::$app->security->generatePasswordHash($this->password);
            $user->role = User::ROLE_CUSTOMER;
            $user->access_token = Yii::$app->security->generateRandomString();
            $user->device_token = $this->device_token;
            $user->status = 'ACTIVE';
            $user->created_at = time();
            $user->updated_at = time();

            if (!$user->save()) {
                throw new \Exception('Failed to save user');
            }

            $customer = new Customer();
            $customer->user_id = $user->id;
            $customer->full_name = $this->full_name;
            $customer->phone = $this->phone;
            $customer->license_plate = $this->license_plate;
            $customer->created_at = time();
            $customer->updated_at = time();

            if (!$customer->save()) {
                throw new \Exception('Failed to save customer');
            }

            if (!empty($this->license_plate)) {
                $vehicle = new Vehicle();
                $vehicle->customer_id = $customer->id;
                $vehicle->license_plate = strtoupper(trim($this->license_plate));
                $vehicle->vehicle_type = 'MOTORBIKE';
                $vehicle->brand_name = 'Xe may';
                $vehicle->status = 'ACTIVE';
                $vehicle->created_at = time();
                $vehicle->updated_at = time();

                if (!$vehicle->save()) {
                    throw new \Exception('Failed to save vehicle');
                }
            }

            // Init loyalty account
            $memberTier = TierRule::findOne(['code' => 'MEMBER']);
            $loyalty = new LoyaltyAccount();
            $loyalty->customer_id = $customer->id;
            $loyalty->tier_rule_id = $memberTier ? $memberTier->id : null;
            $loyalty->point_balance = 0;
            $loyalty->lifetime_spend = 0;
            $loyalty->wash_count = 0;
            $loyalty->created_at = time();
            $loyalty->updated_at = time();
            
            if (!$loyalty->save()) {
                throw new \Exception('Failed to save loyalty account');
            }

            $transaction->commit();
            return $user;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Registration error: ' . $e->getMessage());
            return null;
        }
    }
}
