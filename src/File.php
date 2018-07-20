<?php
/**
 * Created by PhpStorm.
 * User: Nadzif Glovory
 * Date: 7/21/2018
 * Time: 12:34 AM
 */

namespace nadzif\file;


use nadzif\file\model\RecordTable;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use yii\web\UploadedFile;

class File extends Component
{
    /**
     * Table config
     */
    public $tableName = 'file';

    /**
     * Upload config
     */
    public $directoryMode = 0777;

    public $allowGuestToUpload = false;
    public $alias              = RecordTable::ALIAS_WEB;
    public $uploadFolder       = 'uploads';
    public $maximumAllowedSize = 1024 * 1024 * 8;

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
     * @return RecordTable[]
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
     * @return RecordTable
     * @throws ForbiddenHttpException
     */
    public function upload(UploadedFile $fileInstance, $path = null, $additionalInformation = null)
    {

        if (!$this->allowGuestToUpload && \Yii::$app->user->isGuest) {
            throw new ForbiddenHttpException();
        }

        $allowedExtensions = ArrayHelper::merge($this->allowedImageExtensions, $this->allowedDocumentExtensions,
            $this->allowedAudioExtensions, $this->allowedVideoExtensions, $this->allowedOtherExtensions);

        if (ArrayHelper::isIn($fileInstance->extension, $allowedExtensions)) {
            throw new ForbiddenHttpException();
        }

        $model = new RecordTable();

        $model->originalName = $fileInstance->name;
        $model->alias        = $this->alias;

        $model->path                  = $this->uploadFolder . DIRECTORY_SEPARATOR;
        $model->path                  .= $path ? $path . DIRECTORY_SEPARATOR : '';
        $model->filename              = self::slug($fileInstance->name) . '_' . time();
        $model->size                  = $fileInstance->size;
        $model->extension             = $fileInstance->extension;
        $model->mimeType              = $fileInstance->type;
        $model->additionalInformation = $additionalInformation;

        $dirPath = \Yii::getAlias($model->alias) . DIRECTORY_SEPARATOR;

        if ($model->alias == RecordTable::ALIAS_FRONTEND || $model->alias == RecordTable::ALIAS_BACKEND) {
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



}