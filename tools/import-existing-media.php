<?php
require_once __DIR__.'/bootstrap.php';
$db=Yii::app()->db;$root=Yii::app()->params['uploadsDir'];
foreach(array('products','blog') as $dir) foreach(glob($root.'/'.$dir.'/*') as $path) {
 if(!is_file($path) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D',basename($path)))continue;
 $relative=$dir.'/'.basename($path);$name='imported-'.$dir.'-'.basename($path);
 if($db->createCommand('SELECT COUNT(*) FROM media WHERE original_name=:n')->queryScalar(array(':n'=>$name)))continue;
 $row=MediaService::ingest($path,$name);$tx=$db->beginTransaction();try {
   $db->createCommand()->update('product_images',array('filename'=>$row['filename']),'filename=:p',array(':p'=>$relative));
   $db->createCommand()->update('posts',array('cover_image'=>$row['filename']),'cover_image=:p',array(':p'=>$relative));
   foreach(array('products'=>array('summary','description'),'posts'=>array('excerpt','body')) as $table=>$fields)foreach($fields as $field)$db->createCommand("UPDATE $table SET $field=REPLACE($field,:old,:new)")->execute(array(':old'=>'/uploads/'.$relative,':new'=>'/uploads/'.$row['filename']));
   $tx->commit();echo "Imported $relative\n";
 }catch(Exception $e){if($tx->active)$tx->rollback();throw $e;}
}
