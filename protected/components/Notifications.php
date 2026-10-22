<?php
/** Transactional outbox: transport credentials are optional, never fabricate delivery. */
class Notifications {
 public static function warnReservations(){foreach(Yii::app()->db->createCommand("SELECT id FROM orders WHERE status IN ('pending','confirmed') AND reserved_until>:now AND reserved_until<=:soon LIMIT 200")->queryColumn(array(':now'=>date('Y-m-d H:i:s'),':soon'=>date('Y-m-d H:i:s',time()+300))) as $id)self::order($id,'reservation_warning');}
 public static function order($id,$status){$row=Yii::app()->db->createCommand('SELECT * FROM orders WHERE id=:id')->queryRow(true,array(':id'=>$id));if(!$row)return;$now=date('Y-m-d H:i:s');$labels=Order::model()->statuses();$labels['reservation_warning']='مهلت رزرو کمتر از پنج دقیقه است؛ در صورت تمایل پرداخت را تکمیل کنید.';
  foreach(array('email'=>$row['customer_email'],'sms'=>$row['customer_phone']) as $channel=>$recipient)if($recipient)try{Yii::app()->db->createCommand()->insert('notifications',array('dedupe_key'=>hash('sha256',$id.':'.$status.':'.$channel),'order_id'=>$id,'channel'=>$channel,'recipient'=>$recipient,'message'=>'سفارش '.$row['order_number'].'؛ وضعیت: '.($labels[$status]??$status),'available_at'=>$now,'created_at'=>$now));}catch(CDbException $e){if(!in_array((int)($e->errorInfo[1]??0),array(19,1062),true))throw $e;}
 }
}
