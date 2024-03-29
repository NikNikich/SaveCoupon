<?php

use yii\db\Migration;

/**
 * ТАблица для хранения информации по магазинам.
 * id_process_store' =>номер процесса парсинга при которм была записана данная запись(задумана для контроля парсинга и мягкого удаления при парсинге магазинов)
 * 'id_process_coupon' => номер последнего процесса парсинга по купоном данного магазина
 * 'name' => название магазина
 * 'href' => ссылка на страницу с купонами данного магазина
 * 'status' => статус записи, сделана для мягкого удаления(внимательно прочитал задание отказался от реализации но оставил)
 */
class m190809_211034_create_store_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%store}}', [
            'id' => $this->primaryKey(),
            'id_process_store' => $this->integer(19),
            'id_process_coupon' => $this->integer(19)->defaultValue(null),
            'name' => $this->string(200),
            'href' => $this->string(2100),
            'status' => $this->integer(2)->defaultValue(0),
        ]);
        $this->createIndex(
            'idx-store-id_process_store',
            '{{%store}}',
            'id_process_store'
        );
        $this->addForeignKey(
            'fk-store-id_process_store',
            '{{%store}}',
            'id_process_store',
            'process',
            'id'
        );
        $this->createIndex(
            'idx-store-id_process_coupon',
            '{{%store}}',
            'id_process_coupon'
        );
        $this->addForeignKey(
            'fk-store-id_process_coupon',
            '{{%store}}',
            'id_process_coupon',
            'process',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%store}}');
    }
}
