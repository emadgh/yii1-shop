<?php
/** An independent snapshot of reusable product defaults, without identity or inventory. */
class ProductTemplate extends CActiveRecord
{
    public static function model($className=__CLASS__){return parent::model($className);}
    public function tableName(){return 'product_templates';}
    public function rules(){return array(array('name,payload,created_at','required'),array('name','length','max'=>150),array('name','unique'));}
}
