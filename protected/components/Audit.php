<?php
class Audit {
 public static function record($entity,$id,$action,$changes=array()){
  $actor=Yii::app()->hasComponent('user')&&!Yii::app()->user->isGuest?Yii::app()->user->id:null;
  Yii::app()->db->createCommand()->insert('audit_log',array('actor_id'=>$actor,'entity'=>$entity,'entity_id'=>(string)$id,'action'=>$action,'changes'=>CJSON::encode($changes),'created_at'=>date('Y-m-d H:i:s')));
 }
}
