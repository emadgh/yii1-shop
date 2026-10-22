<?php
/** Atomic single-subtree moves; stale tree revisions never overwrite newer ordering. */
class CategoryTreeService {
 public static function revision($rows=null){$rows=$rows===null?Hierarchy::rows('categories'):$rows;$state=array();foreach($rows as $row)$state[]=array((int)$row['id'],(int)$row['parent_id'],(int)$row['sort_order']);return hash('sha256',CJSON::encode($state));}
 public static function move($id,$target,$position,$revision){
  if(!in_array($position,array('before','after','inside','root'),true))throw new CHttpException(422,'نوع جابه‌جایی معتبر نیست.');
  $db=Yii::app()->db;$tx=$db->beginTransaction();
  try{
   if($db->driverName==='mysql')$db->createCommand('SELECT id FROM categories ORDER BY id FOR UPDATE')->queryAll();else $db->createCommand('UPDATE categories SET sort_order=sort_order')->execute();
   $rows=Hierarchy::rows('categories');if(!is_string($revision)||!hash_equals(self::revision($rows),$revision))throw new CHttpException(409,'ساختار دسته‌ها تغییر کرده است؛ درخت را تازه و دوباره جابه‌جا کنید.');
   $map=array();foreach($rows as $row)$map[(int)$row['id']]=$row;$id=(int)$id;$target=(int)$target;
   if(!isset($map[$id])||($position!=='root'&&!isset($map[$target])))throw new CHttpException(422,'دسته انتخاب‌شده پیدا نشد.');
   $descendants=Hierarchy::descendants($rows,$id);if($position!=='root'&&in_array($target,$descendants,true))throw new CHttpException(422,'یک دسته را نمی‌توان داخل خودش یا زیرمجموعه‌هایش قرار داد.');
   $oldParent=(int)$map[$id]['parent_id'];$parent=$position==='root'?0:($position==='inside'?$target:(int)$map[$target]['parent_id']);
   $depth=0;$ancestor=$parent;while($ancestor){if(++$depth>40||!isset($map[$ancestor]))throw new CHttpException(422,'عمق دسته‌بندی بیش از حد مجاز است.');$ancestor=(int)$map[$ancestor]['parent_id'];}
   foreach($descendants as $child){$relative=0;$ancestor=$child;while($ancestor!==$id&&isset($map[$ancestor])&&$relative<=40){$ancestor=(int)$map[$ancestor]['parent_id'];$relative++;}if($depth+$relative>40)throw new CHttpException(422,'عمق دسته‌بندی بیش از حد مجاز است.');}
   $siblings=array();foreach($rows as $row)if((int)$row['parent_id']===$parent&&(int)$row['id']!==$id)$siblings[]=(int)$row['id'];
   $index=count($siblings);if(in_array($position,array('before','after'),true)){$index=array_search($target,$siblings,true);if($index===false)throw new CHttpException(422,'مقصد جابه‌جایی معتبر نیست.');if($position==='after')$index++;}array_splice($siblings,$index,0,array($id));
   $db->createCommand()->update('categories',array('parent_id'=>$parent?:null),'id=:id',array(':id'=>$id));foreach($siblings as $order=>$sibling)$db->createCommand()->update('categories',array('sort_order'=>$order),'id=:id',array(':id'=>$sibling));
   if($oldParent!==$parent){$order=0;foreach($rows as $row)if((int)$row['parent_id']===$oldParent&&(int)$row['id']!==$id)$db->createCommand()->update('categories',array('sort_order'=>$order++),'id=:id',array(':id'=>$row['id']));}
   $tx->commit();return self::revision();
  }catch(Exception $e){if($tx->active)$tx->rollback();throw $e;}
 }
}
