<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__);$copy=$root.'/protected/runtime/challenge-check-'.bin2hex(random_bytes(5)).'.sqlite';copy($root.'/protected/runtime/store.sqlite',$copy);putenv('SHOP_DSN=sqlite:'.$copy);
require $root.'/vendor/yiisoft/yii/framework/yii.php';$config=require $root.'/protected/config/main.php';$config['components']['session']=array('class'=>'CHttpSession','savePath'=>$root.'/protected/runtime');Yii::createWebApplication($config);Yii::app()->session->open();$checks=array();
function challengeCheck($ok,$label){global $checks;if(!$ok)throw new Exception('FAILED '.$label);$checks[]=$label;}
try{
 $user=User::model()->findByAttributes(array('email'=>'demo@bazaarche.test'));$user->totp_secret=TwoFactor::crypt(TwoFactor::secret());$user->totp_step=-1;$user->save(false,array('totp_secret','totp_step'));
 $identity=new StoreIdentity($user->email,'wrong');challengeCheck($identity->beginLogin()===false&&!StoreIdentity::challengeUser(),'bad password cannot create a challenge');
 $identity=new StoreIdentity($user->email,'BazaarcheTest123!');challengeCheck($identity->beginLogin()==='challenge'&&Yii::app()->user->isGuest,'password proof keeps the user anonymous');
 challengeCheck(strpos(serialize(Yii::app()->session['login_challenge']),'BazaarcheTest123!')===false,'challenge does not store plaintext password');
 $pending=Yii::app()->session['login_challenge'];$pending['expires']=time()-1;Yii::app()->session['login_challenge']=$pending;challengeCheck(!StoreIdentity::challengeUser()&&!StoreIdentity::authenticateChallenge('123456'),'expired challenge cannot authenticate');
 $identity->beginLogin();$user->password_hash=password_hash('BazaarcheTest123!',PASSWORD_DEFAULT);$user->save(false,array('password_hash'));challengeCheck(!StoreIdentity::challengeUser(),'password change invalidates pending proof');
 $identity->beginLogin();$user->totp_secret=TwoFactor::crypt(TwoFactor::secret());$user->save(false,array('totp_secret'));challengeCheck(!StoreIdentity::challengeUser(),'authenticator change invalidates pending proof');
 $identity->beginLogin();$user->is_active=0;$user->save(false,array('is_active'));challengeCheck(!StoreIdentity::challengeUser(),'deactivation invalidates pending proof');
 foreach($checks as $label)echo "PASS $label\n";echo 'All '.count($checks)." login challenge checks passed on isolated database.\n";
}finally{Yii::app()->session->destroy();Yii::app()->db->active=false;@unlink($copy);}
