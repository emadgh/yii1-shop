<?php
class ThemeService {
 public static function catalog() { return array(
  'classic'=>array('number'=>'00','title'=>'قالب قبلی','mode'=>'روشن','kind'=>'classic','description'=>'ظاهر فعلی؛ برای بازگشت به نسخه پیشین.','colors'=>array('#f8f8f4','#405b41'),'preview'=>''),
  'market-light'=>array('number'=>'01','title'=>'بازار روشن','mode'=>'روشن','kind'=>'market','description'=>'فروشگاه پُرمحصول با جستجوی برجسته، دسته‌بندی و پیشنهادهای ویژه؛ نزدیک به تجربه دیجیکالا.','colors'=>array('#ffffff','#ef4056'),'preview'=>'theme-01-market-light.png'),
  'market-dark'=>array('number'=>'02','title'=>'بازار شب','mode'=>'تیره','kind'=>'market','description'=>'فروشگاه حرفه‌ای با سطوح زغالی، تأکید نارنجی و پیشنهاد روز.','colors'=>array('#14181d','#f69652'),'preview'=>'theme-02-market-dark.png'),
  'minimal-light'=>array('number'=>'03','title'=>'مینیمال روشن','mode'=>'روشن','kind'=>'minimal','description'=>'چیدمان خلوت، عکس‌های بزرگ و فضای سفید؛ مناسب اشیای خانه و کالکشن‌های منتخب.','colors'=>array('#f7f5ef','#3159d8'),'preview'=>'theme-03-minimal-light.png'),
  'minimal-dark'=>array('number'=>'04','title'=>'گالری شب','mode'=>'تیره','kind'=>'minimal','description'=>'گالری مینیمال با زمینه گرم تیره، خطوط ظریف و تأکید بر تصویر و تایپوگرافی.','colors'=>array('#141413','#b4966b'),'preview'=>'theme-04-minimal-dark.png'),
 ); }
 public static function valid($key) { return is_string($key) && isset(self::catalog()[$key]); }
 public static function active() { $key=ShopSettings::get('active_theme','classic');return self::valid($key)?$key:'classic'; }
 public static function preview() { $key=Yii::app()->request->getQuery('_theme');return AccessService::isSuperAdmin() && self::valid($key)?$key:null; }
 public static function key() { return self::preview()?:self::active(); }
 public static function info() { return self::catalog()[self::key()]; }
 public static function activate($key) { if(!self::valid($key))throw new CHttpException(422,'قالب معتبر نیست.');ShopSettings::set('active_theme',$key); }
 public static function previewUrl($key) { return Yii::app()->createUrl('site/index',array('_theme'=>$key)); }
}
