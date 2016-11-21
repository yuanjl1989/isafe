<?php

/* @var $this yii\web\View */

$this->title = 'Meters/bonwe 安全扫描平台';
?>
<div class="site-index">

    <div class="jumbotron">
        <h1>欢迎使用安全扫描平台</h1>

        <p class="lead">以下为系统基本功能的简要介绍</p>
    </div>

    <div class="body-content">

        <div class="row">
            <div class="col-lg-4" style="border:1px ridge;height: 440px">
                <h2>新增</h2>
                <p>扫描平台的主要功能，提供扫描申请信息的填写</p>
                <ul>
                    <li>网站地址：需要扫描的服务地址（一般只需填写域名或IP+端口即可）</li>
                    <br/>
                    <li>扫描配置：需要扫描的漏洞类型，如SQL注入、XSS、CSRF等，默认为全部检测</li>
                    <br/>
                    <li>登录认证：部分服务中存在登录认证模块，为保障扫描的全面性，可选择填写相应的账号/密码</li>
                    <br/>
                    <li>扫描模块：提供三种扫描模式，默认为快速</li>
                    <br/>
                    <li>邮件通知：若开启，扫描完成后，自动发送测试报告至申请人邮箱</li>
                </ul>

                <p>扫描未开始状态下，可对新增申请进行编辑或取消操作</p>

                <p><a class="btn btn-info" href="/new/new">进入 &raquo;</a></p>
            </div>
            <div class="col-lg-4" style="border:1px ridge;height: 440px">
                <h2>列表</h2>
                <p>展示申请队列的基本信息，包括URL地址、申请人、申请时间、扫描状态及操作<br/><br/>操作部分：根据不同状态，显示不同的操作按钮</p>
                <ul>
                    <li>状态为新建时，展示为编辑、取消</li>
                    <br/>
                    <li>状态为进行中/已取消时，展示为空</li>
                    <br/>
                    <li>状态为已完成时，展示为查看报告</li>
                    <br/>
                </ul>
                <p>其中，查看报告按钮提供了测试报告的查看功能，编辑为申请编辑的入口，取消为等待状态下取消扫描申请</p>
                <br/><br/><br/>

                <p><a class="btn btn-info" href="/list/list">进入 &raquo;</a></p>
            </div>
            <div class="col-lg-4" style="border:1px ridge;height: 440px">
                <h2>报告</h2>

                <p>最终用户看到的扫描结果，申请扫描完成后，两种方式获取报告：</p>
                <ul>
                    <li>直接在列表页查看相应的报告信息</li>
                    <br/>
                    <li>填写申请时勾选发送邮件，扫描完成后，报告会自动通过邮件形式发送至申请人邮箱！</li>
                </ul>

            </div>
        </div>

    </div>
</div>
