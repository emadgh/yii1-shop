<?php
class Audit {
 public static function record($entity,$id,$action,$changes=array()){
  $api=ApiTokenService::$actor;
  $actor=$api?$api['user_id']:(Yii::app()->hasComponent('user')&&!Yii::app()->user->isGuest?Yii::app()->user->id:null);
  Yii::app()->db->createCommand()->insert('audit_log',array('actor_id'=>$api?$api['user_id']:$actor,'api_token_id'=>$api?$api['id']:null,'entity'=>$entity,'entity_id'=>(string)$id,'action'=>$action,'changes'=>CJSON::encode($changes),'created_at'=>date('Y-m-d H:i:s')));
 }
}
