<?php
class CheckoutForm extends CFormModel
{
    public $customer_name,$customer_email,$customer_phone,$shipping_address,$notes,$postal_code,$province,$city,$shipping_method='standard';
    public function rules() { return array(
      array('customer_name,customer_email,customer_phone,shipping_address','required'),
      array('province,city','length','max'=>100),array('shipping_method','in','range'=>array('standard','pickup')),array('postal_code','match','pattern'=>'/^[0-9]{10}$/','allowEmpty'=>true),
      array('customer_name','length','max'=>150),array('customer_email','email'),
      array('customer_email','length','max'=>190),array('customer_phone','match','pattern'=>'/^09[0-9]{9}$/D','message'=>'شماره موبایل را به صورت ۰۹xxxxxxxxx وارد کنید.'),
      array('shipping_address','length','max'=>2000),array('notes','length','max'=>2000),
    ); }
    protected function beforeValidate(){$this->customer_phone=JalaliDate::digits(preg_replace('/[\s-]+/u','',(string)$this->customer_phone));$this->postal_code=JalaliDate::digits((string)$this->postal_code);if(strpos($this->customer_phone,'+98')===0)$this->customer_phone='0'.substr($this->customer_phone,3);return parent::beforeValidate();}
    public function attributeLabels() { return array(
     'customer_name'=>'نام و نام خانوادگی','customer_email'=>'ایمیل','customer_phone'=>'تلفن',
     'shipping_address'=>'آدرس تحویل','notes'=>'یادداشت سفارش',
    ); }
}
