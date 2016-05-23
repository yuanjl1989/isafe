<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\ContactForm */

use yii\helpers\Html;


$this->title = '扫描申请详情';
$this->params['breadcrumbs'][] = $this->title;
$profile = ['1'=>'默认（均检测）','2'=>'AcuSensor传感器','3'=>'SQL盲注','4'=>'跨站点请求伪造','5'=>'目录和文件检查','6'=>'空（不使用任何检测）','7'=>'文件上传','8'=>'谷歌黑客数据库','9'=>'高风险警报','10'=>'网络脚本','11'=>'参数操纵','12'=>'SQL注入','13'=>'文本搜索','14'=>'弱口令','15'=>'Web应用程序','16'=>'跨站脚本攻击'];
$mode = ['1'=>'快速','2'=>'混合','3'=>'广泛'];
$is_mail = ['1'=>'开','2'=>'关'];
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
                <label>网站地址</label>
                <div class="form-control-static"><?=$safe_info[0]['url']?></div>
                <label>扫描配置</label>
                <p class="form-control-static"><?=$profile[$safe_info[0]['profile']]?></p>
                <label>扫描模式</label>
                    <p class="form-control-static"><?=$mode[$safe_info[0]['mode']]?></p>

                <label>邮件通知</label>
                    <p class="form-control-static"><?=$is_mail[$safe_info[0]['is_mail']]?></p>

                <?php if($safe_info[0]['login_username']):?>
                <hr/>
                <label>表单认证</label>
                    <p class="form-control-static">账号：<?=$safe_info[0]['login_username']?></p>
                    <p class="form-control-static">密码：<?=$safe_info[0]['login_password']?></p>
                <hr/>
                <?php endif;?>

                <label>申请人</label>
                    <p class="form-control-static"><?=$safe_info[0]['chinese_name']?></p>

                <label>申请时间</label>
                    <p class="form-control-static"><?=$safe_info[0]['create_at']?></p>

                <label>状态</label>
                    <p class="form-control-static"><?=$status[$safe_info[0]['status']]?></p>
            </div>

        </div>
    </div>
</div>
