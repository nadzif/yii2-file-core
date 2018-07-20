<?php

use yii\db\Migration;

/**
 * Handles the creation of table `file`.
 */
class m180720_174234_create_file_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        switch (\Yii::$app->db->driverName) {
            case 'mysql':
                $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
                break;
            default:
                $tableOptions = null;
        }

        $this->createTable(\nadzif\file\model\RecordTable::tableName(), [
            'id'                    => $this->bigPrimaryKey()->unsigned(),
            'type'                  => $this->string(50),
            'originalName'          => $this->string()->notNull(),
            'alias'                 => $this->string()->notNull(),
            'path'                  => $this->string()->notNull(),
            'fileName'              => $this->string()->notNull(),
            'size'                  => $this->double()->notNull(),
            'extension'             => $this->string()->notNull(),
            'mimeType'              => $this->string()->notNull(),
            'thumbnailFileName'     => $this->string(),
            'thumbnailSize'         => $this->double(),
            'thumbnailExtension'    => $this->string(),
            'thumbnailMimeType'     => $this->string(),
            'additionalInformation' => $this->text(),
            'createdBy'             => $this->bigInteger()->unsigned()->null(),
            'createdAt'             => $this->dateTime(),
            'updatedBy'             => $this->bigInteger()->unsigned()->null(),
            'updatedAt'             => $this->dateTime(),
            'deletedBy'             => $this->bigInteger()->unsigned()->null(),
            'deletedAt'             => $this->dateTime(),
            'flag'                  => $this->integer(),
        ], $tableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable(\nadzif\file\model\RecordTable::tableName());
    }
}
