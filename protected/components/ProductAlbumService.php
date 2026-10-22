<?php
/** Album edits share the product transaction and its row lock. */
class ProductAlbumService {
 public static function revision($productId){$db=Yii::app()->db;$params=array(':p'=>$productId);return hash('sha256',CJSON::encode(array($db->createCommand('SELECT id,title,layout,sort_order FROM product_albums WHERE product_id=:p ORDER BY sort_order,id')->queryAll(true,$params),$db->createCommand('SELECT id,album_id,filename,sort_order FROM product_images WHERE product_id=:p ORDER BY sort_order,id')->queryAll(true,$params))));}
 public static function editorRows($product){
  if($product->isNewRecord)return array(array('id'=>0,'title'=>'تصاویر محصول','layout'=>'grid','media_ids'=>''));
  $rows=array();foreach($product->albums as $album){$ids=array();foreach($album->images as $image){$id=Yii::app()->db->createCommand('SELECT id FROM media WHERE filename=:f')->queryScalar(array(':f'=>$image->filename));if($id)$ids[]=$id;}$rows[]=array('id'=>(int)$album->id,'title'=>$album->title,'layout'=>$album->layout,'media_ids'=>implode(',',$ids));}return $rows;
 }
 public static function normalize($rows){
  if(!is_array($rows)||count($rows)>20)throw new Exception('حداکثر ۲۰ آلبوم برای هر محصول مجاز است.');$out=array();$seen=array();$total=0;
  foreach($rows as $row){
   if(!is_array($row))throw new Exception('اطلاعات آلبوم معتبر نیست.');
   foreach(array('id','title','layout','media_ids') as $key)if(isset($row[$key])&&!is_scalar($row[$key]))throw new Exception('اطلاعات آلبوم معتبر نیست.');
   $id=(string)($row['id']??'0');$title=trim((string)($row['title']??''));$layout=$row['layout']??'grid';$ids=trim((string)($row['media_ids']??''));
   if(!ctype_digit($id)||($id!=='0'&&isset($seen[(int)$id])))throw new Exception('شناسه آلبوم نامعتبر یا تکراری است.');$seen[(int)$id]=true;
   if($title===''||mb_strlen($title)>150)throw new Exception('عنوان هر آلبوم الزامی و حداکثر ۱۵۰ حرف است.');
   if(!isset(ProductAlbum::layouts()[$layout]))throw new Exception('سبک نمایش آلبوم معتبر نیست.');
   if($ids!==''&&!preg_match('/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/D',$ids))throw new Exception('شناسه تصاویر آلبوم معتبر نیست.');
   $media=MediaService::selected($ids,true,100);$total+=count($media);if($total>500)throw new Exception('حداکثر ۵۰۰ تصویر برای هر محصول مجاز است.');
   $out[]=array('id'=>(int)$id,'title'=>$title,'layout'=>$layout,'media_ids'=>$ids,'media'=>$media);
  }return $out;
 }
 public static function save($product,$rows,$revision){
  $db=Yii::app()->db;if(!$db->currentTransaction||!$db->currentTransaction->active)throw new LogicException('Album save requires the product transaction.');
  if($revision!==null&&(!is_string($revision)||!hash_equals(self::revision($product->id),$revision)))throw new Exception('آلبوم‌های محصول پس از بازکردن فرم تغییر کرده‌اند؛ صفحه را تازه کنید.');
  $existing=$db->createCommand('SELECT id FROM product_albums WHERE product_id=:p')->queryColumn(array(':p'=>$product->id));$existing=array_map('intval',$existing);$keep=array();
  foreach($rows as $row)if($row['id']&&!in_array($row['id'],$existing,true))throw new Exception('آلبوم انتخاب‌شده متعلق به این محصول نیست.');
  $db->createCommand()->delete('product_images','product_id=:p',array(':p'=>$product->id));$imageOrder=0;
  foreach($rows as $order=>$row){$data=array('title'=>$row['title'],'layout'=>$row['layout'],'sort_order'=>$order);
   if($row['id']){$id=$row['id'];$db->createCommand()->update('product_albums',$data,'id=:id AND product_id=:p',array(':id'=>$id,':p'=>$product->id));}
   else{$data['product_id']=$product->id;$db->createCommand()->insert('product_albums',$data);$id=(int)$db->lastInsertID;}$keep[]=$id;
   foreach($row['media'] as $media)$db->createCommand()->insert('product_images',array('product_id'=>$product->id,'album_id'=>$id,'filename'=>$media['filename'],'alt_text'=>$product->title,'sort_order'=>$imageOrder++));
  }
  foreach(array_diff($existing,$keep) as $id)$db->createCommand()->delete('product_albums','id=:id AND product_id=:p',array(':id'=>$id,':p'=>$product->id));
  Audit::record('products',$product->id,'albums',array('count'=>count($rows),'images'=>$imageOrder));
 }
}
