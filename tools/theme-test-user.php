<?php
require __DIR__.'/bootstrap.php';
$email='theme-permission-test@example.test';$u=User::model()->findByAttributes(array('email'=>$email));
if(isset($argv[1]) && $argv[1]==='remove'){if($u && $u->name==='Theme permission test')$u->delete();exit;}
if($u)throw new Exception('Test account already exists');
$u=new User;$u->name='Theme permission test';$u->email=$email;$u->password_hash=password_hash('ThemePermission123!',PASSWORD_DEFAULT);$u->role='admin';$u->is_active=1;$u->save(false);echo "Temporary administrator created\n";
