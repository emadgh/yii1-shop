<?php
class Order extends BaseRecord
{
    public static function model($className=__CLASS__) { return parent::model($className); }
    public function tableName() { return 'orders'; }
    public function relations() { return array('items'=>array(self::HAS_MANY,'OrderItem','order_id')); }
    public function statuses() { return array('pending'=>'در انتظار پرداخت / رزرو','confirmed'=>'تأییدشده / رزرو','paid'=>'پرداخت‌شده','shipped'=>'ارسال‌شده','completed'=>'تحویل‌شده','returned'=>'مرجوع‌شده','cancelled'=>'لغوشده','expired'=>'رزرو منقضی'); }
    public function nextStatuses() { $map=OrderService::transitions(); $all=$this->statuses();$out=array();foreach(isset($map[$this->status])?$map[$this->status]:array() as $s) if($s!=='expired')$out[$s]=$all[$s];return $out; }
}
