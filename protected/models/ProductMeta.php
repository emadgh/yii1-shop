<?php
class ProductMeta extends BaseRecord
{
    public static function model($className=__CLASS__) { return parent::model($className); }
    public function tableName() { return 'product_meta'; }
}
