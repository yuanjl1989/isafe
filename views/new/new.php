<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\ContactForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = '新增扫描申请';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-contact">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (Yii::$app->session->hasFlash('newFormSubmitted')): ?>

        <div class="alert alert-success">
            扫描申请新增成功，结果会在扫描完成后自动生成。你可以在<a href="/new/edit?id=<?=Yii::$app->session['safe_id']?>">编辑页</a>修改刚添加的申请，也可以进入<a href="/list/list">列表页</a>查看所有添加的申请！
        </div>

    <?php else: ?>

        <div class="row" style="margin-top: 50px">
            <div class="col-lg-4">

                <?php $form = ActiveForm::begin(['id' => 'new-form']); ?>

                <?= $form->field($model, 'url')->textInput(['placeholder'=>'请输入需要扫描的网站地址'])->label('网站地址') ?>
                <?= $form->field($model, 'profile')->dropDownList(['1'=>'默认（均检测）','2'=>'AcuSensor传感器','3'=>'SQL盲注','4'=>'跨站点请求伪造','5'=>'目录和文件检查','6'=>'空（不使用任何检测）','7'=>'文件上传','8'=>'谷歌黑客数据库','9'=>'高风险警报','10'=>'网络脚本','11'=>'参数操纵','12'=>'SQL注入','13'=>'文本搜索','14'=>'弱口令','15'=>'Web应用程序','16'=>'跨站脚本攻击'])->label('扫描配置') ?>
                <?= $form->field($model, 'mode')->dropDownList(['1'=>'快速','2'=>'混合','3'=>'广泛'])->label('扫描模式') ?>
                <?= $form->field($model, 'is_mail')->inline()->radioList(['1'=>'是','2'=>'否'])->label('邮件通知') ?>

                <div>
                    <hr/>
                    <?= $form->field($model, 'login_username')->textInput(['placeholder'=>'账号'])->label('表单认证') ?>
                    <?= $form->field($model, 'login_password')->passwordInput(['placeholder'=>'密码'])->label(false) ?>
                    <p>提示：若系统存在登录认证模块，请填写账号、密码</p>
                    <br/>
                </div>
                <div class="form-group">
                    <?= Html::resetButton('重置', ['class' => 'btn btn-primary', 'name' => 'new-button']) ?>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <?= Html::submitButton('提交', ['class' => 'btn btn-primary', 'name' => 'new-button']) ?>
                </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    <?php endif; ?>
</div>
