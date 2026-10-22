<?php
class Tag extends BaseRecord
{
    public static function model($className=__CLASS__) { return parent::model($className); }
    public function tableName() { return 'tags'; }
    public function rules() { return array(array('name','required'),array('name','length','max'=>180),array('slug','length','max'=>190),array('slug','unique')); }
    protected function beforeValidate() {
       if(!$this->slug) $this->slug=Slug::unique('Tag',$this->name,$this->isNewRecord ? null : $this->id);
       return parent::beforeValidate();
    }
}
