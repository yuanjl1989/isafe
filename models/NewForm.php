<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class NewForm extends Model
{

    public $id;
    public $url;
    public $profile;
    public $login_username;
    public $login_password;
    public $mode;
    public $is_mail;
    public $tool;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            ['url','url'],
            [['url','profile','mode','is_mail','tool'], 'required'],
        ];
    }
    
}
