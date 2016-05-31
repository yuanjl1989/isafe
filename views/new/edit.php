<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\ContactForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = '编辑扫描申请';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-contact">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (Yii::$app->session->hasFlash('editFormSubmitted') || Yii::$app->session->hasFlash('permission')): ?>
        <?php if (Yii::$app->session->hasFlash('editFormSubmitted')): ?>

        <div class="alert alert-success">
            扫描申请编辑成功，结果会在扫描完成后自动生成。你可以在<a href="/new/edit?id=<?=Yii::$app->session['safe_id']?>">编辑页</a>再次修改该申请，也可以进入<a href="/list/list">列表页</a>查看所有添加的申请！
        </div>

        <?php else: ?>

        <div class="alert alert-success">
            你没有权限编辑该申请，请返回<a href="/list/list">列表页</a>查看其他申请！
        </div>
        <?php endif;?>

    <?php else: ?>
        <div class="row" style="margin-top: 50px">
            <div class="col-lg-4">

                <?php $form = ActiveForm::begin(['id' => 'edit-form']); ?>

                <?= $form->field($model, 'id')->hiddenInput()->label(false) ?>
                <?= $form->field($model, 'url')->textInput(['placeholder'=>'请输入需要扫描的网站地址','required'=>'required','type'=>'url'])->label('网站地址') ?>
                <?= $form->field($model, 'profile')->dropDownList(['1'=>'默认（均检测）','2'=>'AcuSensor传感器','3'=>'SQL盲注','4'=>'跨站点请求伪造','5'=>'目录和文件检查','6'=>'空（不使用任何检测）','7'=>'文件上传','8'=>'谷歌黑客数据库','9'=>'高风险警报','10'=>'网络脚本','11'=>'参数操纵','12'=>'SQL注入','13'=>'文本搜索','14'=>'弱口令','15'=>'Web应用程序','16'=>'跨站脚本攻击'])->label('扫描配置') ?>
                <?= $form->field($model, 'mode')->dropDownList(['1'=>'快速','2'=>'混合','3'=>'广泛'])->label('扫描模式') ?>
                <?= $form->field($model, 'is_mail')->inline()->radioList(['1'=>'是','2'=>'否'],['class'=>'radio-inline'])->label('邮件通知') ?>

                <div>
                    <hr/>
                    <?= $form->field($model, 'login_username')->textInput(['placeholder'=>'账号'])->label('表单认证') ?>
                    <?= $form->field($model, 'login_password')->passwordInput(['placeholder'=>'密码'])->label(false) ?>
                    <p>提示：若系统存在登录认证模块，请填写账号、密码</p>
                    <br/>
                </div>
                <div class="form-group">
                    <?= Html::submitButton('更新', ['class' => 'btn btn-primary', 'name' => 'edit-button']) ?>
                </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>

    <?php endif; ?>
</div>
