<?php
// Idempotent album migration, invoked by upgrade.php and after demo seeding.
$db=Yii::app()->db;$sqlite=$db->driverName==='sqlite';
$db->createCommand($sqlite?
 'CREATE TABLE IF NOT EXISTS product_albums (id INTEGER PRIMARY KEY AUTOINCREMENT,product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,title VARCHAR(150) NOT NULL,layout VARCHAR(20) NOT NULL DEFAULT \'grid\',sort_order INTEGER NOT NULL DEFAULT 0)':
 'CREATE TABLE IF NOT EXISTS product_albums (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,product_id INT UNSIGNED NOT NULL,title VARCHAR(150) NOT NULL,layout VARCHAR(20) NOT NULL DEFAULT \'grid\',sort_order INT NOT NULL DEFAULT 0,INDEX idx_album_product(product_id,sort_order),CONSTRAINT fk_album_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4')->execute();
$schema=$db->schema->getTable('product_images',true);if(!isset($schema->columns['album_id']))$db->createCommand('ALTER TABLE product_images ADD COLUMN album_id '.($sqlite?'INTEGER':'INT UNSIGNED').' NULL')->execute();
if($sqlite)$db->createCommand('CREATE INDEX IF NOT EXISTS idx_album_product ON product_albums(product_id,sort_order)')->execute();
$exists=$sqlite?$db->createCommand("SELECT COUNT(*) FROM sqlite_master WHERE type='index' AND name='idx_product_images_album'")->queryScalar():$db->createCommand("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='product_images' AND index_name='idx_product_images_album'")->queryScalar();if(!$exists)$db->createCommand()->createIndex('idx_product_images_album','product_images','album_id,sort_order');
$tx=$db->beginTransaction();try{
 foreach($db->createCommand('SELECT DISTINCT product_id FROM product_images WHERE album_id IS NULL ORDER BY product_id')->queryColumn() as $productId){
  $album=$db->createCommand('SELECT id FROM product_albums WHERE product_id=:p ORDER BY sort_order,id LIMIT 1')->queryScalar(array(':p'=>$productId));
  if(!$album){$db->createCommand()->insert('product_albums',array('product_id'=>$productId,'title'=>'تصاویر محصول','layout'=>'grid','sort_order'=>0));$album=$db->lastInsertID;}
  $db->createCommand()->update('product_images',array('album_id'=>$album),'product_id=:p AND album_id IS NULL',array(':p'=>$productId));
 }
 // Older uploaded images may predate the media library. Register without moving files.
 foreach($db->createCommand('SELECT DISTINCT i.filename FROM product_images i LEFT JOIN media m ON m.filename=i.filename WHERE m.id IS NULL')->queryColumn() as $filename){
  if(!UploadService::validPath($filename))continue;
  $file=Yii::app()->params['uploadsDir'].'/'.$filename;$info=is_file($file)?@getimagesize($file):false;$ext=strtolower(pathinfo($filename,PATHINFO_EXTENSION));
  $db->createCommand()->insert('media',array('filename'=>$filename,'original_name'=>basename($filename),'mime'=>$info?$info['mime']:($ext==='jpg'?'image/jpeg':'image/'.$ext),'size'=>is_file($file)?filesize($file):0,'width'=>$info?$info[0]:0,'height'=>$info?$info[1]:0,'variants'=>'{}','created_at'=>date('Y-m-d H:i:s')));
 }
 $tx->commit();
}catch(Exception $e){if($tx->active)$tx->rollback();throw $e;}
$db->schema->refresh();echo "Product albums ready; existing galleries preserved.\n";
