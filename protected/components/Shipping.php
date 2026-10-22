<?php
class Shipping {
 public static function methods(){return array('standard'=>'ارسال عادی','pickup'=>'تحویل حضوری');}
 public static function quote($method,$subtotal){
  if(!isset(self::methods()[$method]))throw new Exception('روش ارسال معتبر نیست.');if($method==='pickup')return 0;
  $threshold=(int)ShopSettings::get('shipping_free_threshold','0');return $threshold>0&&$subtotal>=$threshold?0:(int)ShopSettings::get('shipping_fee','0');
 }
}
