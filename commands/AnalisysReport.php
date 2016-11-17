<?php

/**
 * Created by PhpStorm.
 * User: Metersbonwe
 * Date: 2016/8/19
 * Time: 14:25
 */
class AppScan
{
    public function getAppScanIssue($id)
    {
        $report_path = 'E:\\yii\\web\\scanreport\\result_' . $id . '\\';

        $content = file_get_contents($report_path . 'report_appscan_init.html');//

        $doc = new DOMDocument();
        $meta = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        @$doc->loadHTML($meta . $content);
        $xpath = new DOMXPath($doc);

        $summary_html = $xpath->query('//*[@class="datatable nowordwrap"]');
        $summary_issue = strip_tags($doc->saveHTML($summary_html[0]));
        $summary_issue_arr = explode("\n\n\n", $summary_issue);
        unset($summary_issue_arr[0], $summary_issue_arr[1]);
        foreach (array_values($summary_issue_arr) as $k => $summary) {
            $tmp = explode("\n", trim($summary, "\n"));//var_dump($tmp);die;
            $summary_info[$k]['level'] = @$tmp[0];
            $summary_info[$k]['summary'] = @$tmp[1];
            $summary_info[$k]['count'] = @$tmp[2];
        }
        array_pop($summary_info);

        preg_match("/id=\"issuesByIssueType\"(.*?)id=\"Advisories\"/s", $content, $details_html);
        @$doc->loadHTML($meta . $details_html[0]);
        $xpath = new DOMXPath($doc);

        $rows_html = $xpath->query('//*[@class="row"]');
        foreach ($rows_html as $k => $row_html) {
            $row_tmp = explode("\n", strip_tags($doc->saveHTML($row_html)));
            $row_arr[$k] = $row_tmp[2];
        }
        for ($i = 1; $i <= count($row_arr) / 7; $i++) {
            $row_arr_new[$i - 1]['affects'] = $row_arr[($i - 1) * 7 + 1];
            $row_arr_new[$i - 1]['risk'] = $row_arr[($i - 1) * 7 + 3];
            $row_arr_new[$i - 1]['suggestion'] = $row_arr[($i - 1) * 7 + 5];
            $row_arr_new[$i - 1]['desc'] = $row_arr[($i - 1) * 7 + 6];
        }

        $j = $high = $mid = $low = $info = 0;
        foreach ($summary_info as $k => $v) {
            $summary_info[$k]['affects'] = $row_arr_new[$j]['affects'];
            $summary_info[$k]['risk'] = $row_arr_new[$j]['risk'];
            $summary_info[$k]['suggestion'] = $row_arr_new[$j]['suggestion'];
            $summary_info[$k]['desc'] = $row_arr_new[$j]['desc'];
            switch ($summary_info[$k]['level']) {
                case '高':
                    $high++;
                    break;
                case '中':
                    $mid++;
                    break;
                case '低':
                    $low++;
                    break;
                case '参':
                    $info++;
                    break;
            }
            $j += $v['count'];
            unset($summary_info[$k]['count']);
        }

        preg_match("/一般信息<a name=\"generalInformation\"(.*?)loginSettings/s", $content, $content_generalInformation);

        @$doc->loadHTML($meta . $content_generalInformation[0]);
        $xpath = new DOMXPath($doc);
        $generalInformation_html = $xpath->query('//*[@class="row"]');
        foreach ($generalInformation_html as $k => $generalInformation) {
            $generalInformations[$k] = strip_tags($doc->saveHTML($generalInformation));
            $generalInformation_arr[$k] = explode("\n", trim($generalInformations[$k], "\n"));
        }
        unset($generalInformation_arr[0]);
        $summary = ['start_time' => $generalInformation_arr[1][1], 'profile' => $generalInformation_arr[2][1], 'url' => $generalInformation_arr[3][1], 'os' => $generalInformation_arr[4][1], 'server' => $generalInformation_arr[5][1], 'level_count' => ['count' => ($high + $mid + $low + $info), 'high' => $high, 'mid' => $mid, 'low' => $low, 'info' => $info]];

        $ret = ['summary' => $summary, 'content' => $summary_info];

        return $ret;

    }
}

