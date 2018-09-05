# yii2-file-core

Yii2 file upload with database managemen

add component to your config

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

do migration via console
```
php yii migrate --migrationPath=@nadzif/yii2-file-core/src/migrations
```

getting fileManager
```php
/** @var FileManager $fileManager */
$fileManager = \Yii::$app->fileManager;
```

uploading file from fileInstance
```php
$fileInstance = UploadedFile::getInstanceByName('files');
/** @var nadzif\file\models\File $file */
$file = $fileManager->upload($fileInstance);
```