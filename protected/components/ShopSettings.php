<?php
class ShopSettings {
 private static $values;
 public static function get($key,$default='') {
   if(self::$values===null) { self::$values=array(); foreach(Yii::app()->db->createCommand('SELECT setting_key,setting_value FROM settings')->queryAll() as $row) self::$values[$row['setting_key']]=$row['setting_value']; }
   return isset(self::$values[$key])?self::$values[$key]:$default;
 }
 public static function set($key,$value) {
   $db=Yii::app()->db;
   if($db->createCommand('SELECT COUNT(*) FROM settings WHERE setting_key=:k')->queryScalar(array(':k'=>$key))) $db->createCommand()->update('settings',array('setting_value'=>$value),'setting_key=:k',array(':k'=>$key));
   else $db->createCommand()->insert('settings',array('setting_key'=>$key,'setting_value'=>$value));
   self::$values=null;
 }
}
