<?php
class AccessService {
 private static $user;
 public static function current() {
   if(Yii::app()->user->isGuest)return null;
   if(self::$user===null)self::$user=User::model()->findByPk(Yii::app()->user->id);
   return self::$user && self::$user->is_active?self::$user:null;
 }
 public static function isAdmin() { $user=self::current();return $user && in_array($user->role,array('admin','superadmin'),true); }
 public static function isSuperAdmin() { $user=self::current();return $user && $user->role==='superadmin'; }
}
