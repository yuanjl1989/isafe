<?php
/**
 * Created by PhpStorm.
 * User: Metersbonwe
 * Date: 2016/8/19
 * Time: 14:25
 */
class AppScan{

    public function getAppScanIssue($id = 109)
    {
        $pdftotext_path = 'E:\\xpdf\\';

        $report_path = 'E:\\yii\\web\\scanreport\\result_'.$id.'\\';

        $command = $pdftotext_path . 'pdftotext.exe ' . $report_path . 'report.pdf';

        system($command, $output);

        if ($output === 0) {
            $content = file_get_contents($report_path . 'report.txt');//var_dump($content);
            preg_match("/\r\n按问题类型分类的问题\r\n(.*?)\r\n修订建议/s", $content, $matches);//var_dump($matches);die;
            preg_match("/\r\n该报告包含由 IBM Security AppScan Standard 执行的 Web 应用程序安全性扫描的结果。.*?\r\n\r\n一般信息\r\n\r\n(.*?)应用程序服务器：/s", $content, $match_info);//var_dump($match_info);die;
            preg_match("/扫描开始时间： (.*?)\r\n/s", $match_info[1], $start_time);
            preg_match("/测试策略：\r\n\r\n(.*?)\r\n/s", $match_info[1], $profile);
            preg_match("/主机\r\n\r\n(.*?)\r\n/s", $match_info[1], $url);
            preg_match("/操作系统：\r\n\r\n(.*?)\r\n/s", $match_info[1], $os);
            preg_match("/Web 服务器： (.*?)\r\n\r\n/s", $match_info[1], $server);

            $summary_info = array('start_time'=>$start_time[1],'profile'=>$profile[1],'url'=>$url[1],'os'=>$os[1],'server'=>$server[1]);

            $mat_arr = explode(' ', $matches[1]);
            foreach ($mat_arr as $key => $item) {
                if (is_numeric($item) && $item < 100) {
                    if ($key == count($mat_arr) - 1) {
                        unset($mat_arr[$key]);
                    }
                    elseif (!preg_match("/^[a-zA-Z\s]+$/",$mat_arr[$key-1])) {
                        $mat_arr[$key] = '||';
                    }
                }

                if(strstr($item,date('Y/n/j'))){
                    $item_arr = explode("\r\n",$item);
                    if(count(array_filter($item_arr))>3){
                        $new_key = count($item_arr)-1;
                        $mat_arr[$key] = '||'.$item_arr[$new_key];
                    }else{
                        $mat_arr[$key] = '||';
                    }
                }
            }
            $issues = explode('||', implode(' ', $mat_arr));//var_dump($issues);die;

            foreach ($issues as $v) {
                $v_trim = trim($v);
                if($v_trim){
                    @preg_match("/\r\n\r\n($v_trim\r\n.*?)测试请求和响应：/s", $content, $match);//var_dump($match);die;
//                $match[1] = str_replace('<','%3c',$match[1]);
                    $match[1] = htmlspecialchars($match[1]);
                    preg_match("/(.*?)严重性：/s", $match[1], $issue_name);
                    preg_match("/严重性：(.*?)URL：/s", $match[1], $severity);
                    preg_match("/URL：(.*?)实体：/s", $match[1], $affects);
                    preg_match("/风险：(.*?)原因：/s", $match[1], $risk);
                    preg_match("/固定值：(.*?)推理：/s", $match[1], $suggestion);
                    preg_match("/推理：(.*?)/U", $match[1], $desc);

                    $issues_arr[] = array('issue_name' => trim(str_replace("\r\n", '', $issue_name[1])), 'severity' => trim(str_replace("\r\n", '', $severity[1])), 'affects' => trim(str_replace("\r\n", '', $affects[1])), 'risk' => trim(str_replace("\r\n", '', $risk[1])), 'suggestion' => trim(str_replace("\r\n", '', $suggestion[1])), 'desc' => trim(str_replace("\r\n", '', $desc[1])));
                }
            }
//            var_dump($issues_arr);die;
            $high = $mid = $low = $info = 0;
            foreach ($issues_arr as $item){
                if(strstr($item['severity'],'高')){
                    $high++;
                }
                if(strstr($item['severity'],'中')){
                    $mid++;
                }
                if(strstr($item['severity'],'低')){
                    $low++;
                }
                if(strstr($item['severity'],'参考')){
                    $info++;
                }
            }
            $summary_info = array_merge($summary_info,array('high'=>$high,'mid'=>$mid,'low'=>$low,'info'=>$info));
            $ret = array('summary'=>$summary_info,'details'=>$issues_arr) ;
//            var_dump($ret);

            return $ret;
        }
    }
}

$test = new AppScan();
$test->getAppScanIssue();