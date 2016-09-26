<?php

/**
 * Created by PhpStorm.
 * User: Metersbonwe
 * Date: 2016/8/9
 * Time: 10:55
 */
class Scan
{
    private function connectDB()
    {
        $servername = "localhost";
        $username = "root";
        $password = "mbdev321";
        try {
            $conn = new PDO("mysql:host=$servername;dbname=mb_safe", $username, $password,array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));
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
        $sql = 'update safe_list set status=3,update_at=now() where status=2 and unix_timestamp(update_at)<unix_timestamp(now())-86400';
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
                if ($v['tool'] == 1) {
                    //根据页面配置中填写的信息，拼接成扫描命令，--abortscanafter=600该参数作用为：扫描超过10个小时则终止，避免出现假死
                    $command_main = "{$wvs_console} /Scan {$v['url']} /Profile {$scan_profile[$v['profile']]} /Save /GenerateReport /ReportFormat PDF /SaveFolder {$report_path}";
                    $command_mail = " /EmailAddress {$safe_info_ext['user_mail']}";
                    $command_auth = " --HtmlAuthUser={$v['login_username']} --HtmlAuthPass={$v['login_password']}";
                    $command_ext = " --ScanningMode={$scan_mode[$v['mode']]} --abortscanafter=30 >{$report_path}\\wvs_log.log";

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
                } else {
                    $command = "{$appscan_cmd} /e /su {$v['url']} /d {$report_path}\\result_{$v['id']}.scan /rt Pdf /rf {$report_path}\\report.pdf >{$report_path}\\appscan_log.log";
                }
                echo "\n" . $command;
                system("mkdir {$report_path}", $out);
                system($command, $out);

                if ($v['tool'] == 1) {
                    $issues = $this->queryWvsAlerts($v['url']);
                    foreach ($issues as $res) {
                        $sql_3 = "insert into safe_issues(safe_id,servers,os,language,issues,affects,severity,details,request,response) VALUES ({$v['id']},'" . $res['servers'] . "','" . $res['os'] . "','" . $res['language'] . "','" . $res['issues'] . "','" . $res['affects'] . "','" . $res['severity'] . "','" . $res['details'] . "','" . $res['request'] . "','" . $res['response'] . "')";
                        $this->connectDB()->exec($sql_3);
                    }
                    $this->generateWvsReport($v['id']);
                    if ($v['is_mail'] == 1) {
                        $content = file_get_contents($report_path.'\\report.html');//获取最后15行的内容
//                        $content = mb_convert_encoding($content, 'UTF-8', 'GBK');//把日志中读取的内容转化为UTF-8编码
                        $this->sendMail($report_path.'\\report.html', $content, $safe_info_ext['user_mail']);
                    }
                }
                /*用户勾选了发送邮件，且选择扫描工具为appscan时，执行以下代码*/
                if ($v['tool'] == 2){
                    $this->generateAppScanReport($v['id']);
                    if($v['is_mail'] == 1){
                        $content = file_get_contents($report_path.'\\report.html');//获取最后15行的内容
//                        $content = mb_convert_encoding($content, 'UTF-8', 'GBK');//把日志中读取的内容转化为UTF-8编码
                        $this->sendMail($report_path.'\\report.html', $content, $safe_info_ext['user_mail']);
                    }
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

    /**
     * 获取文件最后几行内容的方法
     * $filename为文件路径+文件名
     * $n为获取内容的行数
     */
    public function getFileLastLines($filename, $n)
    {
        if (!$fp = fopen($filename, 'r')) {
            echo "打开文件失败，请检查文件路径是否正确，路径和文件名不要包含中文";
            return false;
        }
        $pos = -2;
        $eof = "";
        while ($n > 0) {
            while ($eof != "\n") {
                if (!fseek($fp, $pos, SEEK_END)) {
                    $eof = fgetc($fp);
                    $pos--;
                } else {
                    break;
                }
            }
            $arr[] = fgets($fp);
            $eof = "";
            $n--;
        }
        unset($arr[0],$arr[1]);
        /*由于获取内容是从最后一行开始的，故把每行内容存入数组后，对数组进行重新排序；重新排序完成后再转换为字符串*/
        krsort($arr);
        $str = implode("<br/>", $arr);
        return $str;
    }

    public function queryWvsAlerts($url)
    {
        $conn = new COM("ADODB.Connection");
        $connstr = "DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=" . "D:\\WVS10\\vulnscanresults.mdb";
        $conn->Open($connstr);
        $scans_rs = $this->queryWvsScans($url);
        $query = "select '" . $scans_rs[0]['starturl'] . "','" . $scans_rs[0]['servers'] . "','" . $scans_rs[0]['os'] . "','" .$scans_rs[0]['language'] . "',algroup,affects,severity,details,request,response from WVS_alerts where scid={$scans_rs[0]['scid']} and severity BETWEEN 1 and 3 ORDER by severity DESC ";
        $rs = $conn->Execute($query);

        $issues = array('starturl', 'servers', 'os','language', 'issues', 'affects', 'severity', 'details', 'request', 'response');

        $content = array();
        $j = 0;
        while (!$rs->EOF) {
            for ($i = 0; $i < $rs->Fields->count; $i++) {
                @$content[$j][$issues[$i]] = $rs->Fields($i)->Value;
            }
            $j++;
            $rs->MoveNext();
        }
        return $content;
    }

    public function queryWvsScans($url)
    {
        $conn = new COM("ADODB.Connection");
        $connstr = "DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=" . "D:\\WVS10\\vulnscanresults.mdb";
        $conn->Open($connstr);
        $query = "select top 1 a.scid,a.starturl,b.banner,b.os,b.technologies from WVS_scans a,WVS_servers b where a.starturl='" . $url . "' and a.serverid=b.serverid order by a.starttime desc";
        $rs = $conn->Execute($query);

        $table_scans = array('scid', 'starturl', 'servers', 'os','language');

        $content = array();
        $j = 0;
        while (!$rs->EOF) {
            for ($i = 0; $i < $rs->Fields->count; $i++) {
                @$content[$j][$table_scans[$i]] = (string)$rs->Fields($i)->Value;
            }
            $j++;
            $rs->MoveNext();
        }
        return $content;
    }

    public function generateWvsReport($id = 106)
    {
        $profile = ['1'=>'默认（均检测）','2'=>'AcuSensor传感器','3'=>'SQL盲注','4'=>'跨站点请求伪造','5'=>'目录和文件检查','6'=>'空（不使用任何检测）','7'=>'文件上传','8'=>'谷歌黑客数据库','9'=>'高风险警报','10'=>'网络脚本','11'=>'参数操纵','12'=>'SQL注入','13'=>'文本搜索','14'=>'弱口令','15'=>'Web应用程序','16'=>'跨站脚本攻击'];

        $log = $this->getFileLastLines("E:\\yii\\web\\scanreport\\result_{$id}\\wvs_log.log", 16);

        preg_match("/Start\ time.*\:\ (.*?)\r\n/U",$log,$starttime);
        $starttime = $starttime[1];

        preg_match("/Finish\ time.*\:\ (.*?)\r\n/U",$log,$finishtime);
        $finishtime = $finishtime[1];

        preg_match("/Scan\ time.*\:\ (.*?)\r\n/U",$log,$scantime);
        $scantime = $scantime[1];

        $conn = $this->connectDB();

        $sql = 'SELECT DISTINCT
                    a.id,
                    a.profile,
                    a.url,
                    b.servers,
                    b.os,
                    b.issues,
                    CASE b.severity
                WHEN 1 THEN
                    \'低\'
                WHEN 2 THEN
                    \'中\'
                WHEN 3 THEN
                    \'高\'
                END AS severity,
                 b.affects,
                 c.issue_ch,
                 c.description,
                 c.risk,
                 c.suggestion
                FROM
                    safe_list a,
                    safe_issues b,
                    issues_lib c
                WHERE
                    a.id = b.safe_id
                AND b.issues = c.issue_en
                AND a.id = ' . $id;
        $safe_issues = $conn->query($sql)->fetchAll();
        $issues_arr = array();
        $high = $mid = $low = 0;

        if(!empty($safe_issues)){
            foreach ($safe_issues as $k => $v){
                if(!in_array($v['issues'],$issues_arr)){
                    $issues_arr[] = $v['issues'];
                    switch ($v['severity']){
                        case '低':
                            $low++;break;
                        case '中':
                            $mid++;break;
                        case '高':
                            $high++;break;
                    }
                }else{
                    unset($safe_issues[$k]);
                }
            }
        }
        $safe_issues = array_values($safe_issues);

        $no_lib = $conn->query('select distinct issues from safe_issues where issues not in (select issue_en from issues_lib) and safe_id='.$id)->fetchAll();
        if(!empty($no_lib)){
            //var_dump($no_lib);die;
            $symbol = array(' "','" ',':','.',',',' ','(',')','[',']');
            foreach ($no_lib as $issue_name){
                $sql_affects_severity = 'select issues,group_concat(distinct affects) as affects,severity from safe_issues where safe_id='.$id.' and issues='."'".$issue_name['issues']."'";
                $affects_severity = $conn->query($sql_affects_severity)->fetch();

                $issue_name_str = strtolower(str_replace($symbol,'-',$affects_severity['issues']));
                $url = 'https://www.acunetix.com/vulnerabilities/web/'.$issue_name_str;

                unset($affects_severity[0],$affects_severity[1],$affects_severity[2]);
                $safe_issues_web[] = array_merge($affects_severity,$this->getContentFromHTML($url));
            }
        }

        if(!empty($safe_issues_web)){
            foreach ($safe_issues_web as $v){
                switch ($v['severity']){
                    case 3:
                        $high++;break;
                    case 2:
                        $mid++;break;
                    case 1:
                        $low++;break;
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
        $content .= '<span style="'.$header_css.'">美邦安全扫描平台测试报告（ID：'.$id.'）</span>';
        $content .= "<br/><br/>";
        $content .= '<table style="'.$table_css.'">';
        $content .= '<tr><th style="'.$th_css.'" colspan="2">扫描信息</th></tr>';
        $content .= '<tr><td style="background-color:#efefef;'.$td_css.'">目标URL</td><td style="'.$td_css.'">'.$safe_issues[0]['url'].'</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;'.$td_css.'">开始时间</td><td style="'.$td_css.'">'.$starttime.'</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;'.$td_css.'">结束时间</td><td style="'.$td_css.'">'.$finishtime.'</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;'.$td_css.'">扫描时长</td><td style="'.$td_css.'">'.$scantime.'</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;'.$td_css.'">扫描模式</td><td style="'.$td_css.'">'.$profile[$safe_issues[0]['profile']].'</td></tr>';
        $content .= '<tr><th style="'.$th_css.'" colspan="2">服务信息</th></tr>';
        $content .= '<tr><td style="background-color:#efefef;'.$td_css.'">服务</td><td style="'.$td_css.'">'.$safe_issues[0]['servers'].'</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;'.$td_css.'">系统</td><td style="'.$td_css.'">'.$safe_issues[0]['os'].'</td></tr>';
        $content .= '<tr><th style="'.$th_css.'" colspan="2">扫描结果</th></tr>';
        $content_num = '<span style="font-size: 15px;font-weight:bold">'.'总数：'.($high+$mid+$low)."</span><br/>".
                       '<span style="font-size: 15px;color:red">'.'高：'.$high."</span><br/>".
                       '<span style="font-size: 15px;color:orange">'.'中：'.$mid."</span><br/>".
                       '<span style="font-size: 15px;color:deepskyblue">'.'低：'.$low."</span><br/>";
        $content .= '<tr><td style="background-color:#efefef;'.$td_css.'">问题数量</td><td style="'.$td_css.'">'.$content_num.'</td></tr>';
        $content .= '</table>';
        $content .= "<br/><br/>";
        $content .= '<div>';
        $content .= '<span style="font-size: 18px;font-weight:bold">安全问题汇总：</span><br/>';
        $content .= "<hr/>";
        foreach ($issues_arr as $k => $v){
            $sql_affects = 'select group_concat(distinct affects) as affects from safe_issues where safe_id='.$id.' and issues='."'".$v."'";
            $affects = $conn->query($sql_affects)->fetch();
            $affects_arr[$k] = $affects['affects'];
        }

        for($i=1;$i<=count($issues_arr);$i++){
            $content .= '<span style="font-size: 16px;font-weight:bold">'.$i.". ".$safe_issues[$i-1]['issue_ch'].'</span><br/>';
            $severity = '';
            switch ($safe_issues[$i-1]['severity']){
                case '低':
                    $severity = '<span style="color:deepskyblue">低</span>';break;
                case '中':
                    $severity = '<span style="color:orange">中</span>';break;
                case '高':
                    $severity = '<span style="color:red">高</span>';break;
            }
            $content .= '<span style="font-weight:bold">风险等级：</span>'.$severity.'<br/>';
            $content .= '<span style="font-weight:bold">描述：</span>'.(!empty($safe_issues[$i-1]['description'])?$safe_issues[$i-1]['description']:'待分析').'<br/>';
            $content .= '<span style="font-weight:bold">影响内容：</span>'.(!empty($affects_arr[$i-1])?$affects_arr[$i-1]:'待分析').'<br/>';
            $content .= '<span style="font-weight:bold">风险：</span>'.(!empty($safe_issues[$i-1]['risk'])?$safe_issues[$i-1]['risk']:'待分析').'<br/>';
            $content .= '<span style="font-weight:bold">建议：</span>'.(!empty($safe_issues[$i-1]['suggestion'])?$safe_issues[$i-1]['suggestion']:'待分析').'<br/>';
            $content .= "<br/><br/>";
        }

        if(!empty($safe_issues_web)){
            for($i=1;$i<=count($safe_issues_web);$i++){
                $severity = '';
                switch ($safe_issues_web[$i-1]['severity']){
                    case 3:
                        $severity = '<span style="color:red">高</span>';break;
                    case 2:
                        $severity = '<span style="color:orange">中</span>';break;
                    case 1:
                        $severity = '<span style="color:deepskyblue">低</span>';break;
                }
                if(!empty($safe_issues_web[$i-1]['issues'])){
                    $content .= '<span style="font-size: 16px;font-weight:bold">'.($i+count($issues_arr)).". ".$safe_issues_web[$i-1]['issues'].'</span><br/>';
                    $content .= '<span style="font-weight:bold">风险等级：</span>'.(!empty($severity)?$severity:'待分析').'<br/>';
                    $content .= '<span style="font-weight:bold">描述：</span>'.(!empty($safe_issues_web[$i-1][0])?strip_tags($safe_issues_web[$i-1][0]):'待分析').'<br/>';
                    $content .= '<span style="font-weight:bold">影响内容：</span>'.(!empty($safe_issues_web[$i-1]['affects'])?strip_tags($safe_issues_web[$i-1]['affects']):'待分析').'<br/>';
                    $content .= '<span style="font-weight:bold">建议：</span>'.(!empty($safe_issues_web[$i-1][1])?strip_tags($safe_issues_web[$i-1][1]):'待分析').'<br/>';
                    $content .= "<br/><br/>";
                }else{
                    unset($safe_issues_web[$i-1]);
                    $safe_issues_web = array_values($safe_issues_web);
                    $i--;
                }
            }
        }

        $content .= '</div>';
        $content .= '</body>';
        $content .= '<html>';

        $fp = fopen("E:\\yii\\web\\scanreport\\result_{$id}\\report.html","w");
        fwrite($fp,$content);
        fclose($fp);
    }

    public function generateAppScanReport($id = 95)
    {
        require_once 'AnalisysPDF.php';

        $appscan = new AppScan();
        $issues_info = $appscan->getAppScanIssue($id);//var_dump($issues_info);die;

        $table_css = 'font-family:微软雅黑;font-size:14px;border-collapse: collapse;border-spacing:0;';
        $td_css = 'padding: 8px;border: 1px solid;width: 200px';
        $th_css = 'background-color:#c0c0c0;font-weight:bold;adding: 8px;border: 1px solid;text-align:left';
        $header_css = 'cursor: default;font-family: 微软雅黑;font-size: 30px;position: relative;';

        $content = '<html>';
        $content .= '<meta http-equiv="content-type" content="text/html;charset=utf8" />';
        $content .= '<body style="font-family:微软雅黑;font-size:14px;">';
        $content .= '<span style="'.$header_css.'">美邦安全扫描平台测试报告（ID：'.$id.'）</span>';
        $content .= "<br/><br/>";
        $content .= '<table style="'.$table_css.'">';
        $content .= '<tr><th style="'.$th_css.'" colspan="2">扫描信息</th></tr>';
        $content .= '<tr><td style="background-color:#efefef;'.$td_css.'">目标URL</td><td style="'.$td_css.'">'.$issues_info['summary']['url'].'</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;'.$td_css.'">开始时间</td><td style="'.$td_css.'">'.$issues_info['summary']['start_time'].'</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;'.$td_css.'">扫描模式</td><td style="'.$td_css.'">'.$issues_info['summary']['profile'].'</td></tr>';
        $content .= '<tr><th style="'.$th_css.'" colspan="2">服务信息</th></tr>';
        $content .= '<tr><td style="background-color:#efefef;'.$td_css.'">服务</td><td style="'.$td_css.'">'.$issues_info['summary']['server'].'</td></tr>';
        $content .= '<tr><td style="background-color:#efefef;'.$td_css.'">系统</td><td style="'.$td_css.'">'.$issues_info['summary']['os'].'</td></tr>';
        $content .= '<tr><th style="'.$th_css.'" colspan="2">扫描结果</th></tr>';
        $content_num = '<span style="font-size: 15px;font-weight:bold">'.'总数：'.($issues_info['summary']['high']+$issues_info['summary']['mid']+$issues_info['summary']['low']+$issues_info['summary']['info'])."</span><br/>".
                       '<span style="font-size: 15px;color:red">'.'高：'.$issues_info['summary']['high']."</span><br/>".
                       '<span style="font-size: 15px;color:orange">'.'中：'.$issues_info['summary']['mid']."</span><br/>".
                       '<span style="font-size: 15px;color:deepskyblue">'.'低：'.$issues_info['summary']['low']."</span><br/>".
                       '<span style="font-size: 15px;color:deepskyblue">'.'参考：'.$issues_info['summary']['info']."</span><br/>";
        $content .= '<tr><td style="background-color:#efefef;'.$td_css.'">问题数量</td><td style="'.$td_css.'">'.$content_num.'</td></tr>';
        $content .= '</table>';
        $content .= "<br/><br/>";
        $content .= '<div>';
        $content .= '<span style="font-size: 18px;font-weight:bold">安全问题汇总：</span><br/>';
        $content .= "<hr/>";

        for($i=1;$i<=count($issues_info['details']);$i++){
            $content .= '<span style="font-size: 16px;font-weight:bold">'.$i.". ".$issues_info['details'][$i-1]['issue_name'].'</span><br/>';
            $severity = '';
            switch ($issues_info['details'][$i-1]['severity']){
                case '参考信息':
                    $severity = '<span style="color:black">参考</span>';break;
                case '低':
                    $severity = '<span style="color:deepskyblue">低</span>';break;
                case '中':
                    $severity = '<span style="color:orange">中</span>';break;
                case '高':
                    $severity = '<span style="color:red">高</span>';break;
            }
            $content .= '<span style="font-weight:bold">风险等级：</span>'.$severity.'<br/>';
            $content .= '<span style="font-weight:bold">描述：</span>'.(!empty($issues_info['details'][$i-1]['desc'])?$issues_info['details'][$i-1]['desc']:'待分析').'<br/>';
            $content .= '<span style="font-weight:bold">影响内容：</span>'.(!empty($issues_info['details'][$i-1]['affects'])?$issues_info['details'][$i-1]['affects']:'待分析').'<br/>';
            $content .= '<span style="font-weight:bold">风险：</span>'.(!empty($issues_info['details'][$i-1]['risk'])?$issues_info['details'][$i-1]['risk']:'待分析').'<br/>';
            $content .= '<span style="font-weight:bold">建议：</span>'.(!empty($issues_info['details'][$i-1]['suggestion'])?$issues_info['details'][$i-1]['suggestion']:'待分析').'<br/>';
            $content .= "<br/><br/>";
        }

        $content .= '</div>';
        $content .= '</body>';
        $content .= '<html>';
        var_dump($content);

        $fp = fopen("E:\\yii\\web\\scanreport\\result_{$id}\\report.html","w");
        fwrite($fp,$content);
        fclose($fp);
    }

    public function getContentFromHTML($url)
    {

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//跳过SSL证书检查
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HEADER,0);
        $html = curl_exec($ch);
        curl_close($ch);

        if($html){
            $doc = new DOMDocument();
            @$doc->loadHTML($html);

            foreach( ( new DOMXPath( $doc ) )->query( '//*[@class="panel-body"]' )
                     as $element )
                $result[] = $doc->saveHTML( $element );

            return $result;
        }else{
            return array();
        }
    }
}

$test = new Scan();
$rs = $test->actionIndex();