<?php
class Hierarchy {
 public static function rows($table){if(!in_array($table,array('categories','menu_items'),true))throw new Exception('Invalid hierarchy');return SiteCache::read(array("hierarchy",$table),array($table),function()use($table){return Yii::app()->db->createCommand("SELECT * FROM $table ORDER BY sort_order,id")->queryAll();});}
 public static function descendants($rows,$id){$out=array((int)$id);for($i=0;$i<count($out);$i++)foreach($rows as $row)if((int)$row['parent_id']===$out[$i] && !in_array((int)$row['id'],$out,true))$out[]=(int)$row['id'];return $out;}
 public static function flatten($rows,$parent=null,$depth=0,$seen=array()){
  $out=array();if($depth>40)return $out;
  foreach($rows as $row)if(($parent===null && !$row['parent_id']) || ($parent!==null && (int)$row['parent_id']===(int)$parent)){
   if(isset($seen[$row['id']]))continue;$seen[$row['id']]=true;$row['_depth']=$depth;$out[]=$row;$out=array_merge($out,self::flatten($rows,$row['id'],$depth+1,$seen));
  }return $out;
 }
 public static function validParent($table,$id,$parent,$location=null){
  if(!$parent)return true;$rows=self::rows($table);$exists=false;foreach($rows as $row)if((int)$row['id']===(int)$parent){$exists=true;if($location!==null && $row['location']!==$location)return false;}
  return $exists && (!$id || !in_array((int)$parent,self::descendants($rows,$id),true));
 }
 public static function save($model,$revision=null){
  $table=$model->tableName();if(!in_array($table,array('categories','menu_items'),true))throw new Exception('Invalid hierarchy');$db=Yii::app()->db;$tx=TransactionScope::begin();
  try{if($db->driverName==='mysql')$db->createCommand("SELECT id FROM $table ORDER BY id FOR UPDATE")->queryAll();else $db->createCommand("UPDATE $table SET sort_order=sort_order")->execute();if($revision!==null&&(!is_string($revision)||!hash_equals((new HierarchyTreeService($table,$table==='menu_items'?$model->location:null))->revision(),$revision)))throw new CHttpException(409,'ساختار درخت تغییر کرده است؛ درخت را تازه کنید و دوباره ذخیره کنید.');$saved=$model->save();$tx->commit();return $saved;}catch(Exception $e){if($tx->active)$tx->rollback();throw $e;}
 }
}
