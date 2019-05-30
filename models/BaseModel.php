<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * 这方法有个问题，只能调用默认的db配置
 * Class BaseModel
 * @package app\models
 */
abstract class BaseModel extends ActiveRecord
{
    const INSERT_LIMIT = 500;
    const DELETE_LIMIT = 500;

    /**
     * ActiveRecord没有批量入库方法，这里给他封装一个
     * @param $data
     * @param $slave
     * @return int
     */
    public function batchInsert($data, $slave = 'db')
    {
        $fields = array_keys($data[0]);
        $batchData = [];
        foreach ($data as $value) {
            $batchData[] = array_values($value);
        }
        $result = 0;
        switch ($slave) {
            case 'ad':
                $db = Yii::$app->get('db_ad');
                break;
            default:
                $db = Yii::$app->db;
        }
        //做下入库限制，免得数据库爆了
        $insertData = array_chunk($batchData, self::INSERT_LIMIT);
        foreach ($insertData as $value) {
            $count = $db->createCommand()->batchInsert(static::tableName(), $fields, $value)->execute();
            $result += $count;
        }
        return $result;
    }

    /**
     * 更新插入方法
     * @param $table
     * @param $datas
     * @param string $slave
     */
    public function insertOrUpdate($datas, $db = 'db')
    {
        switch ($db) {
            case 'ad':
                $db = Yii::$app->get('db_ad');
                break;
            default:
                $db = Yii::$app->db;
        }
        if (empty($datas)) {
            return 0;
        }
        $columns = array_keys(current($datas));
        $sql = $db->getQueryBuilder()
            ->batchInsert(static::tableName(), $columns, $datas);
        $appendSql = array();

        foreach ($columns as $column) {
            $appendSql[] = "{$column} = VALUES({$column})";
        }

        // 使用DUPLICATE KEY UPDATE对已存在的数据进行更新，不存在的数据进行写入
        $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(',', $appendSql);

        return $db->createCommand()
            ->setSql($sql)
            ->execute();
    }

}