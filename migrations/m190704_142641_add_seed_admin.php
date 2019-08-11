<?php

use yii\db\Migration;

/**
 * Class m190704_142641_add_seed_admin
 */
class m190704_142641_add_seed_admin extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->insert('user',[
            'username' => 'Administrator',
            'auth_key' => Yii::$app->getSecurity()->generateRandomString(),
            'password_hash' => Yii::$app->getSecurity()->generatePasswordHash('iamadmin'),
            'email' => 'Admin@example.exe',
            'status' => 10,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
}
