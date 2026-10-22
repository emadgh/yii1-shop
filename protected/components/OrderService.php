<?php
class OrderService {
 public static function transitions() { return array('pending'=>array('confirmed','paid','cancelled','expired'),'confirmed'=>array('paid','cancelled','expired'),'paid'=>array('shipped','returned'),'shipped'=>array('completed','returned'),'completed'=>array('returned'),'cancelled'=>array(),'expired'=>array(),'returned'=>array()); }
 public static function movement($product,$order,$quantity,$kind,$note='') {
   Yii::app()->db->createCommand()->insert('inventory_movements',array('product_id'=>$product,'order_id'=>$order,'quantity'=>$quantity,'kind'=>$kind,'note'=>$note,'created_at'=>date('Y-m-d H:i:s')));
 }
 public static function reserve($items,$customer,$couponCode='') {
   Commerce::requireEnabled();
   self::expire();
   if(!$items) throw new Exception('سبد خرید خالی است.');
   $db=Yii::app()->db; $tx=$db->beginTransaction();
   try {
     $now=date('Y-m-d H:i:s'); $number='ORD-'.strtoupper(bin2hex(random_bytes(6)));
     $expiry=date('Y-m-d H:i:s',time()+60*(int)ShopSettings::get('reservation_minutes',20));
     $db->createCommand()->insert('orders',array('order_number'=>$number,'customer_name'=>$customer['customer_name'],'customer_email'=>$customer['customer_email'],'customer_phone'=>$customer['customer_phone'],'shipping_address'=>$customer['shipping_address'],'notes'=>isset($customer['notes'])?$customer['notes']:'','status'=>'pending','subtotal'=>0,'discount_total'=>0,'total'=>0,'stock_held'=>1,'reserved_until'=>$expiry,'created_at'=>$now,'updated_at'=>$now));
     $orderId=$db->getLastInsertID(); $subtotal=0;
     ksort($items,SORT_NUMERIC);
     foreach($items as $id=>$qty) {
       $candidate=Product::model()->findByPk($id);if($candidate&&!$candidate->parent_product_id&&$candidate->variants())throw new Exception('ابتدا تنوع محصول را انتخاب کنید.');$qty=(int)$qty; if($qty<1 || $qty>99) throw new Exception('تعداد کالا باید بین ۱ تا ۹۹ باشد.');
       $changed=$db->createCommand("UPDATE products SET stock=stock-:q WHERE id=:id AND stock>=:q2 AND status='published'")->execute(array(':q'=>$qty,':q2'=>$qty,':id'=>(int)$id));
       if($changed!==1) throw new Exception('موجودی یکی از کالاها کافی نیست؛ سبد خرید را بررسی کنید.');
       $p=$db->createCommand('SELECT * FROM products WHERE id=:id')->queryRow(true,array(':id'=>(int)$id));
       $price=Product::effectivePrice($p); $line=$price*$qty; $subtotal+=$line;
       $db->createCommand()->insert('order_items',array('order_id'=>$orderId,'product_id'=>$id,'product_title'=>$p['title'],'sku'=>$p['sku'],'price'=>$price,'quantity'=>$qty,'line_total'=>$line));
       self::movement($id,$orderId,-$qty,'reserve');
     }
     $shippingMethod=$customer['shipping_method']??'standard';$shipping=Shipping::quote($shippingMethod,$subtotal);
     $discount=0; $couponCode=strtoupper(trim($couponCode));
     if($couponCode!=='') {
       $sql='SELECT * FROM coupons WHERE code=:code'.($db->driverName==='mysql'?' FOR UPDATE':'');
       $coupon=$db->createCommand($sql)->queryRow(true,array(':code'=>$couponCode));
       $discount=Coupon::discount($coupon,$subtotal);
       $changed=$db->createCommand('UPDATE coupons SET used_count=used_count+1 WHERE id=:id AND (usage_limit=0 OR used_count<usage_limit)')->execute(array(':id'=>$coupon['id']));
       if($changed!==1) throw new Exception('ظرفیت کد تخفیف تکمیل شده است.');
     }
     $db->createCommand()->update('orders',array('subtotal'=>$subtotal,'discount_total'=>$discount,'total'=>$subtotal-$discount+$shipping,'shipping_total'=>$shipping,'shipping_method'=>$shippingMethod,'province'=>$customer['province']??'','city'=>$customer['city']??'','postal_code'=>$customer['postal_code']??'','coupon_code'=>$couponCode),'id=:id',array(':id'=>$orderId));
     self::event($orderId,'','pending'); $tx->commit(); return $number;
   } catch(Exception $e) { if($tx->active)$tx->rollback(); throw $e; }
 }
 private static function event($id,$old,$new) { Notifications::order($id,$new); Yii::app()->db->createCommand()->insert('order_events',array('order_id'=>$id,'from_status'=>$old,'to_status'=>$new,'created_at'=>date('Y-m-d H:i:s'))); }
 public static function changeStatus($id,$new) {
   $db=Yii::app()->db; $owns=!$db->getCurrentTransaction();$tx=$db->getCurrentTransaction()?:$db->beginTransaction();
   try {
     // A conditional write both locks and serializes updates on SQLite and InnoDB.
     $sql='SELECT * FROM orders WHERE id=:id'.($db->driverName==='mysql'?' FOR UPDATE':'');
     $row=$db->createCommand($sql)->queryRow(true,array(':id'=>$id));
     if(!$row) throw new Exception('سفارش پیدا نشد.');
     $old=$row['status']; $map=self::transitions();
     if($old===$new) { if($owns)$tx->commit();return; }
     if(!isset($map[$old]) || !in_array($new,$map[$old],true)) throw new Exception('این تغییر وضعیت مجاز نیست.');
     if(in_array($old,array('pending','confirmed'),true) && $new!=='expired' && $row['reserved_until'] && $row['reserved_until']<=date('Y-m-d H:i:s')) throw new Exception('مهلت رزرو تمام شده است.');
     if($new==='expired' && (!$row['reserved_until'] || $row['reserved_until']>date('Y-m-d H:i:s'))) throw new Exception('رزرو هنوز منقضی نشده است.');
     $release=in_array($new,array('cancelled','expired','returned'),true);
     $data=array('status'=>$new,'updated_at'=>date('Y-m-d H:i:s'));
     if($new==='paid') { $data['paid_at']=date('Y-m-d H:i:s');$data['reserved_until']=null; }
     if($release) { $data['stock_held']=0;$data['reserved_until']=null; }
     $changed=$db->createCommand()->update('orders',$data,'id=:id AND status=:old',array(':id'=>$id,':old'=>$old));
     if($changed!==1) throw new Exception('وضعیت سفارش هم‌زمان تغییر کرده است؛ صفحه را تازه کنید.');
     if($release && $row['stock_held']) {
       $items=$db->createCommand('SELECT product_id,quantity FROM order_items WHERE order_id=:id ORDER BY product_id')->queryAll(true,array(':id'=>$id));
       foreach($items as $item) if($item['product_id']!==null) {
         $db->createCommand('UPDATE products SET stock=stock+:q WHERE id=:id')->execute(array(':q'=>$item['quantity'],':id'=>$item['product_id']));
         self::movement($item['product_id'],$id,(int)$item['quantity'],$new);
       }
       if(!$row['paid_at'] && $row['coupon_code']) $db->createCommand('UPDATE coupons SET used_count=used_count-1 WHERE code=:c AND used_count>0')->execute(array(':c'=>$row['coupon_code']));
     }
     if($new==='paid' || ($new==='returned' && $row['paid_at'])) $db->createCommand()->insert('financial_entries',array('order_id'=>$id,'kind'=>$new==='paid'?'receipt':'refund','amount'=>$new==='paid'?$row['total']:-$row['total'],'created_at'=>date('Y-m-d H:i:s')));
     Audit::record('orders',$id,'status',array('from'=>$old,'to'=>$new));self::event($id,$old,$new); if($owns)$tx->commit();
   } catch(Exception $e) { if($owns&&$tx->active)$tx->rollback();throw $e; }
 }
 public static function expire() {
   $ids=Yii::app()->db->createCommand("SELECT id FROM orders WHERE status IN ('pending','confirmed') AND reserved_until<=:now ORDER BY id LIMIT 200")->queryColumn(array(':now'=>date('Y-m-d H:i:s')));
   foreach($ids as $id) { try { self::changeStatus($id,'expired'); } catch(Exception $e) { Yii::log($e->getMessage(),CLogger::LEVEL_WARNING,'reservations'); } }
   return count($ids);
 }
}
