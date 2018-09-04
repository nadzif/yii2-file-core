# yii2-file-core

Yii2 file upload with database managemen

```php
'fileManager' => [
    'class'                    => \nadzif\file\FileManager::className(),
    'alias'                    => \nadzif\file\models\File::ALIAS_FRONTEND,
    'defaultImageThumbnail'    => '@frontend/web/images/thumb-image.jpg',
    'defaultDocumentThumbnail' => '@frontend/web/images/thumb-document.jpg',
    'defaultAudioThumbnail'    => '@frontend/web/images/thumb-audio.jpg',
    'defaultVideoThumbnail'    => '@frontend/web/images/thumb-video.jpg',
    'defaultOtherThumbnail'    => '@frontend/web/images/thumb-other.jpg',
]
```

```
php yii migrate --migrationPath=@nadzif/migrations
```

```php
/** @var FileManager $fileManager */
$fileManager = \Yii::$app->fileManager;
```

```php
$fileInstance = UploadedFile::getInstanceByName('files');
/** @var nadzif\file\models\File $file */
$file = $fileManager->upload($fileInstance);
```