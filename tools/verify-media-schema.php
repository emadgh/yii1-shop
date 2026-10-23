<?php
if(PHP_SAPI!=='cli')exit(1);
$mediaSchemaCopy=dirname(__DIR__).'/protected/runtime/media-schema-'.bin2hex(random_bytes(6)).'.sqlite';
copy(dirname(__DIR__).'/protected/runtime/store.sqlite',$mediaSchemaCopy);putenv('SHOP_DSN=sqlite:'.$mediaSchemaCopy);
require __DIR__.'/bootstrap.php';
$mediaSchemaUploaded=null;
try{
    $db=Yii::app()->db;
    $db->createCommand('DROP INDEX IF EXISTS idx_media_context')->execute();
    if(isset($db->schema->getTable('media',true)->columns['upload_context']))$db->createCommand('ALTER TABLE media DROP COLUMN upload_context')->execute();
    $before=(int)$db->createCommand('SELECT COUNT(*) FROM media')->queryScalar();
    $source=dirname(__DIR__).'/public/assets/storefront/product-lamp.webp';
    try{MediaUploadService::ingest($source,'schema-test.webp');throw new Exception('Missing schema was not rejected');}
    catch(CHttpException $e){if($e->statusCode!==503||strpos($e->getMessage(),'ارتقا')===false)throw $e;}
    if((int)$db->createCommand('SELECT COUNT(*) FROM media')->queryScalar()!==$before)throw new Exception('Failed upload changed metadata');
    echo "PASS old schema fails before file processing with actionable upgrade message\n";
    require __DIR__.'/upgrade-cms.php';require __DIR__.'/upgrade-cms.php';
    MediaContextService::requireSchema();
    if((int)$db->createCommand('SELECT COUNT(*) FROM media')->queryScalar()!==$before)throw new Exception('Upgrade lost media');
    $mediaSchemaUploaded=MediaUploadService::ingest($source,'schema-test.webp');
    if(!isset($mediaSchemaUploaded['upload_context']))throw new Exception('Upgraded upload missing context field');
    echo "PASS migration adds missing column idempotently, preserves media and restores uploads\n";
}finally{
    if($mediaSchemaUploaded)MediaUploadService::removeFiles($mediaSchemaUploaded['filename'],json_decode($mediaSchemaUploaded['variants'],true));
    Yii::app()->db->active=false;if(is_file($mediaSchemaCopy))unlink($mediaSchemaCopy);
}
