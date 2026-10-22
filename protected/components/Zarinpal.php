<?php
/** v4 gateway. Never trust callback status, amounts or reference IDs from a browser. */
class Zarinpal {
 public static $transport;
 public static function enabled(){return ShopSettings::get('zarinpal_enabled','0')==='1'&&preg_match('/^[a-f0-9-]{36}$/iD',ShopSettings::get('zarinpal_merchant',''));}
 public static function call($method,$payload,$mode){
  if(self::$transport)return call_user_func(self::$transport,$method,$payload,$mode);
  $host=$mode==='sandbox'?'https://sandbox.zarinpal.com':'https://payment.zarinpal.com';
  $ctx=stream_context_create(array('http'=>array('method'=>'POST','header'=>"Content-Type: application/json\r\nAccept: application/json\r\n",'content'=>json_encode($payload),'timeout'=>20,'ignore_errors'=>true),'ssl'=>array('verify_peer'=>true,'verify_peer_name'=>true)));
  $raw=@file_get_contents($host.'/pg/v4/payment/'.$method.'.json',false,$ctx);$result=$raw===false?null:json_decode($raw,true);
  if(!is_array($result))throw new Exception('ارتباط با زرین‌پال برقرار نشد؛ دوباره تلاش کنید.');return $result;
 }
 public static function start($orderId){
  if(!self::enabled())throw new Exception('درگاه پرداخت هنوز فعال نشده است.');$db=Yii::app()->db;$row=$db->createCommand('SELECT * FROM orders WHERE id=:id')->queryRow(true,array(':id'=>$orderId));
  if(!$row||!in_array($row['status'],array('pending','confirmed'),true)||$row['reserved_until']<=date('Y-m-d H:i:s'))throw new Exception('سفارش قابل پرداخت نیست یا مهلت رزرو پایان یافته است.');
  $mode=ShopSettings::get('zarinpal_mode','sandbox')==='live'?'live':'sandbox';$merchant=ShopSettings::get('zarinpal_merchant','');$base=rtrim(ShopSettings::get('public_url',''),'/');
  if(!filter_var($base,FILTER_VALIDATE_URL)||($mode==='live'&&parse_url($base,PHP_URL_SCHEME)!=='https'))throw new Exception('آدرس عمومی سایت را در تنظیمات وارد کنید.');
  $amount=(int)$row['total']*10;$now=date('Y-m-d H:i:s');
  $r=self::call('request',array('merchant_id'=>$merchant,'amount'=>$amount,'currency'=>'IRR','description'=>'سفارش '.$row['order_number'],'callback_url'=>$base.'/payment/callback','metadata'=>array('email'=>$row['customer_email'],'mobile'=>$row['customer_phone'])),$mode);
  $authority=$r['data']['authority']??'';if((int)($r['data']['code']??0)!==100||!preg_match('/^[A-Za-z0-9]{30,100}$/D',$authority))throw new Exception('ایجاد درخواست پرداخت ناموفق بود.');
  $db->createCommand()->insert('payments',array('order_id'=>$orderId,'authority'=>$authority,'amount'=>$amount,'mode'=>$mode,'merchant'=>$merchant,'state'=>'requested','created_at'=>$now,'updated_at'=>$now));
  return ($mode==='sandbox'?'https://sandbox.zarinpal.com':'https://www.zarinpal.com').'/pg/StartPay/'.$authority;
 }
 public static function verify($authority){
  if(!is_string($authority)||!preg_match('/^[A-Za-z0-9]{30,100}$/D',$authority))throw new Exception('شناسه پرداخت معتبر نیست.');$db=Yii::app()->db;
  $payment=$db->createCommand('SELECT * FROM payments WHERE authority=:a')->queryRow(true,array(':a'=>$authority));if(!$payment)throw new Exception('پرداخت پیدا نشد.');
  if(in_array($payment['state'],array('paid','review'),true))return $payment;
  $r=self::call('verify',array('merchant_id'=>$payment['merchant'],'amount'=>(int)$payment['amount'],'authority'=>$authority),$payment['mode']);$code=(int)($r['data']['code']??0);$ref=(string)($r['data']['ref_id']??'');
  if(!in_array($code,array(100,101),true)||!preg_match('/^[0-9]{1,100}$/D',$ref))throw new Exception('پرداخت توسط زرین‌پال تأیید نشد؛ می‌توانید دوباره بررسی کنید.');
  $tx=$db->beginTransaction();try{
   $lock=$db->driverName==='mysql'?' FOR UPDATE':'';$p=$db->createCommand('SELECT * FROM payments WHERE id=:id'.$lock)->queryRow(true,array(':id'=>$payment['id']));
   if(in_array($p['state'],array('paid','review'),true)){$tx->commit();return $p;}
   $o=$db->createCommand('SELECT * FROM orders WHERE id=:id'.$lock)->queryRow(true,array(':id'=>$p['order_id']));
   $state='review';if($o&&in_array($o['status'],array('pending','confirmed'),true)&&$o['reserved_until']>date('Y-m-d H:i:s')&&(int)$o['total']*10===(int)$p['amount']){OrderService::changeStatus($o['id'],'paid');$state='paid';}
   $db->createCommand()->update('payments',array('state'=>$state,'ref_id'=>$ref,'updated_at'=>date('Y-m-d H:i:s')),'id=:id',array(':id'=>$p['id']));$tx->commit();$p['state']=$state;$p['ref_id']=$ref;return $p;
  }catch(Exception $e){if($tx->active)$tx->rollback();throw $e;}
 }
}
