<?php
/**
 * Created by PhpStorm.
 * User: Metersbonwe
 * Date: 2016/5/16
 * Time: 13:56
 */

namespace app\models;

use yii\db\ActiveRecord;

class SafeExt extends ActiveRecord
{
    /**
     * @return string 返回该AR类关联的数据表名
     */
    public static function tableName()
    {
        return 'safe_ext';
    }
}