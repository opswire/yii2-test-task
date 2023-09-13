<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%url_requests}}`.
 */
class m230913_064525_create_url_requests_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%url_requests}}', [
            'hash_string' => $this->string(32)->notNull()->comment('MD5 hash of the URL'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            'url' => $this->text()->notNull(),
            'status_code' => $this->smallInteger()->notNull(),
            'query_count' => $this->integer()->defaultValue(1),
            'failed_attempts' => $this->integer()->defaultValue(0)->comment('The number of failed attempts'),
        ]);

        $this->addPrimaryKey('pk_hash_string', '{{%url_requests}}', 'hash_string');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%url_requests}}');
    }
}
