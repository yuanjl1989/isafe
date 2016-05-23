<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\LoginForm;
use app\models\SafeList;
use app\models\SafeExt;

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
        $details_ext = SafeExt::find()->where(['safe_id'=>$safe_info->id])->one();

        if($details_ext->user_id != Yii::$app->session['user_id'])
        {
            echo '你没有权限进行该操作！';
        }
        else
        {
            $safe_info->status = 3;
            $suss = $safe_info->save();
            echo $suss?'操作已完成':'操作失败，请稍后再试！';
        }
    }

}
