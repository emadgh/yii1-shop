<?php
// Idempotent on SQLite and MySQL. Triggers invalidate AR and raw SQL writes alike.
$db=Yii::app()->db;$sqlite=$db->driverName==='sqlite';
$suffix=$sqlite?'':' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
$newCatalogs=$db->schema->getTable('company_catalogs',true)===null;
$db->createCommand('CREATE TABLE IF NOT EXISTS company_catalogs (id '.($sqlite?'INTEGER PRIMARY KEY AUTOINCREMENT':'INT AUTO_INCREMENT PRIMARY KEY').', title VARCHAR(180) NOT NULL, description TEXT NOT NULL, image_id INT NULL, pdf_id INT NOT NULL, is_active INT NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0, created_at VARCHAR(30) NOT NULL, updated_at VARCHAR(30) NOT NULL, FOREIGN KEY(image_id) REFERENCES media(id), FOREIGN KEY(pdf_id) REFERENCES media(id))'.$suffix)->execute();
$schema=$db->schema->getTable('media',true);
if(!isset($schema->columns['upload_scope']))$db->createCommand("ALTER TABLE media ADD COLUMN upload_scope VARCHAR(30) NOT NULL DEFAULT 'general'")->execute();
$db->createCommand('CREATE TABLE IF NOT EXISTS cache_versions (scope VARCHAR(60) PRIMARY KEY,version BIGINT NOT NULL DEFAULT 1)'.$suffix)->execute();
$db->createCommand('CREATE TABLE IF NOT EXISTS site_cache (id CHAR(32) PRIMARY KEY,expire INT NOT NULL,value '.($sqlite?'BLOB':'LONGBLOB').')'.$suffix)->execute();
foreach(array('idx_catalog_public'=>array('company_catalogs','is_active,sort_order,id'),'idx_media_scope_mime'=>array('media','upload_scope,mime,id'),'idx_site_cache_expire'=>array('site_cache','expire')) as $index=>$definition){
    $exists=$sqlite?$db->createCommand("SELECT COUNT(*) FROM sqlite_master WHERE type='index' AND name=:name")->queryScalar(array(':name'=>$index)):$db->createCommand('SELECT COUNT(*) FROM information_schema.statistics WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table AND INDEX_NAME=:name')->queryScalar(array(':table'=>$definition[0],':name'=>$index));
    if(!$exists)$db->createCommand()->createIndex($index,$definition[0],$definition[1]);
}
foreach(array('categories','menu_items','products','product_attributes','media','company_catalogs') as $table){
    if(!$db->createCommand('SELECT COUNT(*) FROM cache_versions WHERE scope=:s')->queryScalar(array(':s'=>$table)))$db->createCommand()->insert('cache_versions',array('scope'=>$table,'version'=>1));
    foreach(array('INSERT','UPDATE','DELETE') as $event){
        $name='invalidate_'.$table.'_'.strtolower($event);
        $exists=$sqlite?$db->createCommand("SELECT COUNT(*) FROM sqlite_master WHERE type='trigger' AND name=:n")->queryScalar(array(':n'=>$name)):$db->createCommand('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=:n')->queryScalar(array(':n'=>$name));
        if(!$exists)$db->createCommand("CREATE TRIGGER $name AFTER $event ON $table FOR EACH ROW ".($sqlite?'BEGIN ':'')."UPDATE cache_versions SET version=version+1 WHERE scope='$table'".($sqlite?'; END':''))->execute();
    }
}
// New installs and existing stores get a discoverable, editable public catalog link.
if($newCatalogs&&!$db->createCommand("SELECT COUNT(*) FROM menu_items WHERE url='/catalogs'")->queryScalar())$db->createCommand()->insert('menu_items',array('label'=>'کاتالوگ‌های شرکت','url'=>'/catalogs','location'=>'footer','parent_id'=>null,'sort_order'=>90,'is_active'=>1));
$db->schema->refresh();
echo "Company catalogs and transactional cache dependencies ready.\n";
