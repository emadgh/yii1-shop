<?php
$db=Yii::app()->db;$sqlite=$db->driverName==='sqlite';$suffix=$sqlite?'':' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
$id=$sqlite?'INTEGER PRIMARY KEY AUTOINCREMENT':'INT AUTO_INCREMENT PRIMARY KEY';
$db->createCommand("CREATE TABLE IF NOT EXISTS api_tokens (id $id,user_id INT NOT NULL,name VARCHAR(100) NOT NULL,token_hash CHAR(64) NOT NULL UNIQUE,scopes TEXT NOT NULL,expires_at VARCHAR(30) NOT NULL,revoked_at VARCHAR(30) NULL,last_used_at VARCHAR(30) NULL,created_at VARCHAR(30) NOT NULL)".$suffix)->execute();
$db->createCommand('CREATE TABLE IF NOT EXISTS api_requests (token_id INT NOT NULL,request_key VARCHAR(100) NOT NULL,request_hash CHAR(64) NOT NULL,response_json '.($sqlite?'TEXT':'MEDIUMTEXT').' NOT NULL,created_at VARCHAR(30) NOT NULL,PRIMARY KEY(token_id,request_key))'.$suffix)->execute();
$schema=$db->schema->getTable('audit_log',true);
if(!isset($schema->columns['api_token_id']))$db->createCommand('ALTER TABLE audit_log ADD COLUMN api_token_id INT NULL')->execute();
$db->schema->refresh();echo "AI API credentials and request ledger ready.\n";
