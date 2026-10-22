<?php
abstract class BaseRecord extends CActiveRecord
{
    private $auditBefore=array();
    protected function afterSave(){
       parent::afterSave();if(in_array($this->tableName(),array('products','posts','categories','collections'),true)){$changes=array();foreach(array('price','stock','status','title','slug','discount_type','discount_value') as $key)if($this->hasAttribute($key)&&(!array_key_exists($key,$this->auditBefore)||(string)$this->auditBefore[$key]!== (string)$this->$key))$changes[$key]=array('before'=>$this->auditBefore[$key]??null,'after'=>$this->$key);Audit::record($this->tableName(),$this->primaryKey,'save',$changes);}
    }
    protected function afterDelete(){parent::afterDelete();if(in_array($this->tableName(),array('products','posts','categories','collections'),true))Audit::record($this->tableName(),$this->primaryKey,'delete');}
    protected function beforeSave() {
       if(!parent::beforeSave()) return false;
       if(!$this->isNewRecord&&in_array($this->tableName(),array('products','posts','categories','collections'),true)){$old=$this->findByPk($this->primaryKey);$this->auditBefore=$old?$old->attributes:array();}
       if($this->hasAttribute('updated_at')) $this->updated_at=date('Y-m-d H:i:s');
       if($this->isNewRecord && $this->hasAttribute('created_at')) $this->created_at=date('Y-m-d H:i:s');
       return true;
    }
}
