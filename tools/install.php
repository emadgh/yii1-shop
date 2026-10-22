<?php
// CLI installer; set SHOP_ADMIN_EMAIL and SHOP_ADMIN_PASSWORD in your environment.
if(PHP_SAPI!=='cli') { http_response_code(403); exit; }
$root=dirname(__DIR__);
$dsn=getenv('SHOP_DSN') ?: 'sqlite:'.$root.'/protected/runtime/store.sqlite';
$user=getenv('SHOP_DB_USER') ?: '';
$pass=getenv('SHOP_DB_PASSWORD') ?: '';
$email=strtolower(trim((string)getenv('SHOP_ADMIN_EMAIL')));
$password=(string)getenv('SHOP_ADMIN_PASSWORD');
if(!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<12) {
  fwrite(STDERR,"Set SHOP_ADMIN_EMAIL and SHOP_ADMIN_PASSWORD (at least 12 characters).\n"); exit(2);
}
if(!extension_loaded('pdo')) { fwrite(STDERR,"PDO extension required.\n"); exit(3); }
try {
 $db=new PDO($dsn,$user,$pass,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
 $sqlite=strpos($dsn,'sqlite:')===0;
 if($sqlite) $db->exec('PRAGMA foreign_keys = ON');
 $schema=file_get_contents($root.'/database/'.($sqlite?'sqlite':'mysql').'.sql');
 // Schema only contains simple semicolon-delimited statements (no stored procedures).
 foreach(explode(';',$schema) as $statement) if(trim($statement)!=='') $db->exec($statement);
 $exists=$db->prepare('SELECT id FROM users WHERE email = :email');
 $exists->execute(array(':email'=>$email));
 if($exists->fetchColumn()) { fwrite(STDOUT,"User already exists; password unchanged.\n"); require __DIR__.'/upgrade.php'; exit(0); }
 $insert=$db->prepare('INSERT INTO users (name,email,password_hash,role,is_active) VALUES (:name,:email,:hash,:role,1)');
 $insert->execute(array(':name'=>'مدیر فروشگاه',':email'=>$email,':hash'=>password_hash($password,PASSWORD_DEFAULT),':role'=>'superadmin'));
 fwrite(STDOUT,"Database initialized and admin account created.\n");
} catch(Exception $e) { fwrite(STDERR,"Install failed: ".$e->getMessage()."\n"); exit(1); }
require __DIR__.'/upgrade.php';
