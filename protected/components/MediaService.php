<?php
class MediaService {
 public static function ingest($temp,$name) {
   $size=filesize($temp);if(!$size || $size>10*1024*1024)throw new Exception('حداکثر حجم فایل ۱۰ مگابایت است.');
   $mime=mime_content_type($temp);$token=bin2hex(random_bytes(16));$root=Yii::app()->params['uploadsDir'].'/media';
   if(!is_dir($root) && !mkdir($root,0755,true))throw new Exception('ساخت پوشه رسانه ممکن نیست.');
   $variants=array();$width=0;$height=0;
   if(in_array($mime,array('image/jpeg','image/png','image/webp'),true)) {
     $info=@getimagesize($temp);if(!$info || $info[0]*$info[1]>24000000)throw new Exception('ابعاد تصویر معتبر نیست یا از ۲۴ مگاپیکسل بیشتر است.');
     $image=@imagecreatefromstring(file_get_contents($temp));if(!$image)throw new Exception('تصویر خوانده نشد.');
     $width=$info[0];$height=$info[1];
     try { foreach(array('thumb'=>320,'medium'=>800,'large'=>1600) as $key=>$max) {
       $w=min($width,$max);$h=max(1,(int)round($height*$w/$width));$canvas=imagecreatetruecolor($w,$h);imagealphablending($canvas,false);imagesavealpha($canvas,true);imagefill($canvas,0,0,imagecolorallocatealpha($canvas,0,0,0,127));imagecopyresampled($canvas,$image,0,0,0,0,$w,$h,$width,$height);
       $relative='media/'.$token.'-'.$key.'.webp';if(!imagewebp($canvas,Yii::app()->params['uploadsDir'].'/'.$relative,84))throw new Exception('ساخت WebP ناموفق بود.');imagedestroy($canvas);$variants[$key]=array('path'=>$relative,'width'=>$w,'height'=>$h);
     } } catch(Exception $e) { foreach($variants as $v)@unlink(Yii::app()->params['uploadsDir'].'/'.$v['path']);imagedestroy($image);throw $e; }
     imagedestroy($image);$filename=$variants['large']['path'];$mime='image/webp';$size=filesize(Yii::app()->params['uploadsDir'].'/'.$filename);
   } elseif(in_array($mime,array('application/pdf','text/plain'),true)) {
     if($mime==='text/plain' && preg_match('/<\?(?:php|=)|<script\b/i',file_get_contents($temp)))throw new Exception('محتوای اجرایی در فایل متنی مجاز نیست.');
     $ext=$mime==='application/pdf'?'pdf':'txt';$filename='media/'.$token.'.'.$ext;
     if($mime==='application/pdf' && substr(file_get_contents($temp,false,null,0,5),0,5)!=='%PDF-')throw new Exception('فایل PDF معتبر نیست.');
     if(!copy($temp,Yii::app()->params['uploadsDir'].'/'.$filename))throw new Exception('ذخیره فایل ناموفق بود.');
   } else throw new Exception('فرمت مجاز: JPG، PNG، WebP، PDF و متن ساده.');
   try { Yii::app()->db->createCommand()->insert('media',array('filename'=>$filename,'original_name'=>mb_substr(basename($name),0,240),'mime'=>$mime,'size'=>$size,'width'=>$width,'height'=>$height,'variants'=>CJSON::encode($variants),'created_at'=>date('Y-m-d H:i:s'))); }
   catch(Exception $e) { self::removeFiles($filename,$variants);throw $e; }
   return self::find(Yii::app()->db->getLastInsertID());
 }
 public static function removeFiles($filename,$variants) { $paths=array($filename);foreach($variants as $v)$paths[]=$v['path'];foreach(array_unique($paths) as $path) if(preg_match('~^media/[a-f0-9]{32}(?:-(?:thumb|medium|large))?\.(?:webp|pdf|txt)$~D',$path))@unlink(Yii::app()->params['uploadsDir'].'/'.$path); }
 public static function find($id) { return Yii::app()->db->createCommand('SELECT * FROM media WHERE id=:id')->queryRow(true,array(':id'=>$id)); }
 public static function describe($row) {
   $variants=json_decode($row['variants'],true)?:array();$row['url']=UploadService::url($row['filename']);$row['thumb']=isset($variants['thumb'])?UploadService::url($variants['thumb']['path']):'';
   $row['variants']=$variants;return $row;
 }
 public static function selected($ids,$imageOnly=true,$max=20) {
   if(!is_array($ids))$ids=array_filter(explode(',',(string)$ids));$ids=array_values(array_unique(array_map('intval',$ids)));
   if(count($ids)>$max)throw new Exception('تعداد رسانه‌های انتخاب‌شده بیشتر از حد مجاز است.');$rows=array();
   foreach($ids as $id) { $row=self::find($id);if(!$row || ($imageOnly && strpos($row['mime'],'image/')!==0))throw new Exception('رسانه انتخاب‌شده معتبر نیست.');$rows[]=$row; }return $rows;
 }
 public static function responsive($filename,$alt,$options=array()) {
   $row=Yii::app()->db->createCommand('SELECT * FROM media WHERE filename=:p')->queryRow(true,array(':p'=>$filename));
   if($row) { $v=json_decode($row['variants'],true)?:array();$srcset=array();$seen=array();foreach($v as $variant){if(isset($seen[$variant['width']]))continue;$seen[$variant['width']]=true;$srcset[]=UploadService::url($variant['path']).' '.$variant['width'].'w';}if($srcset){$options['srcset']=implode(', ',$srcset);if(!isset($options['sizes']))$options['sizes']='(max-width: 640px) 90vw, (max-width: 1000px) 45vw, 400px';}$options['width']=$row['width'];$options['height']=$row['height']; }
   return CHtml::image(UploadService::url($filename),$alt,$options);
 }
}
