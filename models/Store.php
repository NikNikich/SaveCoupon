<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "store".
 *
 * @property int $id
 * @property int $id_process_store
 * @property int $id_process_coupon
 * @property string $name
 * @property string $href
 * @property int $status
 *
 * @property Coupon[] $coupons
 * @property Process $processCoupon
 * @property Process $processStore
 */
class Store extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'store';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_process_store', 'id_process_coupon', 'status'], 'integer'],
            [['name'], 'string', 'max' => 200],
            [['href'], 'string', 'max' => 2100],
            [['id_process_coupon'], 'exist', 'skipOnError' => true, 'targetClass' => Process::className(), 'targetAttribute' => ['id_process_coupon' => 'id']],
            [['id_process_store'], 'exist', 'skipOnError' => true, 'targetClass' => Process::className(), 'targetAttribute' => ['id_process_store' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'id_process_store' => 'Процесс при записи магазина',
            'id_process_coupon' => 'Последний процесс поиска купонов',
            'name' => 'Имя',
            'href' => 'Ссылка',
            'status' => 'Статус',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCoupons()
    {
        return $this->hasMany(Coupon::className(), ['id_store' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProcessCoupon()
    {
        return $this->hasOne(Process::className(), ['id' => 'id_process_coupon']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProcessStore()
    {
        return $this->hasOne(Process::className(), ['id' => 'id_process_store']);
    }
}
