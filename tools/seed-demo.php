<?php
require_once __DIR__.'/bootstrap.php';
$db=Yii::app()->db;
foreach($db->createCommand('SELECT id,stock FROM products')->queryAll() as $p) {
 if(!$db->createCommand('SELECT COUNT(*) FROM inventory_movements WHERE product_id=:id')->queryScalar(array(':id'=>$p['id'])))OrderService::movement($p['id'],null,(int)$p['stock'],'opening','موجودی اولیه کاتالوگ نمونه');
}
$media=array();
foreach(glob(dirname(__DIR__).'/protected/runtime/placeholders/demo-*.jpg') as $path) {
 $name=basename($path);$existing=$db->createCommand('SELECT * FROM media WHERE original_name=:n')->queryRow(true,array(':n'=>$name));$media[]=$existing?:MediaService::ingest($path,$name);
}
$products=Product::model()->findAll(array('order'=>'id','limit'=>6));
foreach($products as $i=>$p) {
 // Repair literal newline escapes in the original packaged demo specifications.
 foreach($p->specifications as $spec) if(strpos($spec->value,'\\n')!==false) {
   $parts=explode('\\n',$spec->value);$first=array_shift($parts);$db->createCommand()->update('product_attributes',array('value'=>$first),'id=:id',array(':id'=>$spec->id));
   foreach($parts as $part) { $pair=explode('=',$part,2);if(count($pair)===2 && !$db->createCommand('SELECT COUNT(*) FROM product_attributes WHERE product_id=:id AND name=:name')->queryScalar(array(':id'=>$p->id,':name'=>$pair[0])))$db->createCommand()->insert('product_attributes',array('product_id'=>$p->id,'name'=>$pair[0],'value'=>$pair[1],'sort_order'=>1)); }
 }
 if(strpos($p->description,'\\n')!==false)$db->createCommand()->update('products',array('description'=>str_replace('\\n',"\n",$p->description)),'id=:id',array(':id'=>$p->id));
 if($media && !$p->images) {
   foreach(array($media[$i%count($media)],$media[($i+1)%count($media)]) as $sort=>$m)$db->createCommand()->insert('product_images',array('product_id'=>$p->id,'filename'=>$m['filename'],'alt_text'=>$p->title,'sort_order'=>$sort));
 }
 if(!$db->createCommand('SELECT COUNT(*) FROM product_meta WHERE product_id=:id')->queryScalar(array(':id'=>$p->id)))foreach(array('brand'=>'بازارچه استودیو','origin'=>'ایران','collection'=>'زندگی روزمره') as $key=>$value)$db->createCommand()->insert('product_meta',array('product_id'=>$p->id,'meta_key'=>$key,'meta_value'=>$value));
 if(!$p->tags)TagService::sync('product',$p->id,'خانه، طراحی ساده، منتخب');
 if($i<2 && $p->discount_type==='none')$db->createCommand()->update('products',array('discount_type'=>'percent','discount_value'=>$i?10:15),'id=:id',array(':id'=>$p->id));
}
foreach(Post::model()->findAll(array('order'=>'id')) as $i=>$post)if(!$post->cover_image && $media){$post->cover_image=$media[($i+6)%count($media)]['filename'];$post->save(false);TagService::sync('post',$post->id,'راهنمای خرید، سبک زندگی');}
if(!Coupon::model()->findByAttributes(array('code'=>'WELCOME10'))) {
 $coupon=new Coupon;$coupon->code='WELCOME10';$coupon->kind='percent';$coupon->amount=10;$coupon->minimum_total=500000;$coupon->maximum_discount=200000;$coupon->usage_limit=100;$coupon->is_active=1;$coupon->used_count=0;$coupon->save(false);
}
$names=array('نگار احمدی','سامان کریمی','مریم رضایی','آرمان صادقی','پریسا محمدی','نیما شریفی','الهام مرادی','کیان نوری');
foreach(array('pending','confirmed','paid','shipped','completed','returned','cancelled','expired') as $i=>$status) {
 $marker='DEMO-SCENARIO-'.$status;
 $existing=$db->createCommand('SELECT id,status FROM orders WHERE notes=:n')->queryRow(true,array(':n'=>$marker));
 if($existing) {
   if(in_array($status,array('pending','confirmed'),true) && $existing['status']==='expired')$db->createCommand()->update('orders',array('notes'=>$marker.'-history-'.$existing['id']),'id=:id',array(':id'=>$existing['id']));
   else continue;
 }
 $p=$products[$i%max(1,count($products))]??null;if(!$p)break;
 $customer=array('customer_name'=>$names[$i],'customer_email'=>'demo'.$i.'@example.test','customer_phone'=>'0912000000'.$i,'shipping_address'=>'تهران، خیابان نمونه، پلاک '.($i+12),'notes'=>$marker);
 $number=OrderService::reserve(array($p->id=>1),$customer,$i===2?'WELCOME10':'');$order=Order::model()->findByAttributes(array('order_number'=>$number));
 if(in_array($status,array('confirmed','paid','shipped','completed','returned'),true))OrderService::changeStatus($order->id,'confirmed');
 if(in_array($status,array('paid','shipped','completed','returned'),true))OrderService::changeStatus($order->id,'paid');
 if(in_array($status,array('shipped','completed','returned'),true))OrderService::changeStatus($order->id,'shipped');
 if($status==='completed')OrderService::changeStatus($order->id,'completed');
 if($status==='returned')OrderService::changeStatus($order->id,'returned');
 if($status==='cancelled')OrderService::changeStatus($order->id,'cancelled');
 if($status==='expired'){$db->createCommand()->update('orders',array('reserved_until'=>date('Y-m-d H:i:s',time()-60)),'id=:id',array(':id'=>$order->id));OrderService::expire();}
 echo "Seeded $status: $number\n";
}
echo count($media)." media assets; demo order scenarios ready. Coupon: WELCOME10\n";

require __DIR__.'/seed-cms.php';
require __DIR__.'/upgrade-albums.php';
require __DIR__.'/upgrade-catalogs-cache.php';
