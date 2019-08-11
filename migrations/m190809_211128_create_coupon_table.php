<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%coupon}}`.
 */
class m190809_211128_create_coupon_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%coupon}}', [
            'id' => $this->primaryKey(),
            'id_store' => $this->integer(19),
            'title' => $this->string(200),
            'text' => $this->string(2000)->defaultValue(null),
            'logo' => $this->string(2100)->defaultValue(null),
            'period' => $this->string(200)->defaultValue(null),
        ]);
        $this->createIndex(
            'idx-coupon-id_store',
            '{{%coupon}}',
            'id_store'
        );
        $this->addForeignKey(
            'fk-coupon-id_store',
            '{{%coupon}}',
            'id_store',
            'store',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%coupon}}');
    }
}
