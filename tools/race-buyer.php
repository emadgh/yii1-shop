<?php
require __DIR__.'/bootstrap.php';
try {
 $db=Yii::app()->db;
 if($argv[1]==='setup') {
   OrderService::expire();
   $db->createCommand("UPDATE orders SET reserved_until=:future WHERE status IN ('pending','confirmed')")->execute(array(':future'=>date('Y-m-d H:i:s',time()+86400)));
   $ids=$db->createCommand('SELECT id FROM products ORDER BY id LIMIT 2')->queryColumn();
   foreach($ids as $id)$db->createCommand()->update('products',array('stock'=>10),'id=:id',array(':id'=>$id));
   $db->createCommand()->insert('coupons',array('code'=>'RACEONLY','kind'=>'fixed','amount'=>1,'minimum_total'=>0,'maximum_discount'=>0,'usage_limit'=>1,'used_count'=>0,'is_active'=>1));
   $db->createCommand()->update('products',array('stock'=>1),'id=:id',array(':id'=>$ids[0]));
   echo json_encode(array('ids'=>$ids,'orders'=>(int)$db->createCommand('SELECT COUNT(*) FROM orders')->queryScalar()));
 } elseif($argv[1]==='inspect') {
   echo json_encode(array('stock'=>$db->createCommand('SELECT id,stock FROM products ORDER BY id LIMIT 2')->queryAll(),'orders'=>(int)$db->createCommand('SELECT COUNT(*) FROM orders')->queryScalar(),'coupon'=>(int)$db->createCommand("SELECT used_count FROM coupons WHERE code='RACEONLY'")->queryScalar()));
 } else {
   $number=OrderService::reserve(array((int)$argv[1]=>1),array('customer_name'=>'Concurrent test','customer_email'=>'race@example.test','customer_phone'=>'09123456789','shipping_address'=>'Test','notes'=>'RACE'),isset($argv[2])?$argv[2]:'');
   echo json_encode(array('ok'=>true,'number'=>$number));
 }
}catch(Exception $e){echo json_encode(array('ok'=>false,'error'=>$e->getMessage()));}
