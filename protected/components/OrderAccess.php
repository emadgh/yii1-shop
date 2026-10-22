<?php
class OrderAccess {
 public static function issue($id){$token=bin2hex(random_bytes(32));Yii::app()->db->createCommand()->insert('order_access',array('order_id'=>$id,'token_hash'=>hash('sha256',$token),'created_at'=>date('Y-m-d H:i:s')));return $token;}
 public static function find($token){if(!is_string($token)||!preg_match('/^[a-f0-9]{64}$/D',$token))return null;$id=Yii::app()->db->createCommand('SELECT order_id FROM order_access WHERE token_hash=:t')->queryScalar(array(':t'=>hash('sha256',$token)));return $id?Order::model()->with('items')->findByPk($id):null;}
}
