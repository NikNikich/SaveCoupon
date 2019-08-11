<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "process".
 *
 * @property int $id
 * @property string $date_start
 * @property string $date_end
 *
 * @property Store[] $stores
 * @property Store[] $stores0
 */
class Process extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'process';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date_start', 'date_end'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date_start' => 'Date Start',
            'date_end' => 'Date End',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStores()
    {
        return $this->hasMany(Store::className(), ['id_process_coupon' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStores0()
    {
        return $this->hasMany(Store::className(), ['id_process_store' => 'id']);
    }
}
