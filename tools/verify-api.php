<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__);$copy=$root.'/protected/runtime/api-'.bin2hex(random_bytes(6)).'.sqlite';$mysqlApi=getenv('SHOP_API_TEST_MYSQL')==='1';
if(!$mysqlApi){copy($root.'/protected/runtime/store.sqlite',$copy);putenv('SHOP_DSN=sqlite:'.$copy);}
require_once __DIR__.'/bootstrap.php';$apiChecks=0;
function apiCheck($ok,$label){global $apiChecks;if(!$ok)throw new Exception('FAILED '.$label);$apiChecks++;echo "PASS $label\n";}
function apiReject($fn,$status,$label){try{$fn();}catch(CHttpException $e){apiCheck($e->statusCode===$status,$label);return;}apiCheck(false,$label);}
$db=Yii::app()->db;$oldEnabled=ShopSettings::get('ai_api_enabled','0');$oldCommerce=ShopSettings::get('commerce_enabled','1');$issuedIds=array();$postId=null;$productId=null;$apiOrderId=null;$apiMediaId=null;
try{
 $owner=User::model()->findByAttributes(array('email'=>'demo@bazaarche.test'));if(!$owner)throw new Exception('Demo owner missing');
 ShopSettings::set('ai_api_enabled','1');$issued=ApiTokenService::issue($owner->id,'API verification',array_keys(ApiTokenService::scopes()));$issuedIds[]=$issued['id'];
 apiCheck($db->createCommand('SELECT token_hash FROM api_tokens WHERE id=:id')->queryScalar(array(':id'=>$issued['id']))===hash('sha256',$issued['token']),'only SHA256 token hash is stored');
 ApiTokenService::authenticate('Bearer '.$issued['token']);
 $key='api-create-'.bin2hex(random_bytes(8));$args=array('data'=>array('title'=>'Service API','slug'=>'api-service-'.bin2hex(random_bytes(4)),'body'=>'Original content','tags'=>array('service API')));
 $created=ApiGatewayService::call('posts_save',$args,$key);$postId=$created['record']['id'];
 apiCheck($created['record']['status']==='draft','new API post defaults to draft');
 apiCheck(ApiGatewayService::call('posts_save',$args,$key)['record']['id']===$postId,'same mutation returns stored response');
 apiReject(function()use($key){ApiGatewayService::call('posts_save',array('data'=>array('title'=>'different')),$key);},409,'same key cannot represent another intent');
 $current=ApiContentQuery::get('posts',$postId);$changed=ApiGatewayService::call('posts_save',array('id'=>(int)$postId,'revision'=>$current['revision'],'data'=>array('title'=>'Updated API')),bin2hex(random_bytes(10)));
 apiCheck($changed['record']['body']==='Original content'&&$changed['record']['tags']===array('service API'),'partial updates preserve body and tags');
 apiReject(function()use($postId,$current){ApiGatewayService::call('posts_save',array('id'=>(int)$postId,'revision'=>$current['revision'],'data'=>array('body'=>'stale')),bin2hex(random_bytes(10)));},409,'stale revisions rejected');
 $before=(int)Post::model()->count();$failedKey=bin2hex(random_bytes(10));
 try{ApiGatewayService::call('posts_save',array('data'=>array('title'=>'Rollback','tags'=>array(str_repeat('x',181)))),$failedKey);}catch(Exception $e){}
 apiCheck((int)Post::model()->count()===$before&&!(int)$db->createCommand('SELECT COUNT(*) FROM api_requests WHERE token_id=:token AND request_key=:key')->queryScalar(array(':token'=>$issued['id'],':key'=>$failedKey)),'failed dependent write rolls back record and idempotency entry');
 $product=ApiGatewayService::call('products_save',array('data'=>array('title'=>'API product','sku'=>'API-SERVICE-'.bin2hex(random_bytes(4)),'price'=>1000,'stock'=>4,'specifications'=>array(array('key'=>'Material','value'=>'Wood')))),bin2hex(random_bytes(10)));$productId=$product['record']['id'];
 apiCheck((int)$db->createCommand('SELECT SUM(quantity) FROM inventory_movements WHERE product_id=:id')->queryScalar(array(':id'=>$productId))===4,'nested product service commits stock ledger with API request');
 $before=ApiContentQuery::get('products',$productId);$invalidKey=bin2hex(random_bytes(10));
 apiReject(function()use($productId,$before,$invalidKey){ApiGatewayService::call('products_save',array('id'=>(int)$productId,'revision'=>$before['revision'],'data'=>array('price'=>-1)),$invalidKey);},422,'invalid money rejected before persistence');
 apiCheck((int)Product::model()->findByPk($productId)->price===1000,'rejected mutation leaves price unchanged');
 $freshProduct=ApiContentQuery::get('products',$productId);ApiGatewayService::call('products_save',array('id'=>(int)$productId,'revision'=>$freshProduct['revision'],'data'=>array('status'=>'published')),bin2hex(random_bytes(10)));
 ShopSettings::set('commerce_enabled','1');$number=OrderService::reserve(array($productId=>1),array('customer_name'=>'API order','customer_email'=>'api@example.test','customer_phone'=>'09123456789','shipping_address'=>'Tehran','notes'=>'API verification'));
 $apiOrderId=Order::model()->findByAttributes(array('order_number'=>$number))->id;$order=ApiContentQuery::get('orders',$apiOrderId);
 $tracked=ApiGatewayService::call('orders_tracking',array('id'=>(int)$apiOrderId,'revision'=>$order['revision'],'tracking_code'=>'SERVICE-TRACK'),bin2hex(random_bytes(10)));
 apiCheck($tracked['record']['tracking_code']==='SERVICE-TRACK','tracking code update uses real order field');
 $cancelled=ApiGatewayService::call('orders_status',array('id'=>(int)$apiOrderId,'revision'=>$tracked['revision'],'status'=>'cancelled'),bin2hex(random_bytes(10)));
 apiCheck($cancelled['record']['status']==='cancelled'&&(int)Product::model()->findByPk($productId)->stock===4,'API cancellation restores reserved stock atomically');
 $uploaded=ApiGatewayService::call('media_upload',array('filename'=>'api-service.txt','content_base64'=>base64_encode('Temporary media fixture')),bin2hex(random_bytes(10)));$apiMediaId=$uploaded['id'];$media=ApiContentQuery::get('media',$apiMediaId);
 $deleteKey=bin2hex(random_bytes(10));$deleteArgs=array('id'=>(int)$apiMediaId,'revision'=>$media['revision']);$deleted=ApiGatewayService::call('media_delete',$deleteArgs,$deleteKey);
 apiCheck($deleted['deleted']&&!is_file(Yii::app()->params['uploadsDir'].'/'.$uploaded['filename']),'media file is removed after outer transaction commit');
 apiCheck(ApiGatewayService::call('media_delete',$deleteArgs,$deleteKey)['deleted'],'media deletion retry replays without touching unrelated files');$apiMediaId=null;
 $limited=ApiTokenService::issue($owner->id,'API limited',array('content:read','content:write'));$issuedIds[]=$limited['id'];ApiTokenService::authenticate('Bearer '.$limited['token']);
 apiReject(function()use($postId,$changed){ApiGatewayService::call('posts_save',array('id'=>(int)$postId,'revision'=>$changed['revision'],'data'=>array('status'=>'published')),bin2hex(random_bytes(10)));},403,'draft writer cannot publish');
 apiReject(function(){ApiGatewayService::call('products_save',array('data'=>array('title'=>'denied')),bin2hex(random_bytes(10)));},403,'product writes have separate scope');
 apiReject(function(){ApiGatewayService::call('orders_list',array());},403,'customer data requires separate scope');
 $db->createCommand()->update('api_tokens',array('revoked_at'=>date('Y-m-d H:i:s')),'id=:id',array(':id'=>$limited['id']));apiReject(function()use($limited){ApiTokenService::authenticate('Bearer '.$limited['token']);},401,'revoked token cannot authenticate');
 $db->createCommand()->update('api_tokens',array('expires_at'=>'2000-01-01 00:00:00'),'id=:id',array(':id'=>$issued['id']));apiReject(function()use($issued){ApiTokenService::authenticate('Bearer '.$issued['token']);},401,'expired token cannot authenticate');
 apiCheck(!in_array('products:write',ApiTokenService::allowedScopes('content'),true)&&!in_array('settings:write',ApiTokenService::allowedScopes('admin'),true),'token grants cannot elevate content or ordinary admin roles');
 $rate=ApiTokenService::issue($owner->id,'API rate',array('content:read'));$issuedIds[]=$rate['id'];
 for($i=0;$i<120;$i++)$db->createCommand()->insert('api_requests',array('token_id'=>$rate['id'],'request_key'=>'rate-'.date('Y-m-d H:i').'-'.$i,'request_hash'=>str_repeat('0',64),'response_json'=>'{}','created_at'=>date('Y-m-d H:i:s')));
 apiReject(function()use($rate){ApiTokenService::authenticate('Bearer '.$rate['token']);},429,'per-token rate window is enforced');
 ShopSettings::set('ai_api_enabled','0');apiReject(function()use($rate){ApiTokenService::authenticate('Bearer '.$rate['token']);},503,'global API switch disables existing credentials');
 apiCheck((int)$db->createCommand("SELECT COUNT(*) FROM audit_log WHERE api_token_id=:id AND entity='ai_api'")->queryScalar(array(':id'=>$issued['id']))>=3,'API request audit stores token attribution');
 echo "All $apiChecks API service checks passed on ".$db->driverName.".\n";
}finally{
 ApiTokenService::$actor=null;
 if($apiOrderId){$record=Order::model()->findByPk($apiOrderId);if($record&&in_array($record->status,array('pending','confirmed'),true))OrderService::changeStatus($apiOrderId,'cancelled');}
 if($apiMediaId)MediaDeleteService::delete($apiMediaId);
 if($productId)ProductWriteService::delete(Product::model()->findByPk($productId));if($postId)Post::model()->findByPk($postId)->delete();
 foreach($issuedIds as $id){$db->createCommand()->delete('api_requests','token_id=:id',array(':id'=>$id));$db->createCommand()->delete('api_tokens','id=:id',array(':id'=>$id));}
 ShopSettings::set('ai_api_enabled',$oldEnabled);ShopSettings::set('commerce_enabled',$oldCommerce);if(!$mysqlApi){$db->active=false;@unlink($copy);}
}
