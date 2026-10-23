<?php
require_once __DIR__.'/bootstrap.php';
$db=Yii::app()->db;$sqlite=$db->driverName==='sqlite';$pk=$sqlite?'INTEGER PRIMARY KEY AUTOINCREMENT':'INT AUTO_INCREMENT PRIMARY KEY';
$tables=array(
 'collections'=>"id $pk, title VARCHAR(180) NOT NULL, slug VARCHAR(190) NOT NULL UNIQUE, description TEXT NOT NULL, image VARCHAR(255) NOT NULL DEFAULT '', is_active INT NOT NULL DEFAULT 1, created_at VARCHAR(30) NOT NULL, updated_at VARCHAR(30) NOT NULL",
 'collection_products'=>"collection_id INT NOT NULL, product_id INT NOT NULL, position INT NOT NULL DEFAULT 0, PRIMARY KEY(collection_id,product_id)",
 'menu_items'=>"id $pk, label VARCHAR(150) NOT NULL, url VARCHAR(500) NOT NULL, location VARCHAR(20) NOT NULL DEFAULT 'header', parent_id INT NULL, sort_order INT NOT NULL DEFAULT 0, is_active INT NOT NULL DEFAULT 1",
 'theme_blocks'=>"id $pk, theme_key VARCHAR(40) NOT NULL, block_type VARCHAR(30) NOT NULL, title VARCHAR(180) NOT NULL, collection_id INT NULL, category_id INT NULL, presentation VARCHAR(30) NOT NULL DEFAULT 'grid', card_style VARCHAR(30) NOT NULL DEFAULT 'standard', item_limit INT NOT NULL DEFAULT 8, sort_order INT NOT NULL DEFAULT 0, is_enabled INT NOT NULL DEFAULT 1",
 'page_visits'=>"id $pk, event_id VARCHAR(64) NOT NULL UNIQUE, visitor_hash VARCHAR(64) NOT NULL, path VARCHAR(255) NOT NULL, referrer_host VARCHAR(190) NOT NULL DEFAULT '', device VARCHAR(20) NOT NULL, created_at VARCHAR(30) NOT NULL",
);
foreach($tables as $name=>$sql)$db->createCommand("CREATE TABLE IF NOT EXISTS $name ($sql)".($sqlite?'':' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'))->execute();
foreach(array('theme_blocks'=>array('category_id'=>'INT NULL'),'categories'=>array('parent_id'=>'INT NULL','image'=>"VARCHAR(255) NOT NULL DEFAULT ''",'sort_order'=>'INT NOT NULL DEFAULT 0'),'products'=>array('display_layout'=>"VARCHAR(30) NOT NULL DEFAULT 'inherit'",'card_layout'=>"VARCHAR(30) NOT NULL DEFAULT 'inherit'")) as $table=>$columns){$schema=$db->schema->getTable($table,true);foreach($columns as $key=>$type)if(!isset($schema->columns[$key]))$db->createCommand("ALTER TABLE $table ADD COLUMN $key $type")->execute();}
if(!$db->createCommand("SELECT COUNT(*) FROM settings WHERE setting_key='cms_version'")->queryScalar()){
 $tx=$db->beginTransaction();try{
  foreach(array('commerce_enabled'=>'1','catalog_show_prices'=>'1','contact_phone'=>'','contact_email'=>'','footer_text'=>'اشیای ساده برای زندگی روزمره','announcement'=>'ارسال سفارش‌های ثبت‌شده پس از هماهنگی با فروشگاه','analytics_enabled'=>'1','analytics_retention_days'=>'90','matomo_url'=>'','matomo_site_id'=>'','cms_version'=>'1') as $k=>$v)ShopSettings::set($k,$v);
  if(!$db->createCommand('SELECT COUNT(*) FROM menu_items')->queryScalar())foreach(array('header'=>array('خانه'=>'/','محصولات'=>'/shop','مجله'=>'/blog'),'footer'=>array('محصولات'=>'/shop','مجله'=>'/blog','ورود مدیریت'=>'/login')) as $location=>$links){$i=0;foreach($links as $label=>$url)$db->createCommand()->insert('menu_items',array('label'=>$label,'url'=>$url,'location'=>$location,'sort_order'=>$i++,'is_active'=>1));}
  foreach(array('market-light','market-dark','minimal-light','minimal-dark','classic') as $theme){$market=strpos($theme,'market')===0;foreach(array(array('hero','',0,'grid'),array('categories','دسته‌بندی محصولات',10,'grid'),array('collection',$market?'پیشنهادهای ویژه':'اشیای روزمره',20,$market?'carousel':'grid'),array('blog','مجله بازارچه',30,'grid')) as $block)$db->createCommand()->insert('theme_blocks',array('theme_key'=>$theme,'block_type'=>$block[0],'title'=>$block[1],'sort_order'=>$block[2],'presentation'=>$block[3],'card_style'=>$market?'standard':'editorial','item_limit'=>8,'is_enabled'=>1));}
  $tx->commit();
 }catch(Exception $e){if($tx->active)$tx->rollback();throw $e;}
}
$db->schema->refresh();echo "CMS schema ready.\n";
foreach(array('idx_visits_created'=>array('page_visits','created_at'),'idx_visits_path'=>array('page_visits','path'),'idx_categories_parent'=>array('categories','parent_id'),'idx_blocks_theme'=>array('theme_blocks','theme_key'),'idx_collection_product'=>array('collection_products','product_id')) as $name=>$index){
 $exists=$sqlite?$db->createCommand('SELECT COUNT(*) FROM sqlite_master WHERE type=\'index\' AND name=:name')->queryScalar(array(':name'=>$name)):$db->createCommand('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND index_name=:name AND table_name=:table')->queryScalar(array(':name'=>$name,':table'=>$index[0]));
 if(!$exists)$db->createCommand()->createIndex($name,$index[0],$index[1]);
}
