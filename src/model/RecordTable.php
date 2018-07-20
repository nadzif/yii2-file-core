<?php
/**
 * Created by PhpStorm.
 * User: Nadzif Glovory
 * Date: 7/21/2018
 * Time: 12:45 AM
 */

namespace nadzif\file\model;


use nadzif\file\File;
use yii\db\ActiveRecord;

/**
 * Class RecordTable
 *
 * @package nadzif\file\model
 * @property integer $id
 * @property string  $type
 * @property string  $originalName
 * @property string  $alias
 * @property string  $path
 * @property string  $filename
 * @property double  $size
 * @property string  $extension
 * @property string  $mimeType
 * @property string  $thumbnailFilename
 * @property double  $thumbnailSize
 * @property string  $thumbnailExtension
 * @property string  $thumbnailMimeType
 * @property string  $additionalInformation
 * @property string  $createdAt
 * @property integer $createdBy
 * @property string  $updatedAt
 * @property integer $updatedBy
 * @property string  $deletedAt
 * @property integer $deletedBy
 * @property integer $flag
 */
class RecordTable extends ActiveRecord
{
    const FLAG_INSERTED = 0;
    const FLAG_RESTORED = 1;
    const FLAG_UPDATED  = 2;
    const FLAG_DELETED  = 3;

    const TYPE_IMAGE    = 'image';
    const TYPE_DOCUMENT = 'document';
    const TYPE_VIDEO    = 'video';
    const TYPE_AUDIO    = 'audio';
    const TYPE_OTHER    = 'other';


    const ALIAS_BACKEND  = '@backend';
    const ALIAS_FRONTEND = '@frontend';
    const ALIAS_WEB      = '@web';

    /** @var File */
    private $fileManager;

    public static function tableName()
    {
        return '{{%' . File::$tableName . '}}';
    }

    public function init()
    {
        $this->fileManager = \Yii::$app->fileManager;
        parent::init();
    }

    public function rules()
    {
        $rules   = parent::rules();
        $rules[] = ['size', 'double', 'max' => $this->fileManager->maximumAllowedSize];
        return $rules;
    }

    public function createThumbnail($extension = 'jpg', $replaceExist = true, $filename = null)
    {

    }

    public function getFullPath()
    {

    }

    public function getImagePreview()
    {

    }

    public function getSource()
    {

    }

    public function deleteFile()
    {

    }

    public function delete()
    {
        return parent::delete();
    }


}