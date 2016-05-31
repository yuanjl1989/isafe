<?php
/**
 * Created by PhpStorm.
 * User: Metersbonwe
 * Date: 2016/5/24
 * Time: 14:53
 */

namespace app\commands;

use Yii;
use yii\console\Controller;
use app\models\SafeExt;
use app\models\SafeList;


class ScanController extends Controller
{
    public function actionIndex()
    {
        $wvs_console = "D:\\WVS10\\wvs_console";
        $scan_mode = [1 => 'Quick', 2 => 'Heuristic', 3 => 'Extensive'];
        $scan_profile = ['1' => 'Default', '2' => 'AcuSensor', '3' => 'Blind_SQL_Injection', '4' => 'CSRF', '5' => 'Directory_And_File_Checks', '6' => 'Empty', '7' => 'File_Upload', '8' => 'GHDB', '9' => 'High_Risk_Alerts', '10' => 'Network_Scripts', '11' => 'Parameter_Manipulation', '12' => 'SQL_Injection', '13' => 'Text_Search', '14' => 'Weak_Passwords', '15' => 'Web_Applications', '16' => 'Xss'];

        /*该部分用于处理异常中止的数据，进行中的数据如果已经超过24小时未发生变化，状态置为3，并更新时间*/
        $sql = 'update safe_list set status=3,update_at=now() where status=2 and unix_timestamp(update_at)<unix_timestamp(now())-86400';
        Yii::$app->db->createCommand($sql)->execute();

        /*控制同时进行扫描的个数，若超过5个，则退出脚本*/
        $sql_2 = 'select count(*) as num from safe_list where status=2';
        $count_doing = Yii::$app->db->createCommand($sql_2)->queryAll();
        if($count_doing[0]['num']>5){
            exit;
        }

        $date = date('Y-m-d H:i:s');
        foreach ($this->getScanInfo() as $k => $v) {
            //需要进行扫描的数据，更新其状态及时间
            $items = SafeList::findOne($v['id']);
            $items->status = 2;
            $items->update_at = $date;
            $succ = $items->save();

            $safe_info_ext = SafeExt::find()->where(['safe_id'=>$v['id']])->asArray()->one();
            $report_path = "E:\\yii\\web\\scanreport\\result_{$v['id']}";

            if ($succ) {
                //根据页面配置中填写的信息，拼接成扫描命令，--abortscanafter=600该参数作用为：扫描超过10个小时则终止，避免出现假死
                $command_main = "{$wvs_console} /Scan {$v['url']} /Profile {$scan_profile[$v['profile']]} /Save /GenerateReport /ReportFormat PDF /SaveFolder ".$report_path;
                $command_mail = " /EmailAddress {$safe_info_ext['user_mail']}";
                $command_auth = " --HtmlAuthUser={$v['login_username']} --HtmlAuthPass={$v['login_password']}";
                $command_ext = " --ScanningMode={$scan_mode[$v['mode']]} --abortscanafter=600";

                if(!empty($v['login_username']) && !empty($v['login_password'])){
                    $command = $command_main.$command_mail.$command_auth.$command_ext;
                    if($items->is_mail == 2){
                        $command = $command_main.$command_auth.$command_ext;
                    }
                }else{
                    $command = $command_main.$command_mail.$command_ext;
                    if($items->is_mail == 2){
                        $command = $command_main.$command_ext;
                    }
                }

                $res = system($command,$out);

                //根据执行结果更新数据状态，成功则更新为4（已完成），否则为3（取消）
                $new_date = date('Y-m-d H:i:s');
                $items->status = $out?4:3;
                $items->update_at = $new_date;
                $items->save();
            }
        }
    }

    /**
     * 根据创建时间先后，获取status=1（新建）的申请数据
     */
    public function getScanInfo()
    {
        $safe_info = SafeList::find()->where(['status' => 1])->orderBy(['create_at' => SORT_ASC])->asArray()->all();

        return $safe_info;
    }
}