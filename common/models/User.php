<?php

namespace common\models;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    public const ROLE_ADMIN = 'ADMIN';
    public const ROLE_MANAGER = 'MANAGER';
    public const ROLE_CUSTOMER = 'CUSTOMER';

    public static function tableName(): string
    {
        return '{{%user}}';
    }

    public static function findIdentity($id): ?self
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        return static::findOne(['access_token' => $token]);
    }

    public static function findByUsername(string $username): ?self
    {
        return static::findOne(['username' => $username]);
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getAuthKey(): string
    {
        return (string) $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->getAuthKey() === $authKey;
    }

    public function validatePassword(string $password): bool
    {
        return password_verify($password, (string) $this->password_hash);
    }
}

