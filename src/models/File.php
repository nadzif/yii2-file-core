<?php

namespace nadzif\file\models;


use nadzif\file\FileManager;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\imagine\Image;

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
 * @property string  $updatedAt
 */
class File extends ActiveRecord
{
    const TYPE_IMAGE    = 'image';
    const TYPE_DOCUMENT = 'document';
    const TYPE_VIDEO    = 'video';
    const TYPE_AUDIO    = 'audio';
    const TYPE_OTHER    = 'other';

    // list of all (basic, advanced) application
    const ALIAS_APP     = '@app';
    const ALIAS_VENDOR  = '@vendor';
    const ALIAS_RUNTIME = '@runtime';
    const ALIAS_WEB     = '@web';
    const ALIAS_WEBROOT = '@webroot';
    const ALIAS_TESTS   = '@tests';

    // list of advanced application
    const ALIAS_API      = '@api';
    const ALIAS_COMMON   = '@common';
    const ALIAS_BACKEND  = '@backend';
    const ALIAS_FRONTEND = '@frontend';
    const ALIAS_CONSOLE  = '@console';

    /** @var FileManager */
    private $fileManager;

    public static function getDb()
    {
        return \Yii::$app->fileManager->db;
    }

    public static function tableName()
    {
        return '{{%' . FileManager::$tableName . '}}';
    }

    public function behaviors()
    {
        return [
            'timestampBehavior' => [
                'class'              => TimestampBehavior::className(),
                'createdAtAttribute' => 'createdAt',
                'updatedAtAttribute' => 'updatedAt',
                'value'              => date('Y-m-d H:i:s')
            ]
        ];
    }

    public function init()
    {
        $this->fileManager = \Yii::$app->fileManager;
        parent::init();
    }

    public function rules()
    {
        $rules   = parent::rules();
        $rules[] = ['size', 'double', 'max' => $this->fileManager->maximumSizeAllowed];
        return $rules;
    }

    public function createThumbnail($extension = null, $replaceExist = true, $filename = null)
    {
        $thumbnailExtension = $extension ?: $this->fileManager->thumbnailExtension;
        $thumbnailFilename  = $filename
            ?: strtr($this->fileManager->thumbnailNameFormat, ['{filename}' => $this->filename]
            );

        $fileLocation      = $this->getFullPath() . $this->getFullName();
        $thumbnailLocation = $this->getFullPath() . $thumbnailFilename . '.' . $thumbnailExtension;
        $thumbnailOptions  = $this->fileManager->thumbnailOptions;
        $thumbnailUploaded = false;


        if ($this->type == self::TYPE_IMAGE) {
            $thumbnailUploaded =
                Image::thumbnail($fileLocation, $thumbnailOptions['width'], $thumbnailOptions['height'])
                    ->save($thumbnailLocation, ['quality' => $thumbnailOptions['quality']]);

        } elseif ($this->type == self::TYPE_DOCUMENT) {
            try {
                $imagick = new \Imagick($fileLocation . '[' . $this->fileManager->thumbnailOptions['pageIndex'] . ']');
                $imagick->setimageformat($this->fileManager->thumbnailExtension);
                $imagick->setImageColorspace(255);
                $imagick->thumbnailimage($thumbnailOptions['width'], $thumbnailOptions['height']);

                $thumbnailUploaded &= $imagick->writeImage($thumbnailLocation);

                $imagick->clear();
                $imagick->destroy();
            } catch (\Exception $e) {
                return true;
            } catch (\ImagickException $e) {
                return true;
            }

        } elseif ($this->type == self::TYPE_AUDIO) {

        } elseif ($this->type == self::TYPE_VIDEO) {
            try {
                $ffmpeg = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'ffmpeg.exe' : 'ffmpeg';

                if ($thumbnailOptions['videoInterval']) {
                    $realSeconds = $thumbnailOptions['videoInterval'];
                } else {
                    $time = exec($ffmpeg . " -i $fileLocation 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//");

                    $duration          = explode(":", $time);
                    $durationInSeconds = $duration[0] * 3600 + $duration[1] * 60 + round($duration[2]);
                    $durationMiddle    = $durationInSeconds / 2;
                    $minutes           = $durationMiddle / 60;
                    $realMinutes       = floor($minutes);
                    $realSeconds       = round(($minutes - $realMinutes) * 60);
                }

                $thumbnailSize = $thumbnailOptions['width'] . 'x' . $thumbnailOptions['height'];
                $cmd           = $ffmpeg . " -i \"" . $fileLocation . "\" -deinterlace -an -ss " . $realSeconds
                    . " -f mjpeg -t 1 -r 1 -y -s " . $thumbnailSize . " \"" . $thumbnailLocation . "\" 2>&1";

                exec($cmd);

                $thumbnailUploaded = true;
            } catch (\Exception $e) {
                return true;
            }
        } else {

        }

        if ($thumbnailUploaded) {
            if ($this->hasThumbnail() && $replaceExist) {
                $this->deleteThumbnail();
            }

            $this->thumbnailExtension = $thumbnailExtension;
            $this->thumbnailFilename  = $thumbnailFilename;
            $this->thumbnailMimeType  = mime_content_type($thumbnailLocation);
            $this->thumbnailSize      = filesize($thumbnailLocation);
            $this->save();
            $this->refresh();
        }
    }

