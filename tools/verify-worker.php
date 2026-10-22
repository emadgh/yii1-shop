<?php
require __DIR__.'/bootstrap.php';
$state=dirname(__DIR__).'/protected/runtime/worker-verify.json';
if(isset($argv[1]) && $argv[1]==='create') {
 $p=Product::model()->find(array('order'=>'id'));$before=(int)$p->stock;
 $number=OrderService::reserve(array($p->id=>1),array('customer_name'=>'تست پردازشگر رزرو','customer_email'=>'worker@example.test','customer_phone'=>'09123456789','shipping_address'=>'نشانی تست','notes'=>'WORKER-VERIFY'));
 $o=Order::model()->findByAttributes(array('order_number'=>$number));
 Yii::app()->db->createCommand()->update('orders',array('reserved_until'=>date('Y-m-d H:i:s',time()-1)),'id=:id',array(':id'=>$o->id));
 file_put_contents($state,json_encode(array('order'=>$o->id,'product'=>$p->id,'before'=>$before)));echo "Created expired reservation for passive-worker check\n";
}else{
 $data=json_decode(file_get_contents($state),true);$o=Order::model()->findByPk($data['order']);$p=Product::model()->findByPk($data['product']);
 if($o->status!=='expired' || (int)$p->stock!==$data['before'])throw new Exception('Worker did not restore expired stock');
 echo "PASS background worker releases expired reservation without web requests\n";unlink($state);
}
