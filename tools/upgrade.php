<?php
require __DIR__.'/bootstrap.php';
$db=Yii::app()->db;
$sqlite=$db->driverName==='sqlite';
$id=$sqlite?'INTEGER PRIMARY KEY AUTOINCREMENT':'INT AUTO_INCREMENT PRIMARY KEY';
$tables=array(
 'settings'=>"setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT NOT NULL",
 'coupons'=>"id $id, code VARCHAR(80) NOT NULL UNIQUE, kind VARCHAR(20) NOT NULL, amount BIGINT NOT NULL, minimum_total BIGINT NOT NULL DEFAULT 0, maximum_discount BIGINT NOT NULL DEFAULT 0, usage_limit INT NOT NULL DEFAULT 0, used_count INT NOT NULL DEFAULT 0, starts_at VARCHAR(30) NULL, expires_at VARCHAR(30) NULL, is_active INT NOT NULL DEFAULT 1",
 'media'=>"id $id, filename VARCHAR(255) NOT NULL UNIQUE, original_name VARCHAR(255) NOT NULL, mime VARCHAR(100) NOT NULL, size BIGINT NOT NULL, width INT NOT NULL DEFAULT 0, height INT NOT NULL DEFAULT 0, variants TEXT NOT NULL, created_at VARCHAR(30) NOT NULL",
 'inventory_movements'=>"id $id, product_id INT NOT NULL, order_id INT NULL, quantity INT NOT NULL, kind VARCHAR(40) NOT NULL, note VARCHAR(255) NOT NULL DEFAULT '', created_at VARCHAR(30) NOT NULL",
 'financial_entries'=>"id $id, order_id INT NOT NULL, kind VARCHAR(30) NOT NULL, amount BIGINT NOT NULL, created_at VARCHAR(30) NOT NULL",
 'order_events'=>"id $id, order_id INT NOT NULL, from_status VARCHAR(30) NOT NULL, to_status VARCHAR(30) NOT NULL, created_at VARCHAR(30) NOT NULL",
);
$tx=$sqlite?$db->beginTransaction():null;
try {
 foreach($tables as $name=>$columns) $db->createCommand("CREATE TABLE IF NOT EXISTS $name ($columns)".($sqlite?'':' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'))->execute();
 $add=array('products'=>array('discount_type'=>"VARCHAR(20) NOT NULL DEFAULT 'none'",'discount_value'=>'BIGINT NOT NULL DEFAULT 0'),'orders'=>array('subtotal'=>'BIGINT NOT NULL DEFAULT 0','discount_total'=>'BIGINT NOT NULL DEFAULT 0','coupon_code'=>"VARCHAR(80) NOT NULL DEFAULT ''",'reserved_until'=>'VARCHAR(30) NULL','stock_held'=>'INT NOT NULL DEFAULT 1','paid_at'=>'VARCHAR(30) NULL'));
 foreach($add as $table=>$columns) {
   $schema=$db->schema->getTable($table,true);
   foreach($columns as $name=>$type) if(!isset($schema->columns[$name])) $db->createCommand("ALTER TABLE $table ADD COLUMN $name $type")->execute();
 }
 if(!$sqlite)$tx=$db->beginTransaction();
 $first=!$db->createCommand("SELECT COUNT(*) FROM settings WHERE setting_key='schema_version'")->queryScalar();
 if($first) {
   $db->createCommand("UPDATE orders SET subtotal=total,stock_held=CASE WHEN status='cancelled' THEN 0 ELSE 1 END")->execute();
   $db->createCommand("UPDATE orders SET reserved_until=:expiry WHERE status IN ('pending','confirmed')")->execute(array(':expiry'=>date('Y-m-d H:i:s',time()+1200)));
   $db->createCommand("UPDATE orders SET paid_at=created_at WHERE status IN ('shipped','completed')")->execute();
   foreach($db->createCommand("SELECT id,total,created_at FROM orders WHERE status IN ('shipped','completed')")->queryAll() as $old)$db->createCommand()->insert('financial_entries',array('order_id'=>$old['id'],'kind'=>'receipt','amount'=>$old['total'],'created_at'=>$old['created_at']));
   foreach($db->createCommand('SELECT id,stock FROM products')->queryAll() as $p) $db->createCommand()->insert('inventory_movements',array('product_id'=>$p['id'],'quantity'=>$p['stock'],'kind'=>'opening','note'=>'موجودی اولیه پیش از ارتقا','created_at'=>date('Y-m-d H:i:s')));
   foreach(array('site_name'=>'بازارچه','site_description'=>'اشیای ساده برای زندگی روزمره','currency'=>'toman','reservation_minutes'=>'20','schema_version'=>'2') as $key=>$value) $db->createCommand()->insert('settings',array('setting_key'=>$key,'setting_value'=>$value));
 }
 $tx->commit(); echo "Upgrade complete. Existing data preserved.\n";
} catch(Exception $e) { if($tx && $tx->active)$tx->rollback(); fwrite(STDERR,$e->getMessage()."\n");exit(1); }
require __DIR__.'/upgrade-cms.php';

require __DIR__.'/upgrade-shop.php';
require __DIR__.'/upgrade-albums.php';
require __DIR__.'/upgrade-catalogs-cache.php';

require __DIR__.'/upgrade-api.php';