class WVS
{
    public function getWvsIssue($url)
    {
        $summary = $this->queryWvsScans($url);
        $summary_alerts = $this->queryWvsAlerts($summary['scid']);
        $tmp = $tmp_issue = array();
        if (!empty($summary_alerts)) {
            foreach ($summary_alerts as $alert) {
                $tmp[] = $alert['issues'];
            }
            $issues_arr = array_unique($tmp);

            foreach ($summary_alerts as $k => $v){
                foreach ($issues_arr as $issue){
                    if($v['issues'] == $issue){
                        if(!$tmp_issue[$issue]){
                            $tmp_issue[$issue] = $summary_alerts[$k];
                        }else{
                            $tmp_issue[$issue]['affects'] .= ','.$summary_alerts[$k]['affects'];
                        }
                    }
                }
            }

            $summary_alerts = array_values($tmp_issue);
            $summary['level_count']['count'] = $summary['level_count']['low'] = $summary['level_count']['mid'] = $summary['level_count']['high'] = 0;

            foreach ($summary_alerts as $k => $alert) {
                $summary_alerts[$k]['impact_text'] = $this->queryWvsTexts($alert['impact_id'])[0]['content'];
                $summary_alerts[$k]['desc_text'] = $this->queryWvsTexts($alert['desc_id'])[0]['content'];
                $summary_alerts[$k]['recm_text'] = $this->queryWvsTexts($alert['recm_id'])[0]['content'];
                $summary_alerts[$k]['ref_content'] = $this->queryAlerts2Refs($alert['scid'], $alert['alid']);
                $summary_alerts[$k]['affects'] = implode(',',array_unique(explode(',',$summary_alerts[$k]['affects'])));
                unset($summary_alerts[$k]['scid'], $summary_alerts[$k]['alid'], $summary_alerts[$k]['impact_id'], $summary_alerts[$k]['desc_id'], $summary_alerts[$k]['recm_id']);
                switch ($alert['severity']) {
                    case '1':
                        $summary_alerts[$k]['severity'] = '低';
                        $summary['level_count']['low']++;
                        break;
                    case '2':
                        $summary_alerts[$k]['severity'] = '中';
                        $summary['level_count']['mid']++;
                        break;
                    case '3':
                        $summary_alerts[$k]['severity'] = '高';
                        $summary['level_count']['high']++;
                        break;
                }

            }
            $summary['level_count']['count'] = array_sum($summary['level_count']);
        }
        unset($summary['scid']);
        $ret = ['summary' => $summary, 'content' => $summary_alerts];
        return $ret;
    }

    public function queryWvsAlerts($scid)
    {
        $conn = new COM("ADODB.Connection");
        $connstr = "DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=" . "D:\\WVS10\\vulnscanresults.mdb";
        $conn->Open($connstr);

        $query = "select scid,alid,algroup,affects,severity,impact_id,desc_id,recm_id from WVS_alerts where scid={$scid} and severity BETWEEN 1 and 3 ORDER by severity DESC ";
        $rs = $conn->Execute($query);

        $issues = array('scid', 'alid', 'issues', 'affects', 'severity', 'impact_id', 'desc_id', 'recm_id');

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

    public function queryWvsTexts($text_id)
    {
        $conn = new COM("ADODB.Connection");
        $connstr = "DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=" . "D:\\WVS10\\vulnscanresults.mdb";
        $conn->Open($connstr);

        $query = "select content from WVS_texts where text_id={$text_id}";
        $rs = $conn->Execute($query);

        $issues = array('content');

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

    public function queryAlerts2Refs($scid, $alid)
    {
        $conn = new COM("ADODB.Connection");
        $connstr = "DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=" . "D:\\WVS10\\vulnscanresults.mdb";
        $conn->Open($connstr);

        $query = "select b.title,b.url from WVS_alerts2refs_XREF a,WVS_refs b where a.refid=b.refid and a.scid={$scid} and a.alid={$alid}";
        $rs = $conn->Execute($query);

        $issues = array('title', 'url');

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

        $query = "select top 1 a.scid,a.starturl,a.starttime,a.finishtime,b.banner,b.os,b.technologies from WVS_scans a,WVS_servers b where a.starturl='" . $url . "' and a.serverid=b.serverid order by a.starttime desc";
        $rs = $conn->Execute($query);

        $table_scans = array('scid', 'url', 'starttime', 'finishtime', 'servers', 'os', 'language');

        $content = array();
        $j = 0;
        while (!$rs->EOF) {
            for ($i = 0; $i < $rs->Fields->count; $i++) {
                @$content[$j][$table_scans[$i]] = (string)$rs->Fields($i)->Value;
            }
            $j++;
            $rs->MoveNext();
        }
        return $content[0];
    }
}

//$wvs = new WVS();
//$ret = $wvs->getWvsIssue('http://qa.it.mb.com/');
//var_dump($ret);

//$app = new AppScan();
//var_dump($app->getAppScanIssue(133));