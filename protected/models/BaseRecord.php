<?php
abstract class BaseRecord extends CActiveRecord
{
    protected function beforeSave() {
       if(!parent::beforeSave()) return false;
       if($this->hasAttribute('updated_at')) $this->updated_at=date('Y-m-d H:i:s');
       if($this->isNewRecord && $this->hasAttribute('created_at')) $this->created_at=date('Y-m-d H:i:s');
       return true;
    }
}
