<?php

use yii\db\Schema;
use yii\db\Migration;

class m150927_060316_cronjob_init extends Migration
{
        public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%cron_job}}', [
            'id_cron_job' => Schema::TYPE_PK,
            'controller'=> Schema::TYPE_STRING. ' NOT NULL',
            'action'=> Schema::TYPE_STRING. ' NOT NULL',
            'limit'=> Schema::TYPE_INTEGER,
            'offset'=> Schema::TYPE_INTEGER,
            'running' => Schema::TYPE_SMALLINT. ' UNSIGNED NOT NULL',
            'success' => Schema::TYPE_SMALLINT. ' UNSIGNED NOT NULL',
            'started_at' => Schema::TYPE_INTEGER . ' UNSIGNED',
            'ended_at' => Schema::TYPE_INTEGER . ' UNSIGNED',
            'last_execution_time' => Schema::TYPE_FLOAT,
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('{{%cron_job}}');
    }
}
