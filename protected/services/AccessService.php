<?php
class AccessService {
 private static $user;
 public static function current() {
   if(Yii::app()->user->isGuest)return null;
   if(self::$user===null)self::$user=User::model()->findByPk(Yii::app()->user->id);
   return self::$user && self::$user->is_active?self::$user:null;
 }
 public static function canRoute($route){$user=self::current();if(!$user)return false;if(in_array($user->role,array('admin','superadmin'),true))return true;$controller=explode('/',$route)[0];if(in_array($controller,array('site','shop','adminSecurity'),true))return true;$allowed=array('content'=>array('adminPost','adminMedia','adminTag','adminCategory','adminCollection','adminMenu'),'sales'=>array('adminOrder'),'warehouse'=>array('adminReport'));return in_array($controller,$allowed[$user->role]??array(),true);}
 public static function isAdmin() { $user=self::current();return $user && in_array($user->role,array('admin','superadmin','content','sales','warehouse'),true); }
 public static function isSuperAdmin() { $user=self::current();return $user && $user->role==='superadmin'; }
}
