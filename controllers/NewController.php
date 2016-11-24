<?php

namespace app\controllers;

use app\models\SafeExt;
use Yii;
use yii\web\Controller;
use app\models\NewForm;
use app\models\SafeList;

class NewController extends Controller
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

    public function actionNew()
    {
        if (!Yii::$app->session['staff_no']) {
            Yii::$app->session['url'] = Yii::$app->request->url;
            return $this->redirect('/site/login');
        }

        $model = new NewForm();
        $params = Yii::$app->request->post();

        if (!empty($params)) {
            $sql_add = "select COUNT(1) num from safe_ext where DATE_FORMAT(create_at,'%Y-%m-%d') =CURDATE() and user_id=".Yii::$app->session['user_id'];
            $add_num = Yii::$app->db->createCommand($sql_add)->queryAll();
            if($add_num[0]['num']>=3){
                echo "<script>alert('当日申请次数过多，请明日再试！')</script>";
            }else{
                $safe_id = $this->insertSafeList($params['NewForm']);
                $params_ext = array('safe_id' => $safe_id, 'user_id' => Yii::$app->session['user_id'], 'user_mail' => Yii::$app->session['email']);
                $this->insertSafeExt($params_ext);
                Yii::$app->session->setFlash('newFormSubmitted');
                Yii::$app->session['safe_id'] = $safe_id;

                return $this->refresh();
            }
        }

        $model->is_mail = 1;
        $model->tool = 1;

        return $this->render('new', [
            'model' => $model,
        ]);
    }

    public function actionEdit($id)
    {
        if (!Yii::$app->session['staff_no']) {
            Yii::$app->session['url'] = Yii::$app->request->url;
            return $this->redirect('/site/login');
        }

        $details = SafeList::findOne($id);
        $details_ext = SafeExt::find()->where(['safe_id' => $details->id])->one();

        if ($details_ext->user_id != Yii::$app->session['user_id'] || $details->status != 1) {
            Yii::$app->session->setFlash('permission');
        }

        $params = Yii::$app->request->post();

        if (!empty($params)) {
            $this->updateSafeList($params['SafeList']);
            Yii::$app->session->setFlash('editFormSubmitted');
            return $this->refresh();
        }

        $model = $edit_info = SafeList::findOne($id);

        return $this->render('edit', [
            'model' => $model,
        ]);
    }

    public function actionView($id)
    {
        $safe_info = $this->getSafeInfo($id);
        return $this->render('view', [
            'safe_info' => $safe_info,
        ]);
    }


    public function insertSafeList($params)
    {
        $safelist = new SafeList();
        $date = date('Y-m-d H:i:s');
        $safelist->url = $params['url'];
        $safelist->profile = $params['profile'];
        $safelist->login_username = $params['login_username'];
        $safelist->login_password = $params['login_password'];
        $safelist->mode = $params['mode'];
        $safelist->is_mail = $params['is_mail'];
        $safelist->tool = $params['tool'];
        $safelist->create_at = $date;
        $safelist->save();

        return $safelist->attributes['id'];
    }

    public function updateSafeList($params)
    {
        $safe_info = SafeList::findOne($params['id']);
        $date = date('Y-m-d H:i:s');

        $safe_info->url = $params['url'];
        $safe_info->profile = $params['profile'];
        $safe_info->login_username = $params['login_username'];
        $safe_info->login_password = $params['login_password'];
        $safe_info->mode = $params['mode'];
        $safe_info->is_mail = $params['is_mail'];
        $safe_info->tool = $params['tool'];
        $safe_info->update_at = $date;

        $safe_info->save();
    }

    public function insertSafeExt($params)
    {
        $safeext = new SafeExt();

        $date = date('Y-m-d H:i:s');
        $safeext->safe_id = $params['safe_id'];
        $safeext->user_id = $params['user_id'];
        $safeext->user_mail = $params['user_mail'];
        $safeext->create_at = $date;

        $safeext->save();
    }

    public function getSafeInfo($id)
    {
        $connection = Yii::$app->db;
        $sql = "select a.*,b.user_id,b.user_mail,c.chinese_name from safe_list a,safe_ext b,`user` c where a.id=b.safe_id and b.user_id=c.id";

        if (!empty($id)) {
            $sql_ext = " and a.id =" . $id;
            $sql = $sql . $sql_ext;
            $safe_info = $connection->createCommand($sql)->queryAll();
        }
        return $safe_info ? $safe_info : '';
    }

}
