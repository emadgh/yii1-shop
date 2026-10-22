<?php
class Category extends BaseRecord
{
    public static function model($className=__CLASS__) { return parent::model($className); }
    public function tableName() { return 'categories'; }
    public function rules() { return array(
        array('name','required'), array('name','length','max'=>180),
        array('slug','length','max'=>190), array('slug','unique'),array('slug','match','pattern'=>'/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),
        array('description','safe'),
        array('parent_id,sort_order','numerical','integerOnly'=>true,'min'=>0,'allowEmpty'=>true),array('parent_id','checkParent'),
    ); }
    public function relations() { return array('products'=>array(self::HAS_MANY,'Product','category_id')); }
    public function beforeValidate() {
       if(!$this->slug) $this->slug=Slug::unique('Category',$this->name,$this->isNewRecord ? null : $this->id);
       return parent::beforeValidate();
    }
    public function checkParent(){if(!Hierarchy::validParent('categories',$this->id,$this->parent_id))$this->addError('parent_id','والد نامعتبر است؛ یک دسته نمی‌تواند زیرمجموعه خودش باشد.');}
    public static function options($exclude=null){$rows=Hierarchy::rows('categories');$skip=$exclude?Hierarchy::descendants($rows,$exclude):array();$out=array();foreach(Hierarchy::flatten($rows) as $row)if(!in_array((int)$row['id'],$skip,true))$out[$row['id']]=str_repeat('— ',$row['_depth']).$row['name'];return $out;}
}
