<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || $user->password_hash !== md5($this->password)) {
                $this->addError($attribute, 'Incorrect username or password.');
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return boolean whether the user is logged in successfully
     */
    public function login()
    {
        $user_info = $this->getUser();
        if ($user_info) {
            if ($this->validate() || $this->auth_login($this->username, $this->password)) {
                Yii::$app->session['staff_no'] = $user_info['staff_no'];
                Yii::$app->session['staff_id'] = $user_info['id'];
                Yii::$app->session['email'] = $user_info['email'];
                if (Yii::$app->session['url']) {
                    Header("Location: " . Yii::$app->session['url']);
                } else {
                    Header("Location: /");
                }
            }
        } elseif ($this->insertUser()) {
            if (Yii::$app->session['url']) {
                Header("Location: " . Yii::$app->session['url']);
            } else {
                Header("Location: /");
            }
        }
        echo "<script>alert('登录失败，账号或密码错误！')</script>";
        return false;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::find()->where(['staff_no' => $this->username])->one();
        }

        return $this->_user;
    }

    private function auth_login($username, $password)
    {
        if (!empty($password) && !empty($password)) {

            $domain = 'mb.com'; //设定域名
            $basedn = 'dc=mb,dc=com'; //如果域名为“b.a.com”,则此处为“dc=b,dc=a,dc=com”

            $ad = @ldap_connect("ldap://192.168.203.11:389/");
            @ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3);
            @ldap_set_option($ad, LDAP_OPT_REFERRALS, 0);
            @ldap_bind($ad, "{$username}@{$domain}", $password);

            $filter = 'name=' . $username;
            $result = @ldap_search($ad, $basedn, $filter);
            $info = @ldap_get_entries($ad, $result);

            return !$result ? 0 : array(1, $info);
        }
    }

    public function insertUser()
    {
        $inputarr = array();
        $auth = $this->auth_login($this->username, $this->password);
        if (is_array($auth)) {
            $userinfo = $auth[1][0];
            $inputarr['chinese_name'] = $userinfo['displayname'][0];
            $inputarr['english_name'] = $userinfo['mailnickname'][0];
            $inputarr['username'] = $userinfo['mail'][0];
            $inputarr['staff_no'] = $userinfo['name'][0];

            $user = new User();
            $date = date('Y-m-d H:i:s');
            $user->email = $inputarr['username'];
            $user->password_hash = '';
            $user->chinese_name = $inputarr['chinese_name'];
            $user->english_name = $inputarr['english_name'];
            $user->staff_no = $inputarr['staff_no'];
            $user->created_at = $date;
            $user->updated_at = $date;
            $user->is_deleted = 0;
            $user->save();

            Yii::$app->session['staff_no'] = $inputarr['staff_no'];
            Yii::$app->session['staff_id'] = $user->attributes['id'];
            Yii::$app->session['email'] = $inputarr['username'];

            return $user->attributes['id'];
        }
        return false;
    }
}
