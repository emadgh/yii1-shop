<?php
class ProductCollection extends BaseRecord {
 public static function model($className=__CLASS__){return parent::model($className);}
 public function tableName(){return 'collections';}
 public function rules(){return array(array('title','required'),array('title','length','max'=>180),array('slug','length','max'=>190),array('slug','unique'),array('slug','match','pattern'=>'/^[a-z0-9]+(?:-[a-z0-9]+)*$/D'),array('description','safe'),array('is_active','boolean'));}
 protected function beforeValidate(){if(!$this->slug)$this->slug=Slug::unique('ProductCollection',$this->title,$this->isNewRecord?null:$this->id);return parent::beforeValidate();}
 public function productIds(){return Yii::app()->db->createCommand('SELECT product_id FROM collection_products WHERE collection_id=:id ORDER BY position,product_id')->queryColumn(array(':id'=>$this->id));}
 public function products($limit=50){$criteria=new CDbCriteria;$criteria->condition="t.status='published' AND EXISTS (SELECT 1 FROM collection_products cp WHERE cp.product_id=t.id AND cp.collection_id=:collection)";$criteria->params=array(':collection'=>$this->id);$criteria->order='(SELECT cp.position FROM collection_products cp WHERE cp.product_id=t.id AND cp.collection_id=:positionCollection) ASC';$criteria->params[':positionCollection']=$this->id;$criteria->with=array('images','category');$criteria->limit=(int)$limit;return Product::model()->findAll($criteria);}
}
