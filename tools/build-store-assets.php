<?php
/** One-time conversion of generated source artwork through the upload pipeline. */
require_once __DIR__.'/bootstrap.php';
$root=dirname(__DIR__);
foreach(glob($root.'/protected/runtime/generated/*.png') as $path){$key=pathinfo($path,PATHINFO_FILENAME);$row=MediaService::ingest($path,'storefront-'.$key.'.webp');copy(Yii::app()->params['uploadsDir'].'/'.$row['filename'],$root.'/public/assets/storefront/'.$key.'.webp');echo $key." WebP ready\n";}
