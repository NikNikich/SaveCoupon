<?php

use yii\db\Migration;

/**
 * Class m190704_142801_add_user_role
 */
class m190704_142801_add_user_role extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->batchInsert('{{%auth_item}}', ['type', 'name', 'description'], [
            [1, 'user', 'User'],
            [1, 'moder', 'Moderator'],
            [1, 'admin', 'Administrator'],
        ]);

        $this->batchInsert('{{%auth_item_child}}', ['parent', 'child'], [
            ['moder', 'user'],
            ['admin', 'moder'],
        ]);

        $this->execute('INSERT INTO {{%auth_assignment}} (item_name, user_id) SELECT \'admin\', u.id FROM {{%user}} u ORDER BY u.id');
    }

    public function down()
    {
        $this->delete('{{%auth_items}}', ['name' => ['user', 'admin']]);
    }

}
