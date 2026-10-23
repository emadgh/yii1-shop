<?php
require_once __DIR__.'/bootstrap.php';
$db=Yii::app()->db;$sqlite=$db->driverName==='sqlite';
$db->createCommand('CREATE TABLE IF NOT EXISTS product_templates (id '.($sqlite?'INTEGER PRIMARY KEY AUTOINCREMENT':'INT AUTO_INCREMENT PRIMARY KEY').',name VARCHAR(150) NOT NULL UNIQUE,payload '.($sqlite?'TEXT':'MEDIUMTEXT').' NOT NULL,created_at VARCHAR(30) NOT NULL)'.($sqlite?'':' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'))->execute();
$db->schema->refresh();echo "Product templates ready.\n";
