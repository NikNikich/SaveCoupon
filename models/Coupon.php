<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "coupon".
 *
 * @property int $id
 * @property int $id_store
 * @property string $title
 * @property string $text
 * @property string $logo
 * @property string $period
 *
 * @property Store $store
 */
class Coupon extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'coupon';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_store'], 'integer'],
            [['title', 'period'], 'string', 'max' => 200],
            [['text'], 'string', 'max' => 2000],
            [['logo'], 'string', 'max' => 2100],
            [['id_store'], 'exist', 'skipOnError' => true, 'targetClass' => Store::className(), 'targetAttribute' => ['id_store' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_store' => 'Id Store',
            'name' => 'Name',
            'title' => 'Title',
            'logo' => 'Logo',
            'period' => 'Period',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore()
    {
        return $this->hasOne(Store::className(), ['id' => 'id_store']);
    }
}
