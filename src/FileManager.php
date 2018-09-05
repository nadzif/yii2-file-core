<?php

namespace nadzif\file;


use nadzif\file\models\File;
use yii\base\Component;
use yii\db\Connection;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use yii\web\UploadedFile;

/**
 * Class FileManager
 *
 * @package nadzif\file
 */
class FileManager extends Component
{
    /**
     * Table config
     */
    public static $tableName = 'file';

    /**
     * Upload config
     */
    public $directoryMode = 0755;

    public $db = 'db';

    public $allowGuestToUpload = false;
    public $alias              = File::ALIAS_WEB;
    public $uploadFolder       = 'uploads';
    public $maximumSizeAllowed = 88388608;

    public $allowedImageExtensions    = ['jpg', 'jpeg', 'png'];
    public $allowedDocumentExtensions = ['txt', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'];
    public $allowedVideoExtensions    = ['mp4', 'wmv', 'mpg', 'mpeg'];
    public $allowedAudioExtensions    = ['mp3', 'wav'];
    public $allowedOtherExtensions    = [];

    public $deleteWithFile = true;

    /**
     * thumbnail config
     */

    public $thumbnailNameFormat = '{filename}_thumb';
    public $thumbnailExtension  = 'jpg';

    public $defaultImageThumbnail    = null;
    public $defaultDocumentThumbnail = null;
    public $defaultVideoThumbnail    = null;
    public $defaultAudioThumbnail    = null;
    public $defaultOtherThumbnail    = null;

    public $createThumbnail = true;

    public $thumbnailOptions = [
        'width'         => 320,
        'height'        => 240,
        'quality'       => 100,
        'pageIndex'     => 0,
        'videoInterval' => false
    ];

    public static function convertToReadableSize($size)
    {
        $base   = log($size) / log(1024);
        $suffix = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $f_base = floor($base);
        return round(pow(1024, $base - floor($base)), 1) . $suffix[$f_base];
    }

    /**
     * @param UploadedFile[] $fileInstances
     * @param string         $path
     * @param string         $additionalInformation
     *
     * @return File[]
     * @throws ForbiddenHttpException
     */
    public function uploads($fileInstances, $path = null, $additionalInformation = null)
    {

        $model = [];
        foreach ($fileInstances as $fileInstance) {
            $model[] = $this->upload($fileInstance, $path, $additionalInformation);
        }

        return $model;
    }

    /**
     * @param UploadedFile $fileInstance
     * @param string       $path
     * @param string       $additionalInformation
     *
     * @return File
     * @throws ForbiddenHttpException
     */
    public function upload(UploadedFile $fileInstance, $path = null, $additionalInformation = null)
    {

        if (!$this->allowGuestToUpload && \Yii::$app->user->isGuest) {
            throw new ForbiddenHttpException('Please login to continue upload');
        }

        if (!ArrayHelper::isIn($fileInstance->extension, $this->getAllowedExtensions())) {
            throw new ForbiddenHttpException('File extension not allowed');
        }

        $model = new File();

        if (ArrayHelper::isIn($fileInstance->extension, $this->allowedImageExtensions)) {
            $model->type = File::TYPE_IMAGE;
        } elseif (ArrayHelper::isIn($fileInstance->extension, $this->allowedDocumentExtensions)) {
            $model->type = File::TYPE_DOCUMENT;
        } elseif (ArrayHelper::isIn($fileInstance->extension, $this->allowedAudioExtensions)) {
            $model->type = File::TYPE_AUDIO;
        } elseif (ArrayHelper::isIn($fileInstance->extension, $this->allowedVideoExtensions)) {
            $model->type = File::TYPE_VIDEO;
        } else {
            $model->type = File::TYPE_OTHER;
        }


        $model->originalName          = $fileInstance->name;
        $model->alias                 = $this->alias;
        $model->path                  = $this->uploadFolder . DIRECTORY_SEPARATOR;
        $model->path                  .= $path ? $path . DIRECTORY_SEPARATOR : '';
        $model->filename              = self::slug($fileInstance->baseName) . '_' . dechex(time());
        $model->size                  = $fileInstance->size;
        $model->extension             = $fileInstance->extension;
        $model->mimeType              = $fileInstance->type;
        $model->additionalInformation = $additionalInformation;

        $dirPath = \Yii::getAlias($model->alias) . DIRECTORY_SEPARATOR;

        if ($model->alias == File::ALIAS_FRONTEND
            || $model->alias == File::ALIAS_BACKEND
            || $model->alias == File::ALIAS_API
        ) {
            $dirPath .= 'web' . DIRECTORY_SEPARATOR;
        }

        $dirPath .= $model->path;

        if (!is_dir($dirPath)) {
            mkdir($dirPath, $this->directoryMode, true);
        }

        $fullPath = $dirPath . DIRECTORY_SEPARATOR . $model->filename . '.' . $model->extension;


        if ($model->validate() && $fileInstance->saveAs($fullPath) && $model->save()) {
            if ($this->createThumbnail) {
                $model->createThumbnail($this->thumbnailExtension);
            }
        }

        return $model;
    }

    public function getAllowedExtensions()
    {
        return ArrayHelper::merge(
            $this->allowedImageExtensions,
            $this->allowedDocumentExtensions,
            $this->allowedAudioExtensions,
            $this->allowedVideoExtensions,
            $this->allowedOtherExtensions
        );
    }

    public static function slug($text, $length = null)
    {
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
        $text = trim($text, '-');
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = strtolower($text);
        $text = preg_replace('~[^-\w]+~', '', $text);

        if ($length) {
            return substr($text, 0, $length);
        } else {
            return $text;
        }
    }

    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
    }
}