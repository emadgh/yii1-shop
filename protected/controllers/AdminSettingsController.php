<?php
class AdminSettingsController extends AdminController {
 public function actionIndex(){
  $error='';
  if(Yii::app()->request->isPostRequest){try{
   $values=array();foreach(array('site_name'=>100,'site_description'=>500,'announcement'=>250,'footer_text'=>500,'contact_phone'=>60,'contact_email'=>190,'matomo_url'=>500,'matomo_site_id'=>12) as $key=>$max){$value=trim((string)Yii::app()->request->getPost($key,''));if(mb_strlen($value)>$max)throw new Exception('طول یکی از تنظیمات بیشتر از حد مجاز است.');$values[$key]=$value;}
   if(!$values['site_name'])throw new Exception('نام سایت را وارد کنید.');
   if($values['contact_email'] && !filter_var($values['contact_email'],FILTER_VALIDATE_EMAIL))throw new Exception('ایمیل معتبر نیست.');
   if($values['matomo_url'] && (!filter_var($values['matomo_url'],FILTER_VALIDATE_URL) || parse_url($values['matomo_url'],PHP_URL_SCHEME)!=='https' || parse_url($values['matomo_url'],PHP_URL_USER) || parse_url($values['matomo_url'],PHP_URL_QUERY) || parse_url($values['matomo_url'],PHP_URL_FRAGMENT)))throw new Exception('آدرس Matomo باید HTTPS و بدون پارامتر یا نام کاربری باشد.');
   if($values['matomo_site_id'] && (!ctype_digit($values['matomo_site_id']) || (int)$values['matomo_site_id']<1))throw new Exception('شناسه سایت Matomo معتبر نیست.');
   if(($values['matomo_url']==='')!==($values['matomo_site_id']===''))throw new Exception('آدرس Matomo و شناسه سایت را با هم وارد کنید.');
   if(AccessService::isSuperAdmin()){
    $values['cache_backend']=Yii::app()->request->getPost('cache_backend',ShopSettings::get('cache_backend','db'));
    if(!in_array($values['cache_backend'],array('db','file','off'),true))throw new Exception('نوع کش معتبر نیست.');
    $ttl=Yii::app()->request->getPost('cache_ttl',ShopSettings::get('cache_ttl','600'));
    if(!is_string($ttl)||!ctype_digit($ttl)||(int)$ttl<30||(int)$ttl>86400)throw new Exception('مدت کش باید بین ۳۰ و ۸۶۴۰۰ ثانیه باشد.');
    $values['cache_ttl']=$ttl;
    foreach(array('zarinpal_merchant','public_url') as $key){$raw=Yii::app()->request->getPost($key,'');if(!is_string($raw))throw new Exception('تنظیم درگاه معتبر نیست.');$values[$key]=trim($raw);}
    if($values['zarinpal_merchant']&&!preg_match('/^[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}$/iD',$values['zarinpal_merchant']))throw new Exception('Merchant ID معتبر نیست.');
    $values['zarinpal_mode']=Yii::app()->request->getPost('zarinpal_mode','sandbox')==='live'?'live':'sandbox';$values['zarinpal_enabled']=Yii::app()->request->getPost('zarinpal_enabled')==='1'?'1':'0';
    if($values['public_url']&&(!filter_var($values['public_url'],FILTER_VALIDATE_URL)||!in_array(parse_url($values['public_url'],PHP_URL_SCHEME),array('http','https'),true)||parse_url($values['public_url'],PHP_URL_USER)||parse_url($values['public_url'],PHP_URL_QUERY)||parse_url($values['public_url'],PHP_URL_FRAGMENT)))throw new Exception('آدرس عمومی سایت معتبر نیست.');
    if($values['zarinpal_enabled']==='1'&&(!$values['zarinpal_merchant']||!$values['public_url']||($values['zarinpal_mode']==='live'&&parse_url($values['public_url'],PHP_URL_SCHEME)!=='https')))throw new Exception('برای فعال‌سازی، Merchant ID و آدرس عمومی معتبر لازم است؛ حالت واقعی به HTTPS نیاز دارد.');
   }
   foreach(array('shipping_fee','shipping_free_threshold') as $key)$values[$key]=(string)Money::parse(Yii::app()->request->getPost($key,'0'));
   $values['currency']=(string)Yii::app()->request->getPost('currency');if(!in_array($values['currency'],array('toman','rial'),true))throw new Exception('واحد پول معتبر نیست.');
   foreach(array('reservation_minutes'=>array(1,10080),'analytics_retention_days'=>array(7,365)) as $key=>$range){$raw=(string)Yii::app()->request->getPost($key);if(!ctype_digit($raw) || (int)$raw<$range[0] || (int)$raw>$range[1])throw new Exception('مدت رزرو یا نگهداری آمار معتبر نیست.');$values[$key]=$raw;}
   foreach(array('commerce_enabled','catalog_show_prices','analytics_enabled') as $key)$values[$key]=Yii::app()->request->getPost($key)==='1'?'1':'0';
   $tx=Yii::app()->db->beginTransaction();try{foreach($values as $key=>$value)ShopSettings::set($key,$value);$tx->commit();}catch(Exception $e){$tx->rollback();throw $e;}$this->flash('تنظیمات ذخیره شد.');$this->refresh();
  }catch(Exception $e){$error=$e->getMessage();}}
  $this->pageTitle='تنظیمات وبسایت';$this->render('//admin/settings/index',array('error'=>$error));
 }
 public function actionClearCache(){
  $this->requirePost();if(!AccessService::isSuperAdmin())throw new CHttpException(403);
  Yii::app()->siteCache->invalidateAll();$this->flash('کش تمام بخش‌ها باطل شد؛ در درخواست بعدی بازسازی می‌شود.');$this->redirect(array('adminSettings/index'));
 }
}
