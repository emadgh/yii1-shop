<?php
/** Uses existing business services; API-specific orchestration contains no HTTP state. */
class ApiContentService
{
    public static function assertRevision($expected,$actual)
    {
        if(!is_string($expected)||!hash_equals($actual,$expected))throw new CHttpException(409,'Revision changed; read the current record and reapply the intended changes.');
    }
    private static function lockedModel($entity,$id)
    {
        $def=ApiContentQuery::definition($entity);$class=$def['model'];$db=Yii::app()->db;$table=$class::model()->tableName();
        if($db->driverName==='mysql')$db->createCommand('SELECT id FROM '.$table.(in_array($entity,array('categories','menus'),true)?' ORDER BY id':' WHERE id=:id').' FOR UPDATE')->queryAll(true,in_array($entity,array('categories','menus'),true)?array():array(':id'=>$id));
        $model=$class::model()->findByPk($id);if(!$model)throw new CHttpException(404,'Record not found');return $model;
    }
    public static function save($entity,$args)
    {
        $def=ApiContentQuery::definition($entity);$class=$def['model'];$isNew=empty($args['id']);$model=$isNew?new $class:self::lockedModel($entity,$args['id']);
        if($isNew)foreach($model->tableSchema->columns as $key=>$column)if(!$column->isPrimaryKey&&!$column->autoIncrement&&!$column->allowNull&&$column->type==='string'&&$model->$key===null)$model->$key='';
        $before=$isNew?array():ApiContentQuery::get($entity,$model->id)['record'];
        if(!$isNew)self::assertRevision($args['revision']??null,ApiContentQuery::revision($before));
        $data=$args['data'];
        if(in_array($entity,array('products','posts'),true)){
            $status=$data['status']??($isNew?'draft':$model->status);
            if($status==='published'||(!$isNew&&$model->status==='published'))ApiTokenService::requireScope('content:publish');
            $data['status']=$status;
        }
        if(in_array($entity,array('catalogs','collections','menus'),true)){
            $active=$data['is_active']??($isNew?0:(int)$model->is_active);
            if($active||(!$isNew&&$model->is_active))ApiTokenService::requireScope('content:publish');$data['is_active']=$active;
        }
        if($entity==='products'){
            $attributes=array_merge($model->attributes,$data);unset($attributes['tags'],$attributes['specifications'],$attributes['metadata'],$attributes['albums']);
            foreach(array('price','compare_price') as $key)$attributes[$key]=Money::input($attributes[$key]??0);
            if(($attributes['discount_type']??'none')==='fixed')$attributes['discount_value']=Money::input($attributes['discount_value']??0);
            foreach(array('category_id','parent_product_id') as $key)if(empty($attributes[$key]))$attributes[$key]='';
            $input=new ProductEditForm;$post=array('Product'=>$attributes,'stock_snapshot'=>$isNew?0:$model->stock,'tags'=>implode(',',$data['tags']??($before['tags']??array())),
                'spec_rows'=>$data['specifications']??($before['specifications']??array()),'meta_rows'=>$data['metadata']??($before['metadata']??array()));
            if(array_key_exists('albums',$data)){$albums=$data['albums'];foreach($albums as &$album){$album['media_ids']=implode(',',$album['media_ids']);}unset($album);$post+=array('albums_present'=>'1','albums'=>$albums,'album_revision'=>$before['album_revision']??'');}
            $input->load($post);if(!ProductWriteService::save($model,$input))throw new CHttpException(422,CJSON::encode($model->errors));
        }elseif($entity==='posts'){
            $tags=$data['tags']??($before['tags']??array());$cover=$data['cover_media_id']??($before['cover_media_id']??0);unset($data['tags'],$data['cover_media_id']);
            PostWriteService::save($model,$data,implode(',',$tags),$cover?(string)$cover:'');
        }elseif($entity==='catalogs'){
            $image=$data['image_id']??($before['image_id']??0);$pdf=$data['pdf_id']??($before['pdf_id']??0);unset($data['image_id'],$data['pdf_id']);
            CompanyCatalogService::save($model,$data,$image?(string)$image:'',$pdf?(string)$pdf:'');
        }elseif($entity==='categories'||$entity==='menus'){
            if($entity==='categories'){if(array_key_exists('image_id',$data)){CmsMedia::bind($model,$data['image_id']?(string)$data['image_id']:'');unset($data['image_id']);}}
            elseif(!$isNew&&isset($data['location'])&&$data['location']!==$model->location)throw new CHttpException(422,'Move menus within their existing location.');
            $model->attributes=$data;$model->parent_id=$model->parent_id?:null;
            if(!Hierarchy::save($model))throw new CHttpException(422,CJSON::encode($model->errors));
        }elseif($entity==='collections'){
            $ids=$data['product_ids']??($before['product_ids']??array());unset($data['product_ids']);
            if(array_key_exists('image_id',$data)){CmsMedia::bind($model,$data['image_id']?(string)$data['image_id']:'');unset($data['image_id']);}
            $model->attributes=$data;if(!$model->save())throw new CHttpException(422,CJSON::encode($model->errors));
            $db=Yii::app()->db;$db->createCommand()->delete('collection_products','collection_id=:id',array(':id'=>$model->id));
            foreach(array_values(array_unique($ids)) as $position=>$id){if(!Product::model()->exists('id=:id',array(':id'=>$id)))throw new CHttpException(422,'Product not found');$db->createCommand()->insert('collection_products',array('collection_id'=>$model->id,'product_id'=>$id,'position'=>$position));}
        }elseif($entity==='coupons'){
            if($isNew){$model->used_count=0;$model->is_active=0;}
            foreach(array('starts_at','expires_at') as $key)if(isset($data[$key])&&$data[$key]==='')$data[$key]=null;
            $model->attributes=$data;$model->validateRules();$model->save(false);
        }else{$model->attributes=$data;if(!$model->save())throw new CHttpException(422,CJSON::encode($model->errors));}
        return ApiContentQuery::get($entity,$model->id);
    }
    public static function delete($entity,$args)
    {
        ApiTokenService::requireScope('content:delete');$model=self::lockedModel($entity,$args['id']);$before=ApiContentQuery::get($entity,$model->id);
        self::assertRevision($args['revision'],$before['revision']);$db=Yii::app()->db;
        if($entity==='products')ProductWriteService::delete($model);
        else{
            if($entity==='categories'&&(Category::model()->exists('parent_id=:id',array(':id'=>$model->id))||Product::model()->exists('category_id=:id',array(':id'=>$model->id))))throw new CHttpException(422,'Category has children or products');
            if($entity==='menus'&&MenuItem::model()->exists('parent_id=:id',array(':id'=>$model->id)))throw new CHttpException(422,'Menu has children');
            if($entity==='collections'){$db->createCommand()->delete('collection_products','collection_id=:id',array(':id'=>$model->id));$db->createCommand()->update('theme_blocks',array('collection_id'=>null),'collection_id=:id',array(':id'=>$model->id));}
            if($entity==='tags')foreach(array('post_tag','product_tag') as $table)$db->createCommand()->delete($table,'tag_id=:id',array(':id'=>$model->id));
            $model->delete();
        }
        return array('deleted'=>true,'id'=>$args['id']);
    }
    public static function orderStatus($args)
    {
        $model=self::lockedModel('orders',$args['id']);$before=ApiContentQuery::get('orders',$model->id);self::assertRevision($args['revision'],$before['revision']);
        if(isset($args['status']))OrderService::changeStatus($model->id,$args['status']);
        if(isset($args['tracking_code']))Yii::app()->db->createCommand()->update('orders',array('tracking_code'=>trim($args['tracking_code']),'updated_at'=>date('Y-m-d H:i:s')),'id=:id',array(':id'=>$model->id));
        return ApiContentQuery::get('orders',$model->id);
    }
}
