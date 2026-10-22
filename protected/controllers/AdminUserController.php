<?php
class AdminUserController extends AdminController {
 public function actionIndex(){
  if(!AccessService::isSuperAdmin())throw new CHttpException(403);$error='';
  if(Yii::app()->request->isPostRequest){try{$id=(int)Yii::app()->request->getPost('id',0);$user=$id?User::model()->findByPk($id):new User;if(!$user)throw new Exception('کاربر پیدا نشد.');$role=Yii::app()->request->getPost('role');$email=Yii::app()->request->getPost('email');$name=Yii::app()->request->getPost('name');$password=Yii::app()->request->getPost('password','');$active=Yii::app()->request->getPost('is_active')==='1'?1:0;
   if(!is_string($email)||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||!is_string($name)||mb_strlen($name)>150||!trim($name)||!is_string($role)||!isset(self::roles()[$role]))throw new Exception('اطلاعات معتبر نیست.');if($user->id==Yii::app()->user->id&&($role!=='superadmin'||!$active))throw new Exception('نقش و فعالیت حساب خودتان را نمی‌توانید محدود کنید.');if(!is_string($password)||($password!==''&&strlen($password)<12)||($user->isNewRecord&&$password===''))throw new Exception('رمز حساب جدید باید حداقل ۱۲ کاراکتر باشد.');
   $user->name=trim($name);$user->email=strtolower($email);$user->role=$role;$user->is_active=$active;if($password!=='')$user->password_hash=password_hash($password,PASSWORD_DEFAULT);$user->save(false,array('name','email','role','is_active','password_hash'));Audit::record('users',$user->id,'permissions',array('role'=>$role,'active'=>$active));$this->flash('کاربر ذخیره شد.');$this->refresh();
  }catch(Exception $e){$error='ذخیره کاربر انجام نشد؛ اطلاعات و ایمیل تکراری را بررسی کنید.';}}
  $this->pageTitle='کاربران مدیریت';$this->render('//admin/users/index',array('error'=>$error));
 }
 public static function roles(){return array('superadmin'=>'مدیر اصلی','admin'=>'مدیر فروشگاه','content'=>'محتوا','sales'=>'سفارش‌ها','warehouse'=>'انبار و گزارش‌ها');}
}
