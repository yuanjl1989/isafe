<?php

/* @var $this yii\web\View */

use app\assets\AppAsset;
use app\models\SafeList;
use app\models\SafeExt;

AppAsset::register($this);
AppAsset::addScript($this,"/js/list.js");
AppAsset::addScript($this,"/layer/layer.js");

$this->title = '申请列表';
$this->params['breadcrumbs'][] = $this->title;
?>

<form class="form-horizontal" action="/list/list" method="get">

    <div class="form-group">
        <label class="col-lg-1 control-label" for="formGroupInputSmall">申请人</label>
        <div class="col-lg-3">
            <input class="form-control" type="text" id="formGroupInputSmall" placeholder="请输入申请人姓名" name="username" value="<? if(isset($params['username'])) echo $params['username'];?>">
        </div>
    </div>

    <div class="form-group">
        <label class="col-lg-1 control-label" for="formGroupInputSmall">状态</label>
        <label class="checkbox-inline">
            <input type="checkbox" id="inlineCheckbox1" name="status[0]" value="1" <? echo isset($params['status'][0])?'checked':'';?>> 新建
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" id="inlineCheckbox2" name="status[1]" value="2" <? echo isset($params['status'][1])?'checked':'' ?>> 进行中
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" id="inlineCheckbox3" name="status[2]" value="3" <? echo isset($params['status'][2])?'checked':'';?>> 已取消
        </label>
        <label class="checkbox-inline">
            <input type="checkbox" id="inlineCheckbox3" name="status[3]" value="4" <? echo isset($params['status'][3])?'checked':'';?>> 已完成
        </label>
        <button type="submit" class="btn btn-primary btn-lg" style="margin-left: 180px">搜索</button>
        <br/>
    </div>
</form>

<div style="margin-left: 980px">
    <a href="/new/new" class="btn btn-warning btn-lg" role="button">新增安全扫描</a>
</div>


<table class="table table-bordered">
    <br/>
    <thead>
        <tr>
            <th width="4%">ID</th>
            <th width="30%">扫描站点</th>
            <th width="8%">申请人</th>
            <th width="15%">申请时间</th>
            <th width="8%">状态</th>
            <th style="text-align: center" width="12%">操作</th
        </tr>
    </thead>
    <tbody>
        <?php if(!empty($list_info) && is_array($list_info)):?>
            <?php foreach ($list_info as $key => $item):?>
        <tr style="height: 45px;">
            <td><?=$item['id']?></td>
            <td><?=$item['url']?> | <a href="/new/view?id=<?=$item['id']?>">查看详情</a> </td>
            <td><?=$item['chinese_name']?></td>
            <td><?=$item['create_at']?></td>
            <td><?=$status_arr[$item['status']]?></td>
            <td style="text-align: center">
                <?php
                    $details = SafeList::findOne($item['id']);
                    $details_ext = SafeExt::find()->where(['safe_id'=>$details->id])->one();
                    if($details_ext->user_id == Yii::$app->session['user_id']):?>
                    <?php if ($item['status'] == 1):?>
                        <a href="/new/edit?id=<?=$item['id']?>"><button type="button" class="btn btn-info">编辑</button></a>
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        <button type="button" class="btn btn-info" onclick="docancel(<?=$item['id']?>)">取消</button>
                    <?php endif;?>
                    <?php if ($item['status'] == 4):?>
                        <a href="/scanreport/result_<?=$item['id']?>/report_<?=($item['tool'] == 0)?'all':(($item['tool'] == 1)?'wvs':'appscan');?>.html" target="_blank"><button type="button" class="btn btn-success">查看报告</button></a>
                    <?php endif;?>
                <?php endif;?>
            </td>
        </tr>
        <?php endforeach;?>
        <?php endif;?>
    </tbody>

</table>

<ul class="pagination">
    <?php

    $url = Yii::$app->getRequest()-> getUrl();

    if(Yii::$app->getRequest()-> getQueryParam('page')){
        $url_arr = explode('&',$url);
        array_pop($url_arr);
        $url = implode('&',$url_arr);
    }

    $url = strpos($url,'?')?($url.'&page='):($url.'?page=');

    if($page >1)
        echo "<li><a href='".$url.($page-1)."'>&laquo;</a></li>";
    for ($i=1;$i<= $pages;$i++){
        if($i == $page){
            echo "<li><a style='color: lightgray' href ='javascript:return false;'>$i</a></li>";
        }else{
            echo "<li><a href='".$url.$i."'>$i</a></li>";
        }
    }
    if($page < $pages)
        echo "<li><a href='".$url.($page+1)."'>&raquo;</a></li>";
    ?>
</ul>
