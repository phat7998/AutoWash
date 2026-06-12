<?php

namespace customer\models;

use yii\base\Model;
use common\models\User;
use Yii;

class LoginForm extends Model
{
    public $username;
    public $password;
    public $device_token;

    private $_user;

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['device_token', 'string'],
            ['password', 'validatePassword'],
        ];
    }

    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Tên đăng nhập hoặc mật khẩu không đúng.');
            }
        }
    }

    public function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::findByUsername($this->username);
        }
        return $this->_user;
    }

    public function login()
    {
        if ($this->validate()) {
            $user = $this->getUser();
            $user->access_token = Yii::$app->security->generateRandomString();
            if ($this->device_token) {
                $user->device_token = $this->device_token;
            }
            return $user->save(false) ? $user : null;
        }
        return null;
    }
}
