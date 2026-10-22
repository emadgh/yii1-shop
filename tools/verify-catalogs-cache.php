<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__);$copy=$root.'/protected/runtime/catalog-cache-'.bin2hex(random_bytes(5)).'.sqlite';
$mysql=getenv('SHOP_CACHE_TEST_MYSQL')==='1';
if(!$mysql){copy($root.'/protected/runtime/store.sqlite',$copy);putenv('SHOP_DSN=sqlite:'.$copy);}
require_once __DIR__.'/bootstrap.php';$checks=0;
function cacheCheck($ok,$label){global $checks;if(!$ok)throw new Exception('FAILED '.$label);$checks++;echo "PASS $label\n";}
function cacheReject($fn,$label){try{$fn();}catch(Exception $e){cacheCheck(true,$label);return;}cacheCheck(false,$label);}
$db=Yii::app()->db;$cache=Yii::app()->siteCache;$mediaIds=array();$catalogId=null;
$originalBackend=ShopSettings::get('cache_backend','db');$originalTtl=ShopSettings::get('cache_ttl','600');
try{
 foreach(array('db'=>'CDbCache','file'=>'CFileCache') as $mode=>$class){
  ShopSettings::set('cache_backend',$mode);$cache->invalidateAll();$calls=0;$key=bin2hex(random_bytes(8));
  $loader=function()use(&$calls){return ++$calls;};
  cacheCheck(get_class($cache->backend())===$class,$mode.' uses standard Yii storage');
  cacheCheck($cache->remember($key,array('menu_items'),$loader)===1&&$cache->remember($key,array('menu_items'),$loader)===1&&$calls===1,$mode.' warm read avoids expensive loader');
  $tx=$db->beginTransaction();$db->createCommand("UPDATE menu_items SET label=label WHERE id=(SELECT id FROM (SELECT MIN(id) id FROM menu_items) x)")->execute();
  cacheCheck($cache->remember($key,array('menu_items'),$loader)===2,$mode.' transaction bypasses shared cache');$tx->rollback();
  cacheCheck($cache->remember($key,array('menu_items'),$loader)===1,$mode.' rollback preserves original cached value');
  $db->createCommand("UPDATE menu_items SET label=label WHERE id=(SELECT id FROM (SELECT MIN(id) id FROM menu_items) x)")->execute();
  cacheCheck($cache->remember($key,array('menu_items'),$loader)===3,$mode.' committed SQL invalidates cache');
  $otherCalls=0;$other=function()use(&$otherCalls){return ++$otherCalls;};$cache->remember($key,array('categories'),$other);
  $db->createCommand("UPDATE menu_items SET label=label WHERE id=(SELECT id FROM (SELECT MIN(id) id FROM menu_items) x)")->execute();
  cacheCheck($cache->remember($key,array('categories'),$other)===1,$mode.' invalidation is limited to dependencies');
  $racing=function()use($db){$db->createCommand("UPDATE menu_items SET label=label WHERE id=(SELECT id FROM (SELECT MIN(id) id FROM menu_items) x)")->execute();return 'stale';};
  $cache->remember('racing-'.$key,array('menu_items'),$racing);
  cacheCheck($cache->remember('racing-'.$key,array('menu_items'),function(){return 'fresh';})==='fresh',$mode.' loader racing with writer does not poison new generation');
 }
 ShopSettings::set('cache_backend','off');$calls=0;$loader=function()use(&$calls){return ++$calls;};$cache->remember('off',array('menu_items'),$loader);$cache->remember('off',array('menu_items'),$loader);cacheCheck($calls===2,'disabled cache always reads fresh');
 ShopSettings::set('cache_backend','db');
 $attempts=0;cacheReject(function()use($cache,&$attempts){$cache->remember('error',array('menu_items'),function()use(&$attempts){$attempts++;throw new Exception('loader error');});},'loader errors propagate');cacheCheck($attempts===1,'loader errors are not retried as cache failures');
 $image=$db->createCommand("SELECT id FROM media WHERE mime LIKE 'image/%' ORDER BY id LIMIT 1")->queryScalar();
 $data=array('filename'=>'media/'.bin2hex(random_bytes(16)).'.pdf','original_name'=>'catalog-cache-test.pdf','mime'=>'application/pdf','size'=>40,'width'=>0,'height'=>0,'variants'=>'{}','upload_scope'=>'catalogs','created_at'=>date('Y-m-d H:i:s'));
 $pdf=MediaRepository::insert($data);$mediaIds[]=$pdf['id'];
 cacheCheck(count(MediaRepository::library('catalog-cache-test',0,'application/pdf','catalogs'))===1,'PDF library filters MIME and upload scope before pagination');
 cacheCheck(count(MediaRepository::library('catalog-cache-test',0,'image/*','all'))===0,'image library excludes PDFs on server');
 cacheReject(function(){MediaRepository::library('',0,'application/pdf',"catalogs' OR 1=1");},'invalid media scope rejected');
 $catalog=new CompanyCatalog;CompanyCatalogService::save($catalog,array('title'=>'Cache test','description'=>'**توضیحات**','is_active'=>1,'sort_order'=>2),(string)$image,(string)$pdf['id']);$catalogId=$catalog->id;
 $find=function()use($catalogId){return array_values(array_filter(CompanyCatalogQuery::published(),function($row)use($catalogId){return (int)$row['id']===(int)$catalogId;}));};
 cacheCheck(count($find())===1&&CompanyCatalogQuery::download($catalogId)!==false,'published catalog appears in cached list and fresh download lookup');
 cacheReject(function()use($catalog,$image){CompanyCatalogService::save($catalog,array('title'=>'invalid'),'',$image);},'image cannot be submitted as PDF');
 cacheCheck(CompanyCatalog::model()->findByPk($catalogId)->title==='Cache test','invalid media rolls back catalog changes');
 cacheReject(function()use($db,$pdf){$db->createCommand()->delete('media','id=:id',array(':id'=>$pdf['id']));},'database prevents deleting referenced PDF');
 $saved=CompanyCatalog::model()->findByPk($catalogId);$saved->is_active=0;$saved->save();
 cacheCheck(count($find())===0&&CompanyCatalogQuery::download($catalogId)===false,'unpublishing invalidates warm list and denies old download links');
 $saved->is_active=1;$saved->save();$find();$saved->delete();
 cacheCheck(count($find())===0,'deleting catalog invalidates warm public list');$catalogId=null;
 $cache->invalidateAll();
 cacheCheck((int)$db->createCommand('SELECT COUNT(*) FROM site_cache')->queryScalar()>0,'database backend persists cache entries');
 echo "All $checks catalog/cache checks passed on ".$db->driverName.".\n";
}finally{
 if($catalogId)$db->createCommand()->delete('company_catalogs','id=:id',array(':id'=>$catalogId));
 foreach($mediaIds as $id)$db->createCommand()->delete('media','id=:id',array(':id'=>$id));
 ShopSettings::set('cache_backend',$originalBackend);ShopSettings::set('cache_ttl',$originalTtl);$cache->invalidateAll();
 if(!$mysql){$db->active=false;@unlink($copy);}
}
