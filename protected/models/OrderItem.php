<?php
class OrderItem extends BaseRecord
{
    public static function model($className=__CLASS__) { return parent::model($className); }
    public function tableName() { return 'order_items'; }
    public function relations(){return array('product'=>array(self::BELONGS_TO,'Product','product_id'));}
}
