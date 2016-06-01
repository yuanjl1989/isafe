<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\ContactForm */

use yii\helpers\Html;


$this->title = '扫描申请详情';
$this->params['breadcrumbs'][] = $this->title;
$profile = ['1'=>'默认（均检测）','2'=>'AcuSensor传感器','3'=>'SQL盲注','4'=>'跨站点请求伪造','5'=>'目录和文件检查','6'=>'空（不使用任何检测）','7'=>'文件上传','8'=>'谷歌黑客数据库','9'=>'高风险警报','10'=>'网络脚本','11'=>'参数操纵','12'=>'SQL注入','13'=>'文本搜索','14'=>'弱口令','15'=>'Web应用程序','16'=>'跨站脚本攻击'];
$mode = ['1'=>'快速','2'=>'混合','3'=>'广泛'];
$is_mail = ['1'=>'是','2'=>'否'];
$status = ['1'=>'新建','2'=>'进行中','3'=>'已取消','4'=>'已完成'];
?>
<div class="site-contact">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row" style="margin-top: 50px">
        <div class="col-lg-4">
            <div style="margin-bottom: 20px">
                <a class="btn btn-default" href="/list/list" role="button">返回</a>
                <?php if ($safe_info[0]['status'] == 1):?>
                    <a class="btn btn-default" href="/new/edit?id=<?=$safe_info[0]['id']?>" role="button" style="margin-left: 80px">编辑</a>
                <?php endif;?>
            </div>


            <div class="form-group">

                <div class="form-control-static"><label>网站地址：</label><?=$safe_info[0]['url']?></div>
                <div class="form-control-static"><label>扫描配置：</label><?=$profile[$safe_info[0]['profile']]?></div>
                <div class="form-control-static"><label>扫描模式：</label><?=$mode[$safe_info[0]['mode']]?></div>
                <div class="form-control-static"><label>邮件通知：</label><?=$is_mail[$safe_info[0]['is_mail']]?></div>

                <?php if(!empty($safe_info[0]['login_username']) && !empty($safe_info[0]['login_password'])):?>
                <hr/>
                <div class="form-control-static"><label>表单账号：</label><?=$safe_info[0]['login_username']?></div>
                <div class="form-control-static"><label>表单密码：</label><?=$safe_info[0]['login_password']?></div>
                <hr/>
                <?php endif;?>

                <div class="form-control-static"><label>申请人：</label><?=$safe_info[0]['chinese_name']?></div>
                <div class="form-control-static"><label>申请时间：</label><?=$safe_info[0]['create_at']?></div>
                <div class="form-control-static"><label>状态：</label><?=$status[$safe_info[0]['status']]?></div>
            </div>

        </div>
    </div>
</div>
