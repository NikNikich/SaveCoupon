<?php

use yii\db\Migration;

/**
 * Таблица для фиксации запусков парсинга
 * 'date_start' => дата начала процесса парсинга
 * 'date_end' => дата окончания процесса парсинга
 */
class m190809_211014_create_process_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%process}}', [
            'id' => $this->primaryKey(),
            'date_start' => $this->timestamp()->defaultValue(null)->append('ON UPDATE CURRENT_TIMESTAMP'),
            'date_end' => $this->timestamp()->defaultValue(null),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%process}}');
    }
}
