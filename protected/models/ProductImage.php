<?php
class ProductImage extends BaseRecord
{
    public static function model($className=__CLASS__) { return parent::model($className); }
    public function tableName() { return 'product_images'; }
    public function relations() { return array('product'=>array(self::BELONGS_TO,'Product','product_id')); }
}
