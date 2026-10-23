<?php
class ThemeOptions {
 public static function blockTypes(){return array('hero'=>'بنر اصلی','categories'=>'دسته‌بندی‌ها','collection'=>'محصولات کالکشن','latest_products'=>'آخرین محصولات','popular_products'=>'پربازدیدترین محصولات','category_products'=>'محصولات یک دسته‌بندی','blog'=>'تازه‌های مجله','banner'=>'نوار معرفی');}
 public static function defaults($key){$market=strpos($key,'market')===0;return array('hero_title'=>$key==='market-light'?'انتخاب‌های امروز، قیمت‌های بهتر':($key==='market-dark'?'برای انتخاب‌های دقیق‌تر':($key==='minimal-dark'?'زیبایی در جزئیات.':'کمتر، اما بهتر.')),'hero_text'=>$market?'از کالاهای خانه تا جزئیات روزمره؛ با اطلاعات کامل و انتخابی مطمئن.':'اشیای باکیفیت برای زندگی آرام‌تر، در فضای خانه و روزمره.','hero_media'=>0,'product_layout'=>$market?'split':'editorial','card_style'=>$market?'standard':'editorial','show_categories'=>1,'hero_asset'=>$market && $key==='market-light'?'hero-market':(strpos($key,'dark')!==false?'hero-editorial-dark':'hero-editorial-light'));}
 public static function get($key=null){$key=$key?:ThemeService::key();$saved=json_decode(ShopSettings::get('theme_options_'.$key,'{}'),true);return array_merge(self::defaults($key),is_array($saved)?array_intersect_key($saved,self::defaults($key)):array());}
 public static function blocks($key=null){return Yii::app()->db->createCommand('SELECT * FROM theme_blocks WHERE theme_key=:key ORDER BY sort_order,id')->queryAll(true,array(':key'=>$key?:ThemeService::key()));}
 public static function hero($options){if(!empty($options['hero_media'])){$row=MediaService::find((int)$options['hero_media']);if($row && strpos($row['mime'],'image/')===0)return UploadService::url($row['filename']);}return Yii::app()->baseUrl.'/assets/storefront/'.$options['hero_asset'].'.webp';}
 public static function save($key,$data,$blocks){
  if(!ThemeService::valid($key) || !is_array($data) || !is_array($blocks))throw new Exception('تنظیمات قالب معتبر نیست.');
  $options=self::get($key);foreach(array('hero_title'=>140,'hero_text'=>500) as $field=>$max){$value=trim(isset($data[$field])?(string)$data[$field]:'');if(mb_strlen($value)>$max)throw new Exception('متن بنر بیش از حد بلند است.');$options[$field]=$value;}
  if(!in_array($data['product_layout']??'',array('split','gallery','editorial'),true) || !in_array($data['card_style']??'',array('standard','compact','editorial'),true))throw new Exception('مدل نمایش معتبر نیست.');
  $selected=MediaService::selected($data['hero_media']??'',true,1);$options['hero_media']=$selected?(int)$selected[0]['id']:0;$options['product_layout']=$data['product_layout'];$options['card_style']=$data['card_style'];
  if(count($blocks)>16)throw new Exception('حداکثر ۱۶ بخش مجاز است.');$validated=array();
  foreach($blocks as $i=>$block){if(!is_array($block))throw new Exception('بخش نامعتبر است.');$type=$block['block_type']??'';$presentation=$block['presentation']??'grid';$style=$block['card_style']??'standard';
   if(!is_string($type)||!isset(self::blockTypes()[$type]) || !in_array($presentation,array('grid','carousel'),true) || !in_array($style,array('standard','compact','editorial'),true))throw new Exception('نوع ویجت یا مدل نمایش مجاز نیست.');
   $collection=(int)($block['collection_id']??0);if($collection && !ProductCollection::model()->findByPk($collection))throw new Exception('کالکشن پیدا نشد.');
   $category=null;
   if($type==='category_products'){
    $value=$block['category_id']??'';
    if(!is_scalar($value)||!ctype_digit((string)$value)||(int)$value<1||!Category::model()->exists('id=:id',array(':id'=>(int)$value)))throw new Exception('برای ویجت محصولات دسته‌بندی، یک دسته‌بندی معتبر انتخاب کنید.');
    $category=(int)$value;
   }
   $title=trim((string)($block['title']??''));if(mb_strlen($title)>180)throw new Exception('عنوان بخش بیش از حد بلند است.');
   $limit=(int)($block['item_limit']??8);$position=(int)($block['sort_order']??$i);if($limit<1 || $limit>24 || $position<0 || $position>1000)throw new Exception('تعداد کالا باید بین ۱ تا ۲۴ و ترتیب بین ۰ تا ۱۰۰۰ باشد.');
   $validated[]=array('theme_key'=>$key,'block_type'=>$type,'title'=>$title,'collection_id'=>$type==='collection'?($collection?:null):null,'category_id'=>$category,'presentation'=>$presentation,'card_style'=>$style,'item_limit'=>$limit,'sort_order'=>$position,'is_enabled'=>empty($block['is_enabled'])?0:1);
  }
  $db=Yii::app()->db;$tx=TransactionScope::begin();try{ShopSettings::set('theme_options_'.$key,CJSON::encode($options));$db->createCommand()->delete('theme_blocks','theme_key=:key',array(':key'=>$key));foreach($validated as $block)$db->createCommand()->insert('theme_blocks',$block);$tx->commit();}catch(Exception $e){if($tx->active)$tx->rollback();throw $e;}
 }
}
