<?php
class LoginGuard {
 public static function attempt($email){
  $db=Yii::app()->db;$now=date('Y-m-d H:i:s');$until=date('Y-m-d H:i:s',time()+900);
  foreach(array('ip:'.Yii::app()->request->userHostAddress,'account:'.strtolower($email)) as $key){$bucket=hash('sha256',$key);$db->createCommand('DELETE FROM login_attempts WHERE bucket=:b AND expires_at<=:now')->execute(array(':b'=>$bucket,':now'=>$now));
   try{$db->createCommand()->insert('login_attempts',array('bucket'=>$bucket,'attempts'=>1,'expires_at'=>$until));}catch(CDbException $e){$db->createCommand('UPDATE login_attempts SET attempts=attempts+1 WHERE bucket=:b')->execute(array(':b'=>$bucket));}
   if((int)$db->createCommand('SELECT attempts FROM login_attempts WHERE bucket=:b')->queryScalar(array(':b'=>$bucket))>10)throw new CHttpException(429,'تلاش‌های ورود بیش از حد مجاز است؛ ۱۵ دقیقه دیگر امتحان کنید.');
  }
 }
}
