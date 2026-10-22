<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__);$copy=$root.'/protected/runtime/verify-'.bin2hex(random_bytes(5)).'.sqlite';
if(!copy($root.'/protected/runtime/store.sqlite',$copy))throw new Exception('Cannot copy test database');
putenv('SHOP_DSN=sqlite:'.$copy);require __DIR__.'/bootstrap.php';$checks=0;
function check($ok,$message){global $checks;if(!$ok)throw new Exception('FAILED: '.$message);$checks++;echo "PASS $message\n";}
function rejects($fn,$message){try{$fn();}catch(Exception $e){check(true,$message);return;}check(false,$message);}
function customer(){return array('customer_name'=>'تست خودکار','customer_email'=>'test@example.test','customer_phone'=>'09123456789','shipping_address'=>'تهران، نشانی تست','notes'=>'VERIFY');}
try {
 $db=Yii::app()->db;OrderService::expire();
 $p=Product::model()->find(array('order'=>'id'));$id=$p->id;$before=(int)$p->stock;
 ShopSettings::set('reservation_minutes','17');
 $number=OrderService::reserve(array($id=>2),customer());$o=Order::model()->findByAttributes(array('order_number'=>$number));
 check((int)Product::model()->findByPk($id)->stock===$before-2,'reservation subtracts available inventory');
 check(abs(strtotime($o->reserved_until)-time()-17*60)<4,'reservation duration comes from settings');
 rejects(function()use($id){OrderService::reserve(array($id=>99),customer());},'insufficient stock rolls back');
 $db->createCommand()->update('orders',array('reserved_until'=>date('Y-m-d H:i:s',time()-5)),'id=:id',array(':id'=>$o->id));
 OrderService::expire();OrderService::expire();
 check((int)Product::model()->findByPk($id)->stock===$before,'expiration restores stock exactly once');
 rejects(function()use($o){OrderService::changeStatus($o->id,'paid');},'expired order cannot be paid');
 $number=OrderService::reserve(array($id=>1),customer(),'WELCOME10');$o=Order::model()->findByAttributes(array('order_number'=>$number));
 check((int)$o->discount_total>0 && $o->total==$o->subtotal-$o->discount_total,'coupon is included in immutable order total');
 $used=(int)Coupon::model()->findByAttributes(array('code'=>'WELCOME10'))->used_count;OrderService::changeStatus($o->id,'cancelled');OrderService::changeStatus($o->id,'cancelled');
 check((int)Coupon::model()->findByAttributes(array('code'=>'WELCOME10'))->used_count===$used-1,'unpaid cancellation frees coupon capacity once');
 $number=OrderService::reserve(array($id=>1),customer());$o=Order::model()->findByAttributes(array('order_number'=>$number));
 OrderService::changeStatus($o->id,'paid');OrderService::changeStatus($o->id,'paid');OrderService::changeStatus($o->id,'shipped');OrderService::changeStatus($o->id,'completed');OrderService::changeStatus($o->id,'returned');OrderService::changeStatus($o->id,'returned');
 check((int)Product::model()->findByPk($id)->stock===$before,'returned goods restored once');
 check((int)$db->createCommand('SELECT SUM(amount) FROM financial_entries WHERE order_id=:id')->queryScalar(array(':id'=>$o->id))===0,'receipt and refund net to zero');
 check((int)$db->createCommand('SELECT COUNT(*) FROM financial_entries WHERE order_id=:id')->queryScalar(array(':id'=>$o->id))===2,'no duplicate receipt or refund');
 rejects(function()use($o){OrderService::changeStatus($o->id,'paid');},'returned order cannot reopen');
 $row=Coupon::model()->findByAttributes(array('code'=>'WELCOME10'))->attributes;
 rejects(function()use($row){Coupon::discount($row,1);},'coupon minimum purchase enforced');
 $row['used_count']=$row['usage_limit'];rejects(function()use($row){Coupon::discount($row,1000000);},'coupon usage limit enforced');
 $row['usage_limit']=0;$row['expires_at']=date('Y-m-d H:i:s',time()-1);rejects(function()use($row){Coupon::discount($row,1000000);},'coupon expiry enforced');
 check(Product::effectivePrice(array('price'=>123456,'discount_type'=>'percent','discount_value'=>15))===104938,'percentage discount rounds to integer currency');
 ShopSettings::set('currency','rial');check(Money::format(123456)==='1,234,560 ریال' && Money::parse('۱,۲۳۴,۵۶۰')===123456,'rial formatting and Persian number roundtrip');ShopSettings::set('currency','toman');
 check(Money::format(123456)==='123,456 تومان','toman formatting preserves value');
 $html=Content::markdown('## عنوان' . "\n\n" . '**پررنگ** <script>alert(1)</script><img src="x" onerror="alert(1)">');
 check(strpos($html,'<strong>')!==false && strpos($html,'<script')===false && strpos($html,'onerror')===false,'Markdown formatting and HTML sanitization');
 $m=$db->createCommand("SELECT * FROM media WHERE mime='image/webp' LIMIT 1")->queryRow();$v=json_decode($m['variants'],true);check(count($v)===3,'media has three responsive versions');
 foreach($v as $key=>$variant){$info=getimagesize($root.'/public/uploads/'.$variant['path']);check($info['mime']==='image/webp' && $info[0]===$variant['width'],'valid '.$key.' WebP image');}
 $bad=$root.'/protected/runtime/verify-bad.txt';file_put_contents($bad,'<?php echo 1;');rejects(function()use($bad){MediaService::ingest($bad,'fake.jpg');},'executable content rejected');unlink($bad);
 $stock=$db->createCommand('SELECT p.id,p.stock,COALESCE(SUM(m.quantity),0) ledger FROM products p LEFT JOIN inventory_movements m ON m.product_id=p.id GROUP BY p.id,p.stock')->queryAll();foreach($stock as $r)check((int)$r['stock']===(int)$r['ledger'],'inventory ledger reconciles product '.$r['id']);
 echo "All $checks checks passed on isolated database.\n";
}finally{Yii::app()->db->active=false;@unlink($copy);}
