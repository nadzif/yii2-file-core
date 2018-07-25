<?php

use yii\db\Migration;

/**
 * Handles the creation of table `file`.
 */
class m180720_000000_file_init extends Migration
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

        $this->createTable(\nadzif\file\models\File::tableName(), [
            'id'                    => $this->bigPrimaryKey()->unsigned(),
            'type'                  => $this->string(50),
            'originalName'          => $this->string()->notNull(),
            'alias'                 => $this->string()->notNull(),
            'path'                  => $this->string()->notNull(),
            'filename'              => $this->string()->notNull(),
            'size'                  => $this->double()->notNull(),
            'extension'             => $this->string(15)->notNull(),
            'mimeType'              => $this->string(100)->notNull(),
            'thumbnailFilename'     => $this->string(),
            'thumbnailSize'         => $this->double(),
            'thumbnailExtension'    => $this->string(15),
            'thumbnailMimeType'     => $this->string(100),
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
        $this->dropTable(\nadzif\file\models\File::tableName());
    }
}
