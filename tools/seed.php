<?php
// Optional sample catalog for a new installation. Does not overwrite existing items.
if(PHP_SAPI!=='cli'&&!defined('BAZAARCHE_INSTALL_CONFIG')) { http_response_code(403); exit; }
$root=dirname(__DIR__);
$dsn=defined('BAZAARCHE_INSTALL_CONFIG')?Yii::app()->db->connectionString:(getenv('SHOP_DSN') ?: 'sqlite:'.$root.'/protected/runtime/store.sqlite');
$db=defined('BAZAARCHE_INSTALL_CONFIG')?Yii::app()->db->pdoInstance:new PDO($dsn,getenv('SHOP_DB_USER')?:'',getenv('SHOP_DB_PASSWORD')?:'',array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
if(strpos($dsn,'sqlite:')===0) $db->exec('PRAGMA foreign_keys=ON');
if((int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn()>0) { echo "Sample catalog skipped; products already exist.\n"; require __DIR__.'/seed-demo.php';if(defined('BAZAARCHE_INSTALL_CONFIG'))return; exit(0); }
$categories=array(
  array('خانه و دکور','home-decor'),array('سبک زندگی','lifestyle'),array('منتخب بازارچه','featured'),
);
$products=array(
  array('چراغ رومیزی مینیمال','minimal-desk-lamp','LAMP-101',1680000,12,0,'نور ملایم و طراحی جمع‌وجور برای فضای کار','رنگ: کرم\nجنس بدنه: فلزی\nمناسب اتاق خواب و میز کار','رنگ=کرم\nجنس=فلز'),
  array('گلدان سرامیکی آرتا','arta-ceramic-vase','VASE-102',980000,23,0,'گلدان دکوراتیو با خطوط نرم و پرداخت مات','گلدانی مناسب دکورهای مدرن و مینیمال','ارتفاع=۲۵ سانتی‌متر\nجنس=سرامیک'),
  array('ست ظروف چوبی نوژان','nozhan-wood-set','WOOD-103',1320000,16,1,'زیبایی طبیعی چوب در آشپزخانه شما','مجموعه ظروف دکوراتیو از چوب فرآوری‌شده','رنگ=طبیعی\nمجموعه=۳ قطعه'),
  array('ساعت دیواری استودیو','studio-wall-clock','CLOCK-104',2150000,8,0,'خوانا، ساده و هماهنگ با فضاهای روشن','موتور بی‌صدا و قاب سبک','قطر=۳۵ سانتی‌متر'),
  array('ماگ طرح آونتا','aventa-coffee-mug','MUG-105',465000,46,1,'همراه دوست‌داشتنی برای لحظه‌های استراحت','قابل شستشو و مناسب استفاده روزانه','حجم=۳۵۰ میلی‌لیتر\nجنس=سرامیک'),
  array('جعبه نگهدارنده چوبی','wood-storage-box','BOX-106',760000,29,2,'راهکاری زیبا برای مرتب‌سازی میز و قفسه','سبک، مقاوم و مناسب خانه و محل کار','ابعاد=۲۰×۱۵×۱۲ سانتی‌متر'),
);
$db->beginTransaction();
try {
  $catIds=array();
  foreach($categories as $c) {
    $s=$db->prepare('INSERT INTO categories(name,slug,description) VALUES(?,?,?)');$s->execute(array($c[0],$c[1],''));
    $catIds[]=$db->lastInsertId();
  }
  $insert=$db->prepare('INSERT INTO products (title,slug,sku,price,compare_price,stock,category_id,summary,description,status,featured,seo_title,seo_description,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
  foreach($products as $i=>$p) {
    $insert->execute(array($p[0],$p[1],$p[2],$p[3],0,$p[4],$catIds[$p[5]],$p[6],str_replace('\\n',"\n",$p[7]),'published',1,$p[0],$p[6],date('Y-m-d H:i:s'),date('Y-m-d H:i:s')));
    $id=$db->lastInsertId();
    $pairs=explode("\n",str_replace('\\n',"\n",$p[8]));
    foreach($pairs as $line) {
      $bits=explode('=',$line,2);
      if(count($bits)===2) $db->prepare('INSERT INTO product_attributes (product_id,name,value) VALUES(?,?,?)')->execute(array($id,$bits[0],$bits[1]));
    }
  }
  $posts=array(
    array('چطور دکور ساده و کاربردی بسازیم؟','simple-home-decor','ایده‌هایی برای چیدمان خلوت و کاربردی در خانه','برای شروع از چند وسیله ساده استفاده کنید. رنگ‌های هماهنگ و نور طبیعی به فضا آرامش می‌دهند. هر وسیله باید دلیل مشخصی برای حضور در خانه داشته باشد.'),
    array('راهنمای انتخاب هدیه کاربردی','practical-gift-guide','چند نکته برای انتخاب هدیه‌ای ماندگار','سلیقه و نیاز روزمره گیرنده را در نظر بگیرید. بسته‌بندی تمیز و کیفیت ساخت مناسب، حتی یک هدیه کوچک را خاص می‌کند.'),
  );
  $insertPost=$db->prepare('INSERT INTO posts(title,slug,excerpt,body,cover_image,status,seo_title,seo_description,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?)');
  foreach($posts as $p) $insertPost->execute(array($p[0],$p[1],$p[2],$p[3],'','published',$p[0],$p[2],date('Y-m-d H:i:s'),date('Y-m-d H:i:s')));
  $db->commit(); echo "6 demo products, 3 categories and 2 posts added.\n";
} catch(Exception $e) { if($db->inTransaction())$db->rollBack();if(defined('BAZAARCHE_INSTALL_CONFIG'))throw $e; fwrite(STDERR,$e->getMessage()."\n");exit(1); }
require __DIR__.'/seed-demo.php';
