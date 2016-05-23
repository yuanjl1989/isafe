<?php
/**
 * Created by PhpStorm.
 * User: Metersbonwe
 * Date: 2016/5/16
 * Time: 15:43
 */
namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\SafeList;
use app\models\SafeExt;
use app\models\User;

class ListController extends Controller
{
    public $enableCsrfValidation = false;
    
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


    public function actionList()
    {
        $params = $p = Yii::$app->request->post();

        $status_arr = array(1=>'新建',2=>'进行中',3=>'已取消',4=>'已完成');

        if($params){
            if(key_exists('status_1',$params) || key_exists('status_2',$params) || key_exists('status_3',$params) || key_exists('status_4',$params)){
                foreach ($params as $k=>$v){
                    if(substr($k,0,6) == 'status'){
                        $status[] = $v;
                        unset($params[$k]);
                    }
                }
                $params['status'] = $status;
            }
        }

        $conditions = $params?$params:array();

        $list_info = $this->getListInfo($conditions);


        return $this->render('list',['list_info'=>$list_info,'status_arr'=>$status_arr,'params'=>$p]);
    }

    public function getListInfo($conditions = array(),$id=0)
    {
        if(!empty($conditions['username']) && empty($conditions['status']))
        {
            $user_info = User::find()->where(['chinese_name'=>$conditions['username']])->asArray()->one();
            if($user_info){
                $safe_ext_info = SafeExt::find()->where(['user_id'=>$user_info['id']])->asArray()->all();
                if($safe_ext_info){
                    foreach ($safe_ext_info as $item){
                        $safe_id_arr[] = "'".$item['safe_id']."'";
                    }
                    $safe_ids = implode(',',$safe_id_arr);
                }
            }
        }

        if(empty($conditions['username']) && !empty($conditions['status']))
        {
            $safe_info = SafeList::find()->where(['in','status',$conditions['status']])->asArray()->all();
            if($safe_info){
                foreach ($safe_info as $item){
                    $safe_id_arr[] = "'".$item['id']."'";
                }
                $safe_ids = implode(',',$safe_id_arr);
            }

        }

        if(!empty($conditions['username']) && !empty($conditions['status']))
        {
            $user_info = User::find()->where(['chinese_name'=>$conditions['username']])->asArray()->one();
            if($user_info){
                $safe_ext_info = SafeExt::find()->where(['user_id'=>$user_info['id']])->asArray()->all();
                if($safe_ext_info){
                    foreach ($safe_ext_info as $item){
                        $safe_id_arr1[] = "'".$item['safe_id']."'";
                    }
                }
            }

            $safe_info = SafeList::find()->where(['in','status',$conditions['status']])->asArray()->all();
            if($safe_info){
                foreach ($safe_info as $item){
                    $safe_id_arr2[] = "'".$item['id']."'";
                }
            }

            if(isset($safe_id_arr1) && isset($safe_id_arr2)){
                $safe_id_arr = array_intersect($safe_id_arr1,$safe_id_arr2);
                $safe_ids = implode(',',$safe_id_arr);
            }
        }

        if(empty($conditions['username']) && empty($conditions['status']))
        {
            $safe_ids = '';
        }

        $connection = Yii::$app->db;
        $sql = "select a.*,c.chinese_name from safe_list a,safe_ext b,`user` c where a.id=b.safe_id and b.user_id=c.id";

        if(isset($safe_ids)){
            if(!empty($safe_ids)){
                $sql_ext = " and a.id in (".$safe_ids.")";
                $sql = $sql.$sql_ext;
                $list_info = $connection->createCommand($sql." order by a.id desc")->queryAll();
            }else{
                $list_info = $connection->createCommand($sql." order by a.id desc")->queryAll();
            }
        }else{
            $list_info = array();
        }

        if(!empty($id)){
            $sql_ext = " and a.id =".$safe_ids;
            $sql = $sql.$sql_ext;
            $list_info = $connection->createCommand($sql." order by a.id desc")->queryAll();
        }

        return $list_info;
    }

}