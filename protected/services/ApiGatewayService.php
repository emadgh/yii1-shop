<?php
class ApiGatewayService
{
    public static function call($operation,$arguments,$requestKey=null)
    {
        $operations=ApiSchema::operations();if(!isset($operations[$operation]))throw new CHttpException(404,'Unknown operation');$definition=$operations[$operation];
        ApiTokenService::requireScope($definition['scope']);ApiSchema::validate($arguments,$definition['inputSchema']);
        if($definition['annotations']['readOnlyHint'])return self::dispatch($operation,$arguments);
        if(!is_string($requestKey)||!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.:-]{7,99}$/D',$requestKey)||strpos($requestKey,'rate-')===0)throw new CHttpException(400,'A unique Idempotency-Key (8-100 characters) is required for writes.');
        $db=Yii::app()->db;$tx=$db->beginTransaction();$token=ApiTokenService::$actor;$hash=hash('sha256',CJSON::encode(array($operation,$arguments)));$uploaded=null;$deletedMedia=null;
        try{
            // Serializes same-token requests and establishes an SQLite write lock before reads.
            $db->createCommand('UPDATE api_tokens SET last_used_at=last_used_at WHERE id=:id')->execute(array(':id'=>$token['id']));
            $credential=$db->createCommand('SELECT revoked_at,expires_at FROM api_tokens WHERE id=:id')->queryRow(true,array(':id'=>$token['id']));
            if(!$credential||$credential['revoked_at']||$credential['expires_at']<=date('Y-m-d H:i:s'))throw new CHttpException(401,'Token revoked');
            $previous=$db->createCommand('SELECT request_hash,response_json FROM api_requests WHERE token_id=:id AND request_key=:key')->queryRow(true,array(':id'=>$token['id'],':key'=>$requestKey));
            if($previous){if(!hash_equals($previous['request_hash'],$hash))throw new CHttpException(409,'Idempotency-Key already used for different arguments');$result=json_decode($previous['response_json'],true);$tx->commit();return $result;}
            $result=self::dispatch($operation,$arguments);
            if($operation==='media_upload')$uploaded=$result;
            if($operation==='media_delete'){$deletedMedia=$result['_deleted_media'];unset($result['_deleted_media']);}
            $db->createCommand()->insert('api_requests',array('token_id'=>$token['id'],'request_key'=>$requestKey,'request_hash'=>$hash,'response_json'=>CJSON::encode($result),'created_at'=>date('Y-m-d H:i:s')));
            Audit::record('ai_api',$requestKey,$operation,array('target_id'=>$arguments['id']??($result['record']['id']??null),'fields'=>array_keys($arguments['data']??array())));
            $tx->commit();if($deletedMedia)MediaService::removeFiles($deletedMedia['filename'],json_decode($deletedMedia['variants'],true)?:array());return $result;
        }catch(Exception $e){if($tx->active)$tx->rollback();ShopSettings::reset();if($uploaded)MediaService::removeFiles($uploaded['filename'],$uploaded['variants']);throw $e;}
    }
    private static function dispatch($operation,$args)
    {
        foreach(ApiSchema::entities() as $entity=>$definition){
            if($operation===$entity.'_list')return ApiContentQuery::listing($entity,$args);
            if($operation===$entity.'_get')return ApiContentQuery::get($entity,$args['id']);
            if(isset($definition['fields'])&&$operation===$entity.'_save')return ApiContentService::save($entity,$args);
            if(isset($definition['fields'])&&$operation===$entity.'_delete')return ApiContentService::delete($entity,$args);
        }
        if(in_array($operation,array('categories_move','menus_move'),true)){
            $service=new HierarchyTreeService($operation==='menus_move'?'menu_items':'categories',$operation==='menus_move'?($args['location']??'header'):null);
            return array('tree_revision'=>$service->move($args['id'],$args['target_id']??0,$args['position'],$args['tree_revision']));
        }
        if($operation==='media_upload'){
            $bytes=base64_decode($args['content_base64'],true);if($bytes===false||!strlen($bytes)||strlen($bytes)>10*1024*1024)throw new CHttpException(422,'Invalid base64 or file larger than 10 MB');
            $temp=tempnam(Yii::app()->runtimePath,'api_upload_');
            try{file_put_contents($temp,$bytes);return MediaService::describe(MediaService::ingest($temp,$args['filename'],$args['scope']??'general',$args['accept']??'*'));}finally{@unlink($temp);}
        }
        if($operation==='media_delete'){
            ApiTokenService::requireScope('content:delete');$current=ApiContentQuery::get('media',$args['id']);ApiContentService::assertRevision($args['revision'],$current['revision']);
            return array('deleted'=>true,'id'=>$args['id'],'_deleted_media'=>MediaDeleteService::delete($args['id'],false));
        }
        if($operation==='settings_get')return ApiContentQuery::settings();
        if($operation==='settings_save'){
            self::lockSettings();
            $current=ApiContentQuery::settings();ApiContentService::assertRevision($args['revision'],$current['revision']);
            foreach($args['data'] as $key=>$value){if($key==='contact_email'&&$value&&!filter_var($value,FILTER_VALIDATE_EMAIL))throw new CHttpException(422,'Invalid contact email');if($key==='site_name'&&!trim($value))throw new CHttpException(422,'Site name required');ShopSettings::set($key,(string)$value);}
            return ApiContentQuery::settings();
        }
        if($operation==='themes_get')return ApiContentQuery::themes($args['key']??null);
        if($operation==='themes_save'||$operation==='themes_activate'){
            self::lockSettings();
            $current=ApiContentQuery::themes($args['key']);ApiContentService::assertRevision($args['revision'],$current['revision']);
            if($operation==='themes_activate'){ApiTokenService::requireScope('content:publish');ThemeService::activate($args['key']);}
            else{$options=array_merge($current['options'],$args['options']??array());ThemeOptions::save($args['key'],$options,$args['blocks']??$current['blocks']);}
            return ApiContentQuery::themes($args['key']);
        }
        if($operation==='orders_status'||$operation==='orders_tracking')return ApiContentService::orderStatus($args);
        if($operation==='reports_get')return ApiContentQuery::reports($args['days']??7);
        if($operation==='cache_clear'){Yii::app()->siteCache->invalidateAll();return array('invalidated'=>true);}
        throw new CHttpException(404);
    }
    private static function lockSettings()
    {
        $db=Yii::app()->db;if($db->driverName==='mysql')$db->createCommand('SELECT setting_key FROM settings ORDER BY setting_key FOR UPDATE')->queryColumn();
        ShopSettings::reset();
    }
}
