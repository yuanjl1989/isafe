<?php

/* @var $this yii\web\View */

use yii\helpers\Html;
use app\assets\AppAsset;
use yii\bootstrap\ActiveForm;

AppAsset::register($this);
AppAsset::addScript($this,"/js/list.js");

$this->title = '申请列表';
$this->params['breadcrumbs'][] = $this->title;
?>

<form class="form-horizontal" action="/list/list" method="post">

    <div class="form-group">
        <label class="col-lg-1 control-label" for="formGroupInputSmall">申请人</label>
        <div class="col-lg-3">
            <input class="form-control" type="text" id="formGroupInputSmall" placeholder="请输入申请人姓名" name="username" value="<? if(isset($params['username'])) echo $params['username'];?>">
        </div>
    </div>

    <div class="form-group">
        <label class="col-lg-1 control-label" for="formGroupInputSmall">状态</label>
        <label class="checkbox-inline">
            <input type="checkbox" id="inlineCheckbox1" name="status_1" value="1" <? if(isset($params['status_1'])):?> checked <? endif;?>> 新建
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" id="inlineCheckbox2" name="status_2" value="2" <? if(isset($params['status_2'])):?> checked <? endif;?>> 进行中
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" id="inlineCheckbox3" name="status_3" value="3" <? if(isset($params['status_3'])):?> checked <? endif;?>> 已取消
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" id="inlineCheckbox3" name="status_4" value="4" <? if(isset($params['status_4'])):?> checked <? endif;?>> 已完成
        </label>
        <button type="submit" class="btn btn-primary btn-lg" style="margin-left: 180px">搜索</button>
        <br/>
    </div>
</form>

<div style="margin-left: 950px">
    <a href="/new/new" class="btn btn-default btn-lg" role="button">新增安全扫描申请</a>
</div>

<table class="table table-bordered">
    <br/>
    <thead>
        <tr>
            <th width="4%">ID</th>
            <th width="45%">扫描站点</th>
            <th width="8%">申请人</th>
            <th width="15%">申请时间</th>
            <th width="8%">扫描状态</th>
            <th width="20%">操作</th>
        </tr>
    </thead>
    <tbody>
        <?php if(!empty($list_info) && is_array($list_info)):?>
            <?php foreach ($list_info as $key => $item):?>
        <tr>
            <td><?=$item['id']?></td>
            <td><?=$item['url']?> | <a href="/new/view?id=<?=$item['id']?>">查看详情</a> </td>
            <td><?=$item['chinese_name']?></td>
            <td><?=$item['create_at']?></td>
            <td><?=$status_arr[$item['status']]?></td>
            <td>
                <?php if ($item['status'] == 1):?>
                    <a href="/new/edit?id=<?=$item['id']?>"><button type="button" class="btn btn-default">编辑</button></a>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <button type="button" class="btn btn-default" onclick="docancel(<?=$item['id']?>)">取消</button>
                <?php endif;?>
                <?php if ($item['status'] == 2):?>
                    <button type="button" class="btn btn-default" onclick="docancel(<?=$item['id']?>)">中止</button>
                <?php endif;?>
                <?php if ($item['status'] == 4):?>
                    <a href="/new/edit?id=<?=$item['id']?>"><button type="button" class="btn btn-default">下载报告</button></a>
                <?php endif;?>
            </td>
        </tr>
        <?php endforeach;?>
        <?php endif;?>
    </tbody>

</table>

<div style="text-align: center">
    <ul class="pagination">
        <li><a href="#">&laquo;</a></li>
        <li class="active"><a href="#">1</a></li>
        <li><a href="#">2</a></li>
        <li><a href="#">3</a></li>
        <li><a href="#">4</a></li>
        <li><a href="#">5</a></li>
        <li><a href="#">&raquo;</a></li>
    </ul>
</div>


<!--    <code>--><?//= __FILE__ ?><!--</code>-->

