<?php
class TagService {
 public static function sync($type,$id,$text) {
   if(!in_array($type,array('product','post'),true))throw new Exception('Invalid relation');
   $names=array_unique(array_filter(array_map('trim',preg_split('/[,،\n]+/u',$text))));if(count($names)>30)throw new Exception('حداکثر ۳۰ تگ مجاز است.');
   Yii::app()->db->createCommand()->delete($type.'_tag',$type.'_id=:id',array(':id'=>$id));
   foreach($names as $name){if(mb_strlen($name)>180)throw new Exception('نام تگ بسیار بلند است.');$tag=Tag::model()->findByAttributes(array('name'=>$name));if(!$tag){$tag=new Tag;$tag->name=$name;if(!$tag->save())throw new Exception('تگ معتبر نیست.');}Yii::app()->db->createCommand()->insert($type.'_tag',array($type.'_id'=>$id,'tag_id'=>$tag->id));}
 }
}
