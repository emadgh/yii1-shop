<?php
class Money {
 public static function unit() { return ShopSettings::get('currency','toman')==='rial'?'ریال':'تومان'; }
 public static function factor() { return ShopSettings::get('currency','toman')==='rial'?10:1; }
 public static function format($amount,$unit=true) { return number_format((int)$amount*self::factor(),0,'.',',').($unit?' '.self::unit():''); }
 public static function input($value) { return number_format((int)$value*self::factor(),0,'.',','); }
 public static function parse($value) {
   $value=strtr((string)$value,array('۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'));
   $value=str_replace(array(',','٬',' '),'',$value);
   if(!preg_match('/^\d{1,14}$/D',$value)) throw new Exception('مبلغ معتبر وارد کنید.');
   if((int)$value%self::factor()!==0) throw new Exception('مبلغ ریالی باید مضربی از ۱۰ باشد.');
   return intdiv((int)$value,self::factor());
 }
}
