<?php
/** User-facing calendar only; SQL timestamps and machine-readable dates stay Gregorian. */
class JalaliDate {
 public static function digits($value){return strtr((string)$value,array_combine(preg_split('//u','۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩',-1,PREG_SPLIT_NO_EMPTY),str_split('01234567890123456789')));}
 public static function format($value,$withTime=true){
  if(!$value)return '—';$date=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',substr($value,0,10).' '.(strlen($value)>10?substr($value,11,8):'00:00:00'),new DateTimeZone('Asia/Tehran'));
  if(!$date)return '—';$j=\Morilog\Jalali\CalendarUtils::toJalali((int)$date->format('Y'),(int)$date->format('m'),(int)$date->format('d'));
  return sprintf('%04d/%02d/%02d',$j[0],$j[1],$j[2]).($withTime?' '.$date->format('H:i'):'');
 }
 public static function parse($value,$withTime=true){
  if(!is_scalar($value))throw new InvalidArgumentException('تاریخ جلالی معتبر نیست.');$value=self::digits(trim((string)$value));
  $pattern=$withTime?'~^(\d{4})/(\d{1,2})/(\d{1,2}) (\d{2}):(\d{2})(?::(\d{2}))?$~D':'~^(\d{4})/(\d{1,2})/(\d{1,2})$~D';
  if(!preg_match($pattern,$value,$m)||(int)$m[1]<1200||(int)$m[1]>1600||!\Morilog\Jalali\CalendarUtils::checkDate((int)$m[1],(int)$m[2],(int)$m[3]))throw new InvalidArgumentException('تاریخ را به صورت جلالی مثل ۱۴۰۵/۰۱/۰۱ وارد کنید.');
  $hour=$withTime?(int)$m[4]:0;$minute=$withTime?(int)$m[5]:0;$second=$withTime?(int)($m[6]??0):0;
  if($hour>23||$minute>59||$second>59)throw new InvalidArgumentException('ساعت معتبر نیست.');
  $g=\Morilog\Jalali\CalendarUtils::toGregorian((int)$m[1],(int)$m[2],(int)$m[3]);return sprintf('%04d-%02d-%02d',$g[0],$g[1],$g[2]).($withTime?sprintf(' %02d:%02d:%02d',$hour,$minute,$second):'');
 }
 public static function field($name,$value,$time=false,$options=array()){
  return CHtml::textField($name,$value,array_merge(array('data-jdp'=>'','data-jdp-only-date'=>$time?null:'','dir'=>'ltr','autocomplete'=>'off','placeholder'=>$time?'1405/01/01 12:00':'1405/01/01','data-jalali-date'=>$time?'datetime':'date'),$options));
 }
 public static function assets(){
  $cs=Yii::app()->clientScript;$base=Yii::app()->baseUrl.'/assets/';$cs->registerCssFile($base.'vendor/jalalidatepicker/jalalidatepicker.min.css');$cs->registerCssFile($base.'css/jalali.css');
  $cs->registerScriptFile($base.'vendor/jalalidatepicker/jalalidatepicker.min.js',CClientScript::POS_END);$cs->registerScriptFile($base.'js/jalali.js',CClientScript::POS_END);
 }
}
