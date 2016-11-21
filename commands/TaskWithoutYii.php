<?php

/**
 * Created by PhpStorm.
 * User: Metersbonwe
 * Date: 2016/8/9
 * Time: 10:55
 */

require 'AnalisysReport.php';

class Scan
{
    private function connectDB()
    {
        $servername = "localhost";
        $username = "root";
        $password = "mbdev321";
        try {
            $conn = new PDO("mysql:host=$servername;dbname=mb_safe", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));
            return $conn;
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function actionIndex()
    {
        $wvs_console = "D:\\WVS10\\wvs_console";
        $appscan_cmd = "D:\\appscan\\AppScanCMD";
        $scan_mode = [1 => 'Quick', 2 => 'Heuristic', 3 => 'Extensive'];
        $scan_profile = ['1' => 'Default', '2' => 'AcuSensor', '3' => 'Blind_SQL_Injection', '4' => 'CSRF', '5' => 'Directory_And_File_Checks', '6' => 'Empty', '7' => 'File_Upload', '8' => 'GHDB', '9' => 'High_Risk_Alerts', '10' => 'Network_Scripts', '11' => 'Parameter_Manipulation', '12' => 'Sql_Injection', '13' => 'Text_Search', '14' => 'Weak_Passwords', '15' => 'Web_Applications', '16' => 'Xss'];

        /*该部分用于处理异常中止的数据，进行中的数据如果已经超过24小时未发生变化，状态置为3，并更新时间*/
        $sql = 'update safe_list set status=3,update_at=now() where status=2 and (unix_timestamp(update_at)<unix_timestamp(now())-86400 OR unix_timestamp(create_at)<unix_timestamp(now())-86400*7)';
        $stmt = $this->connectDB()->exec($sql);
        echo $stmt . " data has been updated !";

        /*控制同时进行扫描的个数，若超过5个，则退出脚本*/
        $sql_2 = 'select count(*) as num from safe_list where status=2';
        $count_doing = $this->connectDB()->query($sql_2)->fetchAll();
        if ($count_doing[0]['num'] > 5) {
            exit;
        }

        $date = date('Y-m-d H:i:s');
        $sql = 'select * from safe_list where status=1 order by create_at ASC';
        $safe_info = $this->connectDB()->query($sql)->fetchAll();

        foreach ($safe_info as $k => $v) {
            $succ = $this->connectDB()->exec("update safe_list set status=2,update_at='" . $date . "' where id=" . $v['id']);
            $safe_info_ext = $this->connectDB()->query('select * from safe_ext where safe_id=' . $v['id'])->fetch();

            $report_path = "E:\\yii\\web\\scanreport\\result_{$v['id']}";
            if ($succ) {
                if ($v['tool'] == 1 || $v['tool'] == 0) {
                    //根据页面配置中填写的信息，拼接成扫描命令，--abortscanafter=60该参数作用为：扫描超过1个小时则终止，避免出现假死
                    $command_main = "{$wvs_console} /Scan {$v['url']} /Profile {$scan_profile[$v['profile']]} /Save /GenerateReport /ReportFormat PDF /SaveFolder {$report_path}";
                    $command_mail = " /EmailAddress {$safe_info_ext['user_mail']}";
                    $command_auth = " --HtmlAuthUser={$v['login_username']} --HtmlAuthPass={$v['login_password']}";
                    $command_ext = " --ScanningMode={$scan_mode[$v['mode']]} --abortscanafter=60 >{$report_path}\\wvs_log.log";

                    if (!empty($v['login_username']) && !empty($v['login_password'])) {
                        $command = $command_main . $command_mail . $command_auth . $command_ext;
                        if ($v['is_mail'] == 2) {
                            $command = $command_main . $command_auth . $command_ext;
                        }
                    } else {
                        $command = $command_main . $command_mail . $command_ext;
                        if ($v['is_mail'] == 2) {
                            $command = $command_main . $command_ext;
                        }
                    }
                    if ($v['tool'] == 0) {
                        $command_wvs = $command;
                    }
                }
                if ($v['tool'] == 2 || $v['tool'] == 0) {
                    $command = "{$appscan_cmd} /e /su {$v['url']} /d {$report_path}\\result_{$v['id']}.scan /rt html /rf {$report_path}\\report_appscan_init.html >{$report_path}\\appscan_log.log";
                    if ($v['tool'] == 0) {
                        $command_appscan = $command;
                    }
                }

                if ($v['tool'] == 0) {
                    echo "\n" . $command_wvs;
                    system("mkdir {$report_path}", $out);
                    system($command_wvs, $out);
                    $wvs_safe_info = $this->generateWvsReport($v['id']);

                    echo "\n" . $command_appscan;
                    system($command_appscan, $out);
                    $appscan_safe_info = $this->generateAppScanReport($v['id']);
                    system('rm -rf *.xml');

                    $this->generateAllReport($wvs_safe_info, $appscan_safe_info);

                    if ($v['is_mail'] == 1) {
                        $content = file_get_contents($report_path . '\\report_all.html');
                        $this->sendMail($report_path . '\\report_all.html', $content, $safe_info_ext['user_mail']);
                    }
                } else {
                    echo "\n" . $command;
//                    system("mkdir {$report_path}", $out);
//                    system($command, $out);
                }

                /*用户勾选了发送邮件，且选择扫描工具为wvs时，执行以下代码*/
                if ($v['tool'] == 1) {
                    $this->generateWvsReport($v['id']);
                    if ($v['is_mail'] == 1) {
                        $content = file_get_contents($report_path . '\\report_wvs.html');
                        $this->sendMail($report_path . '\\report_wvs.html', $content, $safe_info_ext['user_mail']);
                    }
                }
                /*用户勾选了发送邮件，且选择扫描工具为appscan时，执行以下代码*/
                if ($v['tool'] == 2) {
                    $this->generateAppScanReport($v['id']);
                    if ($v['is_mail'] == 1) {
                        $content = file_get_contents($report_path . '\\report_appscan.html');
                        $this->sendMail($report_path . '\\report_appscan.html', $content, $safe_info_ext['user_mail']);
                    }
                    exec('rm -rf *.xml');
                }

                //根据执行结果更新数据状态
                $this->connectDB()->exec("update safe_list set status=4,update_at='" . $date . "' where id=" . $v['id']);

            }
        }
    }

    /**
     * 发送邮件方法
     * $report_path为附件路径
     * $content为邮件正文内容
     * $mail_to为收件人
     */
    private function sendMail($report_path, $content, $mail_to)
    {
        require_once '../phpmailer/class.phpmailer.php';

        try {
            $mail = new PHPMailer(true);
            $mail->IsSMTP();
            $mail->CharSet = 'UTF-8'; //设置邮件的字符编码，这很重要，不然中文乱码
            $mail->SMTPAuth = true; //开启认证
            $mail->Port = 25;
            $mail->Host = "smtp.163.com";
            $mail->Username = "pmt_noreply@163.com";
            $mail->Password = "w83yJVLd8w3amKBy";
            $mail->AddReplyTo("pmt_noreply@163.com", "pmt_noreply");//回复地址
            $mail->From = "pmt_noreply@163.com";
            $mail->FromName = "pmt_noreply";
            $mail->AddAddress($mail_to);
//            $mail->AddAddress('SDET@metersbonwe.com');
            $mail->Subject = "美邦安全扫描平台测试报告";
            $mail->Body = $content;
            $mail->AltBody = "To view the message, please use an HTML compatible email viewer!"; //当邮件不支持html时备用显示，可以省略
//            $mail->WordWrap = 80; // 设置每行字符串的长度
            $mail->AddAttachment($report_path); //可以添加附件
            $mail->IsHTML(true);
            $mail->Send();
            echo 'send success';
        } catch (phpmailerException $e) {
            echo "send failure：" . $e->errorMessage();
        }
    }

//    /**
//     * 获取文件最后几行内容的方法
//     * $filename为文件路径+文件名
//     * $n为获取内容的行数
//     */
//    public function getFileLastLines($filename, $n)
//    {
//        if (!$fp = fopen($filename, 'r')) {
//            echo "打开文件失败，请检查文件路径是否正确，路径和文件名不要包含中文";
//            return false;
//        }
//        $pos = -2;
//        $eof = "";
//        while ($n > 0) {
//            while ($eof != "\n") {
//                if (!fseek($fp, $pos, SEEK_END)) {
//                    $eof = fgetc($fp);
//                    $pos--;
//                } else {
//                    break;
//                }
//            }
//            $arr[] = fgets($fp);
//            $eof = "";
//            $n--;
//        }
//        unset($arr[0], $arr[1]);
//        /*由于获取内容是从最后一行开始的，故把每行内容存入数组后，对数组进行重新排序；重新排序完成后再转换为字符串*/
//        krsort($arr);
//        $str = implode("<br/>", $arr);
//        return $str;
//    }

    public function generateWvsReport($id)
    {
        $profile = ['1' => '默认（均检测）', '2' => 'AcuSensor传感器', '3' => 'SQL盲注', '4' => '跨站点请求伪造', '5' => '目录和文件检查', '6' => '空（不使用任何检测）', '7' => '文件上传', '8' => '谷歌黑客数据库', '9' => '高风险警报', '10' => '网络脚本', '11' => '参数操纵', '12' => 'SQL注入', '13' => '文本搜索', '14' => '弱口令', '15' => 'Web应用程序', '16' => '跨站脚本攻击'];

        $conn = $this->connectDB();
        $sql = 'SELECT id,url,profile FROM safe_list WHERE id = ' . $id;
        $safe_list = $conn->query($sql)->fetchAll();

        $wvs = new WVS();
        $issues_info = @$wvs->getWvsIssue($safe_list[0]['url']);
        $issues_info['summary']['id'] = $id;
        $issues_info['summary']['profile'] = $profile[$safe_list[0]['profile']];

        $table_css = 'font-family:微软雅黑;font-size:14px;border-collapse: collapse;border-spacing:0;';
        $td_css = 'padding: 8px;border: 1px solid;width: 200px';
        $th_css = 'background-color:#c0c0c0;font-weight:bold;adding: 8px;border: 1px solid;text-align:left';
        $header_css = 'cursor: default;font-family: 微软雅黑;font-size: 30px;position: relative;';

        $content = '<html>';
        $content .= '<meta http-equiv="content-type" content="text/html;charset=utf8" />';
        $content .= '<body style="font-family:微软雅黑;font-size:14px;">';
        $content .= '<a href="http://localhost:8081/scanreport/result_' . $id . '/report_wvs.html"  style="text-decoration : none"><span style="' . $header_css . '">美邦安全扫描平台测试报告（ID：' . $id . '）</span></a>';
        $content .= "<br/><br/>";
        $content .= '<table style="' . $table_css . '">';
        $content .= '<tr><th style="' . $th_css . '" colspan="2">扫描信息</th></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">目标URL</td><td style="' . $td_css . '">' . $issues_info['summary']['url'] . '</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">开始时间</td><td style="' . $td_css . '">' . $issues_info['summary']['starttime'] . '</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">结束时间</td><td style="' . $td_css . '">' . $issues_info['summary']['finishtime'] . '</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">扫描模式</td><td style="' . $td_css . '">' . $issues_info['summary']['profile'] . '</td></tr>';
        $content .= '<tr><th style="' . $th_css . '" colspan="2">服务信息</th></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">服务</td><td style="' . $td_css . '">' . $issues_info['summary']['servers'] . '</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">系统</td><td style="' . $td_css . '">' . $issues_info['summary']['os'] . '</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">语言</td><td style="' . $td_css . '">' . $issues_info['summary']['language'] . '</td></tr>';
        $content .= '<tr><th style="' . $th_css . '" colspan="2">扫描结果</th></tr>';
        $content_num = '<span style="font-size: 15px;font-weight:bold">' . '总数：' . $issues_info['summary']['level_count']['count'] . "</span><br/>" .
            '<span style="font-size: 15px;color:red">' . '高：' . $issues_info['summary']['level_count']['high'] . "</span><br/>" .
            '<span style="font-size: 15px;color:orange">' . '中：' . $issues_info['summary']['level_count']['mid'] . "</span><br/>" .
            '<span style="font-size: 15px;color:deepskyblue">' . '低：' . $issues_info['summary']['level_count']['low'] . "</span><br/>";
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">问题数量</td><td style="' . $td_css . '">' . $content_num . '</td></tr>';
        $content .= '</table>';
        $content .= "<br/><br/>";
        $content .= '<div>';
        $content .= '<span style="font-size: 18px;font-weight:bold">安全问题汇总：</span><br/>';
        $content .= "<hr/>";

        $j = 1;
        foreach ($issues_info['content'] as $key => $issue_content) {
            $query_issues_lib = "SELECT issue_ch,description,risk,suggestion FROM issues_lib WHERE issue_en = '" . $issue_content['issues'] . "'";
            $issues_lib = $conn->query($query_issues_lib)->fetchAll();
            if (!empty($issues_lib)) {
                $issues_info['content'][$key]['issues'] = $issue_content['issues'] = $issues_lib[0]['issue_ch'];
                $issues_info['content'][$key]['desc_text'] = $issue_content['desc_text'] = $issues_lib[0]['description'];
                $issues_info['content'][$key]['impact_text'] = $issue_content['impact_text'] = $issues_lib[0]['risk'];
                $issues_info['content'][$key]['recm_text'] = $issue_content['recm_text'] = $issues_lib[0]['suggestion'];
            } else {
                $issues_info['content'][$key]['issues'] = $issue_content['issues'] = $this->translateLongContent($issue_content['issues']);
                $issues_info['content'][$key]['desc_text'] = $issue_content['desc_text'] = $this->translateLongContent($issue_content['desc_text']);
                $issues_info['content'][$key]['impact_text'] = $issue_content['impact_text'] = $this->translateLongContent($issue_content['impact_text']);
                $issues_info['content'][$key]['recm_text'] = $issue_content['recm_text'] = $this->translateLongContent($issue_content['recm_text']);
            }

            $content .= '<span style="font-size: 16px;font-weight:bold">' . $j . ". " . $issue_content['issues'] . '</span><br/>';
            $severity = '';
            switch ($issue_content['severity']) {
                case '低':
                    $severity = '<span style="color:deepskyblue">低</span>';
                    break;
                case '中':
                    $severity = '<span style="color:orange">中</span>';
                    break;
                case '高':
                    $severity = '<span style="color:red">高</span>';
                    break;
            }
            $content .= '<span style="font-weight:bold">风险等级：</span>' . $severity . '<br/>';
            $content .= '<span style="font-weight:bold">描述：</span>' . (!empty($issue_content['desc_text']) ? $issue_content['desc_text'] : '待分析') . '<br/>';
            $content .= '<span style="font-weight:bold">影响内容：</span>' . (!empty($issue_content['affects']) ? $issue_content['affects'] : '待分析') . '<br/>';
            $content .= '<span style="font-weight:bold">风险：</span>' . (!empty($issue_content['impact_text']) ? $issue_content['impact_text'] : '待分析') . '<br/>';
            $content .= '<span style="font-weight:bold">建议：</span>' . (!empty($issue_content['recm_text']) ? $issue_content['recm_text'] : '待分析') . '<br/>';
            $content .= "<br/><br/>";
            $j++;
        }

        $content .= '</div>';
        $content .= '</body>';
        $content .= '<html>';

        $fp = fopen("E:\\yii\\web\\scanreport\\result_{$id}\\report_wvs.html", "w");
        fwrite($fp, $content);
        fclose($fp);
        return $issues_info;
    }

    public function generateAppScanReport($id)
    {
        $appscan = new AppScan();
        $issues_info = $appscan->getAppScanIssue($id);

        $table_css = 'font-family:微软雅黑;font-size:14px;border-collapse: collapse;border-spacing:0;';
        $td_css = 'padding: 8px;border: 1px solid;width: 200px';
        $th_css = 'background-color:#c0c0c0;font-weight:bold;adding: 8px;border: 1px solid;text-align:left';
        $header_css = 'cursor: default;font-family: 微软雅黑;font-size: 30px;position: relative;';

        $content = '<html>';
        $content .= '<meta http-equiv="content-type" content="text/html;charset=utf8" />';
        $content .= '<body style="font-family:微软雅黑;font-size:14px;">';
        $content .= '<a href="http://localhost:8081/scanreport/result_' . $id . '/report_appscan.html" style="text-decoration : none"><span style="' . $header_css . '">美邦安全扫描平台测试报告（ID：' . $id . '）</span></a>';
        $content .= "<br/><br/>";
        $content .= '<table style="' . $table_css . '">';
        $content .= '<tr><th style="' . $th_css . '" colspan="2">扫描信息</th></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">目标URL</td><td style="' . $td_css . '">' . $issues_info['summary']['url'] . '</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">开始时间</td><td style="' . $td_css . '">' . $issues_info['summary']['start_time'] . '</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">扫描模式</td><td>默认</td></tr>';
        $content .= '<tr><th style="' . $th_css . '" colspan="2">服务信息</th></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">服务</td><td style="' . $td_css . '">' . (($issues_info['summary']['server'] == 'Unknown') ? '未知' : $issues_info['summary']['server']) . '</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">系统</td><td style="' . $td_css . '">' . (($issues_info['summary']['os'] == 'Unknown') ? '未知' : $issues_info['summary']['os']) . '</td></tr>';
        $content .= '<tr><th style="' . $th_css . '" colspan="2">扫描结果</th></tr>';
        $content_num = '<span style="font-size: 15px;font-weight:bold">' . '总数：' . $issues_info['summary']['level_count']['count'] . "</span><br/>" .
            '<span style="font-size: 15px;color:red">' . '高：' . $issues_info['summary']['level_count']['high'] . "</span><br/>" .
            '<span style="font-size: 15px;color:orange">' . '中：' . $issues_info['summary']['level_count']['mid'] . "</span><br/>" .
            '<span style="font-size: 15px;color:deepskyblue">' . '低：' . $issues_info['summary']['level_count']['low'] . "</span><br/>" .
            '<span style="font-size: 15px;color:deepskyblue">' . '参考：' . $issues_info['summary']['level_count']['info'] . "</span><br/>";
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">问题数量</td><td style="' . $td_css . '">' . $content_num . '</td></tr>';
        $content .= '</table>';
        $content .= "<br/><br/>";
        $content .= '<div>';
        $content .= '<span style="font-size: 18px;font-weight:bold">安全问题汇总：</span><br/>';
        $content .= "<hr/>";

        for ($i = 1; $i <= count($issues_info['content']); $i++) {
            $content .= '<span style="font-size: 16px;font-weight:bold">' . $i . ". " . $issues_info['content'][$i - 1]['summary'] . '</span><br/>';
            $severity = '';
            switch ($issues_info['content'][$i - 1]['level']) {
                case '参':
                    $severity = '<span style="color:black">参考</span>';
                    break;
                case '低':
                    $severity = '<span style="color:deepskyblue">低</span>';
                    break;
                case '中':
                    $severity = '<span style="color:orange">中</span>';
                    break;
                case '高':
                    $severity = '<span style="color:red">高</span>';
                    break;
            }
            $content .= '<span style="font-weight:bold">风险等级：</span>' . $severity . '<br/>';
            $content .= '<span style="font-weight:bold">描述：</span>' . (!empty($issues_info['content'][$i - 1]['desc']) ? $issues_info['content'][$i - 1]['desc'] : '待分析') . '<br/>';
            $content .= '<span style="font-weight:bold">影响内容：</span>' . (!empty($issues_info['content'][$i - 1]['affects']) ? $issues_info['content'][$i - 1]['affects'] : '待分析') . '<br/>';
            $content .= '<span style="font-weight:bold">风险：</span>' . (!empty($issues_info['content'][$i - 1]['risk']) ? $issues_info['content'][$i - 1]['risk'] : '待分析') . '<br/>';
            $content .= '<span style="font-weight:bold">建议：</span>' . (!empty($issues_info['content'][$i - 1]['suggestion']) ? $issues_info['content'][$i - 1]['suggestion'] : '待分析') . '<br/>';
            $content .= "<br/><br/>";
        }

        $content .= '</div>';
        $content .= '</body>';
        $content .= '<html>';

        $fp = fopen("E:\\yii\\web\\scanreport\\result_{$id}\\report_appscan.html", "w");
        fwrite($fp, $content);
        fclose($fp);
        return $issues_info;
    }

    public function generateAllReport($wvs_safe_info, $appscan_safe_info)
    {
        require_once 'LCS.php';
        $lcs = new LCS();

        foreach ($wvs_safe_info['content'] as $wvs_k => $wvs_v) {
            foreach ($appscan_safe_info['content'] as $app_k => $app_v) {
                //返回相似度
                $similar_per = $lcs->getSimilar($wvs_v['issues'], $app_v['summary']);
                if ($similar_per >= 0.6) {
                    var_dump($wvs_v['issues'] . '-------' . $app_v['summary'] . '------------' . $similar_per . "\n\n\n");
                    switch ($wvs_safe_info['content'][$wvs_k]['severity']) {
                        case '高':
                            $wvs_safe_info['summary']['level_count']['high']--;
                            $wvs_safe_info['summary']['level_count']['count']--;
                            break;
                        case '中':
                            $wvs_safe_info['summary']['level_count']['mid']--;
                            $wvs_safe_info['summary']['level_count']['count']--;
                            break;
                        case '低':
                            $wvs_safe_info['summary']['level_count']['low']--;
                            $wvs_safe_info['summary']['level_count']['count']--;
                            break;
                    }
                    unset($wvs_safe_info['content'][$wvs_k]);
                    break;
                }
            }
        }

        $table_css = 'font-family:微软雅黑;font-size:14px;border-collapse: collapse;border-spacing:0;';
        $td_css = 'padding: 8px;border: 1px solid;width: 200px';
        $th_css = 'background-color:#c0c0c0;font-weight:bold;adding: 8px;border: 1px solid;text-align:left';
        $header_css = 'cursor: default;font-family: 微软雅黑;font-size: 30px;position: relative;';

        $content = '<html>';
        $content .= '<meta http-equiv="content-type" content="text/html;charset=utf8" />';
        $content .= '<body style="font-family:微软雅黑;font-size:14px;">';
        $content .= '<a href="http://localhost:8081/scanreport/result_' . $wvs_safe_info['summary']['id'] . '/report_appscan.html" style="text-decoration : none"><span style="' . $header_css . '">美邦安全扫描平台测试报告（ID：' . $wvs_safe_info['summary']['id'] . '）</span></a>';
        $content .= "<br/><br/>";
        $content .= '<table style="' . $table_css . '">';
        $content .= '<tr><th style="' . $th_css . '" colspan="2">扫描信息</th></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">目标URL</td><td style="' . $td_css . '">' . $wvs_safe_info['summary']['url'] . '</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">开始时间</td><td style="' . $td_css . '">' . $wvs_safe_info['summary']['starttime'] . '</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">结束时间</td><td style="' . $td_css . '">' . $wvs_safe_info['summary']['finishtime'] . '</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">扫描模式</td><td style="' . $td_css . '">' . $wvs_safe_info['summary']['profile'] . '</td></tr>';
        $content .= '<tr><th style="' . $th_css . '" colspan="2">服务信息</th></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">服务</td><td style="' . $td_css . '">' . $wvs_safe_info['summary']['servers'] . '</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">系统</td><td style="' . $td_css . '">' . $wvs_safe_info['summary']['os'] . '</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">语言</td><td style="' . $td_css . '">' . $wvs_safe_info['summary']['language'] . '</td></tr>';
        $content .= '<tr><th style="' . $th_css . '" colspan="2">扫描结果</th></tr>';
        $content_num = '<span style="font-size: 15px;font-weight:bold">' . '总数：' . (count($wvs_safe_info['content']) + count($appscan_safe_info['content'])) . "</span><br/>" .
            '<span style="font-size: 15px;color:red">' . '高：' . ($wvs_safe_info['summary']['level_count']['high'] + $appscan_safe_info['summary']['level_count']['high']) . "</span><br/>" .
            '<span style="font-size: 15px;color:orange">' . '中：' . ($wvs_safe_info['summary']['level_count']['mid'] + $appscan_safe_info['summary']['level_count']['mid']) . "</span><br/>" .
            '<span style="font-size: 15px;color:deepskyblue">' . '低：' . ($wvs_safe_info['summary']['level_count']['low'] + $appscan_safe_info['summary']['level_count']['low']) . "</span><br/>" .
            '<span style="font-size: 15px;color:black">' . '参考：' . $appscan_safe_info['summary']['level_count']['info'] . "</span><br/>";
        $content .= '<tr><td style="background-color:#efefef;' . $td_css . '">问题数量</td><td style="' . $td_css . '">' . $content_num . '</td></tr>';
        $content .= '</table>';
        $content .= "<br/><br/>";
        $content .= '<div>';
        $content .= '<span style="font-size: 18px;font-weight:bold">安全问题汇总：</span><br/>';
        $content .= "<hr/>";

        $tmp_wvs = $tmp_high = $tmp_mid = $tmp_low = $tmp_info =array();
        foreach ($wvs_safe_info['content'] as $wvs_key => $wvs_issue){
            $tmp_wvs[$wvs_key]['summary'] = $wvs_issue['issues'];
            $tmp_wvs[$wvs_key]['level'] = $wvs_issue['severity'];
            $tmp_wvs[$wvs_key]['desc'] = $wvs_issue['desc_text'];
            $tmp_wvs[$wvs_key]['affects'] = $wvs_issue['affects'];
            $tmp_wvs[$wvs_key]['risk'] = $wvs_issue['impact_text'];
            $tmp_wvs[$wvs_key]['suggestion'] = $wvs_issue['recm_text'];
        }
        $issue_contents = array_merge($tmp_wvs,$appscan_safe_info['content']);

        foreach ($issue_contents as $merge_key => $issue){
            switch ($issue['level']){
                case '参':
                    $tmp_info[] = $issue;
                    break;
                case '低':
                    $tmp_low[] = $issue;
                    break;
                case '中':
                    $tmp_mid[] = $issue;
                    break;
                case '高':
                    $tmp_high[] = $issue;
                    break;
            }
        }
        $issue_contents = array_merge($tmp_high,$tmp_mid,$tmp_low,$tmp_info);

        for ($i = 1; $i <= count($issue_contents); $i++) {
            $content .= '<span style="font-size: 16px;font-weight:bold">' . $i . ". " . $issue_contents[$i - 1]['summary'] . '</span><br/>';
            $severity = '';
            switch ($issue_contents[$i - 1]['level']) {
                case '参':
                    $severity = '<span style="color:black">参考</span>';
                    break;
                case '低':
                    $severity = '<span style="color:deepskyblue">低</span>';
                    break;
                case '中':
                    $severity = '<span style="color:orange">中</span>';
                    break;
                case '高':
                    $severity = '<span style="color:red">高</span>';
                    break;
            }
            $content .= '<span style="font-weight:bold">风险等级：</span>' . $severity . '<br/>';
            $content .= '<span style="font-weight:bold">描述：</span>' . (!empty($issue_contents[$i - 1]['desc']) ? $issue_contents[$i - 1]['desc'] : '待分析') . '<br/>';
            $content .= '<span style="font-weight:bold">影响内容：</span>' . (!empty($issue_contents[$i - 1]['affects']) ? $issue_contents[$i - 1]['affects'] : '待分析') . '<br/>';
            $content .= '<span style="font-weight:bold">风险：</span>' . (!empty($issue_contents[$i - 1]['risk']) ? $issue_contents[$i - 1]['risk'] : '待分析') . '<br/>';
            $content .= '<span style="font-weight:bold">建议：</span>' . (!empty($issue_contents[$i - 1]['suggestion']) ? $issue_contents[$i - 1]['suggestion'] : '待分析') . '<br/>';
            $content .= "<br/><br/>";
        }

        $content .= '</div>';
        $content .= '</body>';
        $content .= '<html>';

        $fp = fopen("E:\\yii\\web\\scanreport\\result_{$wvs_safe_info['summary']['id']}\\report_all.html", "w");
        fwrite($fp, $content);
        fclose($fp);
    }

    public function translateContent($content)
    {
        $url = 'http://fanyi.youdao.com/openapi.do?keyfrom=mb-safe-platform&key=1746923643&type=data&doctype=json&version=1.1&q=' . $content;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $file_contents = curl_exec($ch);
        //翻译不成功，重试三次
        if(!$file_contents){
            $file_contents = curl_exec($ch);
            if(!$file_contents){
                $file_contents = curl_exec($ch);
                if(!$file_contents){
                    $file_contents = curl_exec($ch);
                }
            }
        }
        curl_close($ch);
        if ($file_contents) {
            $wvs_translate_obj = json_decode($file_contents);
            $ret = @$wvs_translate_obj->translation[0];
        }
        return isset($ret) ? $ret : urldecode($content);
    }

    public function translateLongContent($content)
    {
        if (!preg_match('/[\x{4e00}-\x{9fa5}]/u', $content)) {
            if (strlen($content) > 200) {
                $ret = $translate_split = array();
                $len = strlen($content);
                for ($i = 0; $i < $len; $i += 200) {
                    $ret[] = substr($content, $i, 200);
                }
                foreach ($ret as $split_content) {
                    $translate_split[] = $this->translateContent(urlencode($split_content));
                }
                $ret = implode('', $translate_split);
            } else {
                $ret = $this->translateContent(urlencode($content));
            }
        } else {
            $ret = $content;
        }
        return $ret;
    }
}

$test = new Scan();
$rs = $test->actionIndex();