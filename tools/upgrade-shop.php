<?php
// Invoked by upgrade.php after the base schema is available.
$db=Yii::app()->db;$sqlite=$db->driverName==='sqlite';$id=$sqlite?'INTEGER PRIMARY KEY AUTOINCREMENT':'INT AUTO_INCREMENT PRIMARY KEY';
$tables=array(
 'redirects'=>"source VARCHAR(255) PRIMARY KEY, target VARCHAR(255) NOT NULL",
 'payments'=>"id $id, order_id INT NOT NULL, authority VARCHAR(100) NULL UNIQUE, amount BIGINT NOT NULL, mode VARCHAR(20) NOT NULL, merchant VARCHAR(40) NOT NULL, state VARCHAR(30) NOT NULL, ref_id VARCHAR(100) NULL UNIQUE, error_code VARCHAR(100) NOT NULL DEFAULT '', created_at VARCHAR(30) NOT NULL, updated_at VARCHAR(30) NOT NULL",
 'order_access'=>"id $id, order_id INT NOT NULL, token_hash VARCHAR(64) NOT NULL UNIQUE, created_at VARCHAR(30) NOT NULL",
 'audit_log'=>"id $id, actor_id INT NULL, entity VARCHAR(100) NOT NULL, entity_id VARCHAR(100) NOT NULL, action VARCHAR(40) NOT NULL, changes TEXT NOT NULL, created_at VARCHAR(30) NOT NULL",
 'notifications'=>"id $id, order_id INT NOT NULL, channel VARCHAR(30) NOT NULL, recipient VARCHAR(190) NOT NULL, message TEXT NOT NULL, state VARCHAR(30) NOT NULL DEFAULT 'pending', attempts INT NOT NULL DEFAULT 0, available_at VARCHAR(30) NOT NULL, created_at VARCHAR(30) NOT NULL",
 'login_attempts'=>"bucket VARCHAR(64) PRIMARY KEY, attempts INT NOT NULL DEFAULT 0, expires_at VARCHAR(30) NOT NULL",
);
foreach($tables as $table=>$columns)$db->createCommand("CREATE TABLE IF NOT EXISTS $table ($columns)".($sqlite?'':' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'))->execute();
$schema=$db->schema->getTable('orders',true);foreach(array('province'=>"VARCHAR(100) NOT NULL DEFAULT ''",'city'=>"VARCHAR(100) NOT NULL DEFAULT ''",'shipping_total'=>'BIGINT NOT NULL DEFAULT 0','shipping_method'=>"VARCHAR(30) NOT NULL DEFAULT ''",'postal_code'=>"VARCHAR(10) NOT NULL DEFAULT ''",'tracking_code'=>"VARCHAR(190) NOT NULL DEFAULT ''") as $key=>$type)if(!isset($schema->columns[$key]))$db->createCommand("ALTER TABLE orders ADD COLUMN $key $type")->execute();
$schema=$db->schema->getTable('products',true);foreach(array('parent_product_id'=>'INT NULL','variant_label'=>"VARCHAR(190) NOT NULL DEFAULT ''") as $key=>$type)if(!isset($schema->columns[$key]))$db->createCommand("ALTER TABLE products ADD COLUMN $key $type")->execute();
$schema=$db->schema->getTable('users',true);foreach(array('totp_secret'=>"VARCHAR(255) NOT NULL DEFAULT ''",'totp_step'=>'BIGINT NOT NULL DEFAULT -1') as $key=>$type)if(!isset($schema->columns[$key]))$db->createCommand("ALTER TABLE users ADD COLUMN $key $type")->execute();
$schema=$db->schema->getTable('notifications',true);if(!isset($schema->columns['dedupe_key']))$db->createCommand('ALTER TABLE notifications ADD COLUMN dedupe_key VARCHAR(64) NULL')->execute();
$exists=$sqlite?$db->createCommand("SELECT COUNT(*) FROM sqlite_master WHERE type='index' AND name='uniq_notification_event'")->queryScalar():$db->createCommand("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='notifications' AND index_name='uniq_notification_event'")->queryScalar();if(!$exists)$db->createCommand()->createIndex('uniq_notification_event','notifications','dedupe_key',true);
$db->schema->refresh();echo "Shop operations schema ready.\n";
