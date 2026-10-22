<?php
class MenuItem extends CActiveRecord {
 public static function model($className=__CLASS__){return parent::model($className);}
 public function tableName(){return 'menu_items';}
 public function rules(){return array(array('label,url,location','required'),array('label','length','max'=>150),array('url','length','max'=>500),array('url','checkUrl'),array('parent_id','checkParent'),array('parent_id,sort_order','numerical','integerOnly'=>true,'min'=>0,'allowEmpty'=>true),array('location','in','range'=>array('header','footer')),array('is_active','boolean'));}
 public function checkUrl(){if(!preg_match('~^/(?!/)[^\s<>"\x00-\x1f]*$|^https://[^\s<>"\x00-\x1f]+$~uD',$this->url) || strpos($this->url,'\\')!==false)$this->addError('url','فقط مسیر داخلی با / یا آدرس https مجاز است.');}
 public function checkParent(){if(!Hierarchy::validParent('menu_items',$this->id,$this->parent_id,$this->location))$this->addError('parent_id','والد نامعتبر است یا منجر به چرخه می‌شود.');if(!$this->isNewRecord){$n=Yii::app()->db->createCommand('SELECT COUNT(*) FROM menu_items WHERE parent_id=:id AND location<>:location')->queryScalar(array(':id'=>$this->id,':location'=>$this->location));if($n)$this->addError('location','ابتدا محل نمایش زیرمنوها را هماهنگ کنید.');}}
}
