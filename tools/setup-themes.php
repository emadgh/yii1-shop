<?php
require __DIR__.'/bootstrap.php';
$email=getenv('SHOP_SUPERADMIN_EMAIL')?:'demo@bazaarche.test';
$user=User::model()->findByAttributes(array('email'=>$email));
if(!$user){fwrite(STDERR,"Superadmin email not found. Set SHOP_SUPERADMIN_EMAIL.\n");exit(1);}
if(!in_array($user->role,array('admin','superadmin'),true))throw new Exception('Selected account must already be an administrator.');
$user->role='superadmin';$user->save(false);
echo "Superadmin enabled for $email. Other accounts unchanged.\n";
