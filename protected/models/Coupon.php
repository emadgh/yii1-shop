<?php
class Coupon extends CActiveRecord {
 public static function model($className=__CLASS__) { return parent::model($className); }
 public function tableName() { return 'coupons'; }
 public function rules() { return array(
   array('code,kind,amount','required'),array('code','match','pattern'=>'/^[A-Z0-9_-]{3,40}$/D'),array('code','unique'),
   array('kind','in','range'=>array('percent','fixed')),array('amount,minimum_total,maximum_discount,usage_limit','numerical','integerOnly'=>true,'min'=>0,'max'=>1000000000000),
   array('is_active','boolean'),array('starts_at,expires_at','safe'),
 ); }
 public function validateRules() {
   if(!$this->validate()) throw new Exception('اطلاعات کد تخفیف معتبر نیست.');
   if($this->amount<=0 || ($this->kind==='percent' && $this->amount>100)) throw new Exception('تخفیف درصدی باید بین ۱ تا ۱۰۰ باشد.');
   foreach(array('starts_at','expires_at') as $key) if($this->$key) { $date=DateTime::createFromFormat('!Y-m-d H:i:s',$this->$key);if(!$date || $date->format('Y-m-d H:i:s')!==$this->$key)throw new Exception('تاریخ تخفیف معتبر نیست.'); }
   if($this->starts_at && $this->expires_at && $this->starts_at>=$this->expires_at) throw new Exception('پایان تخفیف باید پس از شروع آن باشد.');
 }
 public static function discount($row,$subtotal) {
   $now=date('Y-m-d H:i:s');
   if(!$row || !$row['is_active'] || ($row['starts_at'] && $row['starts_at']>$now) || ($row['expires_at'] && $row['expires_at']<=$now)) throw new Exception('کد تخفیف معتبر یا فعال نیست.');
   if($subtotal<(int)$row['minimum_total']) throw new Exception('حداقل خرید برای این کد '.Money::format($row['minimum_total']).' است.');
   if($row['usage_limit']>0 && $row['used_count']>=$row['usage_limit']) throw new Exception('ظرفیت استفاده از کد تخفیف تمام شده است.');
   $amount=$row['kind']==='percent'?intdiv($subtotal*(int)$row['amount'],100):(int)$row['amount'];
   if($row['maximum_discount']>0)$amount=min($amount,(int)$row['maximum_discount']);
   return min($subtotal,$amount);
 }
}
