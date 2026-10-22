<?php
class CheckoutForm extends CFormModel
{
    public $customer_name,$customer_email,$customer_phone,$shipping_address,$notes;
    public function rules() { return array(
      array('customer_name,customer_email,customer_phone,shipping_address','required'),
      array('customer_name','length','max'=>150),array('customer_email','email'),
      array('customer_email','length','max'=>190),array('customer_phone','length','max'=>50),
      array('shipping_address','length','max'=>2000),array('notes','length','max'=>2000),
    ); }
    public function attributeLabels() { return array(
     'customer_name'=>'نام و نام خانوادگی','customer_email'=>'ایمیل','customer_phone'=>'تلفن',
     'shipping_address'=>'آدرس تحویل','notes'=>'یادداشت سفارش',
    ); }
}
