<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\SafeList;

class SiteController extends Controller
{
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionLogin()
    {
        if (Yii::$app->session['staff_no']) {
            return $this->redirect('/');
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    public function actionLogout()
    {
        Yii::$app->session->removeAll();

        return $this->redirect('/site/login');
    }

    public function actionCancel($id)
    {
        if(!Yii::$app->session['staff_no']){
            return $this->redirect('/site/login');
        }

        $safe_info = SafeList::findOne($id);

        $safe_info->status = 3;

        $suss = $safe_info->save();

        echo $suss?'操作已完成':'操作失败，请稍后再试！';

    }

}