    private function getFullPath()
    {
        $fullPath = \Yii::getAlias($this->alias) . DIRECTORY_SEPARATOR;

        if ($this->requireWebFolder()) {
            $fullPath .= 'web' . DIRECTORY_SEPARATOR;
        }

        $fullPath .= $this->path . DIRECTORY_SEPARATOR;

        return str_replace('\\', DIRECTORY_SEPARATOR, $fullPath);
    }

    public function requireWebFolder()
    {
        return !ArrayHelper::isIn($this->alias, [self::ALIAS_WEBROOT, self::ALIAS_WEB]);
    }

    public function getFullName()
    {
        return $this->filename . '.' . $this->extension;
    }

    public function hasThumbnail()
    {
        return $this->thumbnailFilename != null;
    }

    public function deleteThumbnail()
    {
        if ($this->hasThumbnail()) {
            $thumbnailLocation = $this->getFullPath() . $this->getThumbnailFullName();

            if (file_exists($thumbnailLocation)) {
                unlink($thumbnailLocation);

                $this->thumbnailFilename  = null;
                $this->thumbnailExtension = null;
                $this->thumbnailMimeType  = null;
                $this->thumbnailSize      = null;

                $this->save();
                $this->refresh();
            }
        }

        return true;
    }

    public function getThumbnailFullName()
    {
        return $this->thumbnailFilename . '.' . $this->thumbnailExtension;
    }

    public function getThumbnail($baseUrl = null, $options = [])
    {
        return Html::img($this->getThumbnailSource($baseUrl), $options);
    }

    public function getThumbnailSource($baseUrl = null)
    {
        if (!$baseUrl) {
            $baseUrl = \Yii::$app->urlManager->baseUrl;
        }
        switch ($this->type) {
            case self::TYPE_IMAGE :
                $thumbnailSource = $this->fileManager->defaultImageThumbnail;
                break;
            case self::TYPE_DOCUMENT:
                $thumbnailSource = $this->fileManager->defaultDocumentThumbnail;
                break;
            case self::TYPE_AUDIO:
                $thumbnailSource = $this->fileManager->defaultAudioThumbnail;
                break;
            case self::TYPE_VIDEO:
                $thumbnailSource = $this->fileManager->defaultAudioThumbnail;
                break;
            default :
                $thumbnailSource = $this->fileManager->defaultOtherThumbnail;
                break;
        }

        if ($this->hasThumbnail()) {
            if ($this->alias == self::ALIAS_WEB) {
                $source = $baseUrl . DIRECTORY_SEPARATOR;
            } else {
                $source = $baseUrl;
            }

            $thumbnailLocation =
                $source . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $this->path)
                . $this->getThumbnailFullName();

            $thumbnailSource = $thumbnailLocation;
        }

        return $thumbnailSource;
    }

    public function getSource()
    {
        if ($this->alias == self::ALIAS_FRONTEND && isset(\Yii::$app->frontendUrlManager->baseUrl)) {
            $source = \Yii::$app->frontendUrlManager->baseUrl;
        } elseif (isset(\Yii::$app->urlManager->baseUrl)) {
            $source = \Yii::$app->urlManager->baseUrl;
        } else {
            $source = DIRECTORY_SEPARATOR;
        }

        return $source . DIRECTORY_SEPARATOR . $this->path . $this->getFullName();
    }

    public function delete()
    {
        if ($this->fileManager->deleteWithFile) {
            $this->deleteFile();
            $this->deleteThumbnail();
        }

        return parent::delete();
    }

    public function deleteFile()
    {
        $fileLocation = $this->getFullPath() . $this->getFullName();
        if (file_exists($fileLocation)) {
            unlink($fileLocation);
        }

        return true;
    }

}