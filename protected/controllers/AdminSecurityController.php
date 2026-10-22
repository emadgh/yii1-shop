<?php
class AdminSecurityController extends AdminController {
 public function actionIndex(){
  header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');header('X-Robots-Tag: noindex');
  $user=User::model()->findByPk(Yii::app()->user->id);$error='';$secret=Yii::app()->session['pending_totp'];if(!$secret){$secret=TwoFactor::secret();Yii::app()->session['pending_totp']=$secret;}
  if(Yii::app()->request->isPostRequest){try{$password=Yii::app()->request->getPost('password','');$code=Yii::app()->request->getPost('code','');if(!is_string($password)||!is_string($code)||!password_verify($password,$user->password_hash))throw new Exception('رمز عبور معتبر نیست.');
   if($user->totp_secret){if(!TwoFactor::authenticate($user,$code))throw new Exception('کد معتبر نیست یا قبلاً استفاده شده است.');$user->totp_secret='';}
   else{if(TwoFactor::step($secret,$code)===false)throw new Exception('کد معتبر نیست.');$user->totp_secret=TwoFactor::crypt($secret);$user->totp_step=-1;}$user->save(false,$user->totp_secret?array('totp_secret','totp_step'):array('totp_secret'));Yii::app()->session->remove('pending_totp');Audit::record('users',$user->id,'two_factor');$this->flash('تنظیم ورود دومرحله‌ای ذخیره شد.');$this->refresh();
  }catch(Exception $e){$error=$e->getMessage();}}
  $this->pageTitle='امنیت حساب';$this->render('//admin/security/index',array('user'=>$user,'secret'=>$secret,'error'=>$error));
 }
}
