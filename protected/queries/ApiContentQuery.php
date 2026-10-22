<?php
/** Bounded, whitelisted management reads; never exposes user or payment secrets. */
class ApiContentQuery
{
    public static function revision($value){return hash('sha256',CJSON::encode($value));}
    public static function definition($entity)
    {
        $definitions=ApiSchema::entities();if(!isset($definitions[$entity]))throw new CHttpException(404,'Unknown entity');return $definitions[$entity];
    }
    public static function listing($entity,$args)
    {
        $definition=self::definition($entity);$page=$args['page']??1;$size=$args['page_size']??20;$criteria=new CDbCriteria;
        if($entity==='media'){
            $command=Yii::app()->db->createCommand()->select('*')->from('media');
            $where=array('and');$params=array();
            if(!empty($args['q'])){$where[]='original_name LIKE :q';$params[':q']='%'.strtr($args['q'],array('%'=>'\\%','_'=>'\\_')).'%';}
            if(($args['scope']??'all')==='catalogs'){$where[]='upload_scope=:scope';$params[':scope']='catalogs';}
            if(($args['mime']??'*')!=='*'){$where[]=($args['mime']==='image/*'?'mime LIKE :mime':'mime=:mime');$params[':mime']=$args['mime']==='image/*'?'image/%':$args['mime'];}
            $condition=count($where)>1?$where:'1=1';$command->where($condition,$params);
            $count=(int)Yii::app()->db->createCommand()->select('COUNT(*)')->from('media')->where($condition,$params)->queryScalar();
            $rows=array_map(array('MediaService','describe'),$command->order('id DESC')->limit($size)->offset(($page-1)*$size)->queryAll());
        }else{
            $class=$definition['model'];$model=$class::model();
            if(!empty($args['q'])){
                $column=$entity==='categories'||$entity==='tags'?'name':($entity==='menus'?'label':($entity==='coupons'?'code':($entity==='orders'?'order_number':'title')));
                $criteria->addSearchCondition($column,$args['q']);
            }
            if(!empty($args['status'])&&in_array($entity,array('products','posts','orders'),true))$criteria->compare('status',$args['status']);
            if($entity==='menus')$criteria->compare('location',$args['location']??'header');
            $count=(int)$model->count($criteria);$criteria->order='id DESC';$criteria->limit=$size;$criteria->offset=($page-1)*$size;$rows=array();
            foreach($model->findAll($criteria) as $record){$row=$record->attributes;
                if($entity==='orders')$row=array_intersect_key($row,array_flip(array('id','order_number','status','subtotal','total','created_at','updated_at')));
                elseif($entity==='posts')unset($row['body']);elseif($entity==='products')unset($row['description']);
                $rows[]=$row;
            }
        }
        $out=array('items'=>$rows,'total'=>$count,'page'=>$page,'page_size'=>$size,'has_more'=>$page*$size<$count);
        if(in_array($entity,array('categories','menus'),true)){
            $service=new HierarchyTreeService($entity==='menus'?'menu_items':'categories',$entity==='menus'?($args['location']??'header'):null);
            $out['tree_revision']=$service->revision();$out['tree_location']=$entity==='menus'?($args['location']??'header'):null;
        }
        return $out;
    }
    public static function get($entity,$id)
    {
        $definition=self::definition($entity);
        if($entity==='media'){$row=MediaRepository::find($id);if(!$row)throw new CHttpException(404);$data=MediaService::describe($row);}
        else{
            $class=$definition['model'];$model=$class::model()->findByPk($id);if(!$model)throw new CHttpException(404,'Record not found');$data=$model->attributes;
            if($entity==='products'){
                $data['tags']=array_map(function($tag){return $tag->name;},$model->tags);
                $data['specifications']=array_map(function($row){return array('key'=>$row->name,'value'=>$row->value);},$model->specifications);
                $data['metadata']=array_map(function($row){return array('key'=>$row->meta_key,'value'=>$row->meta_value);},$model->metadata);
                $data['albums']=ProductAlbumService::editorRows($model);foreach($data['albums'] as &$album)$album['media_ids']=array_map('intval',array_filter(explode(',',$album['media_ids'])));unset($album);$data['album_revision']=ProductAlbumService::revision($model->id);
            }elseif($entity==='posts'){$data['tags']=array_map(function($tag){return $tag->name;},$model->tags);$ids=CmsMedia::ids($model->cover_image);$data['cover_media_id']=$ids?(int)$ids[0]:0;}
            elseif(in_array($entity,array('categories','collections'),true)){$ids=CmsMedia::ids($model->image);$data['image_id']=$ids?(int)$ids[0]:0;if($entity==='collections')$data['product_ids']=array_map('intval',$model->productIds());}
            elseif($entity==='orders'){
                foreach(array_keys($data) as $key)if(preg_match('/hash|secret|token/i',$key))unset($data[$key]);
                $data['items']=Yii::app()->db->createCommand('SELECT product_id,product_title,sku,quantity,price,line_total FROM order_items WHERE order_id=:id ORDER BY id')->queryAll(true,array(':id'=>$id));
                $data['allowed_statuses']=array_values(array_diff(OrderService::transitions()[$model->status]??array(),array('paid','expired')));
            }
        }
        $paths=array('products'=>'product','posts'=>'blog','categories'=>'category','collections'=>'collection');
        $url=isset($paths[$entity])&&isset($data['slug'])?'/'.$paths[$entity].'/'.$data['slug']:($entity==='catalogs'?'/catalogs':null);
        return array('record'=>$data,'revision'=>self::revision($data),'url'=>$url,'money_unit'=>'toman','date_format'=>'Y-m-d H:i:s Asia/Tehran');
    }
    public static function settings()
    {
        $values=array();$defaults=array('currency'=>'toman','commerce_enabled'=>'1','catalog_show_prices'=>'1','reservation_minutes'=>'20','analytics_enabled'=>'1','analytics_retention_days'=>'90','cache_backend'=>'db','cache_ttl'=>'600');
        foreach(ApiSchema::settings() as $key=>$schema){$value=ShopSettings::get($key,$defaults[$key]??'');$values[$key]=$schema['type']==='integer'?(int)$value:$value;}
        return array('settings'=>$values,'revision'=>self::revision($values));
    }
    public static function themes($key=null)
    {
        $key=$key?:ThemeService::active();if(!ThemeService::valid($key))throw new CHttpException(422,'Invalid theme');
        $data=array('key'=>$key,'active'=>ThemeService::active(),'options'=>ThemeOptions::get($key),'blocks'=>ThemeOptions::blocks($key));
        return array_merge($data,array('themes'=>ThemeService::catalog(),'revision'=>self::revision($data)));
    }
    public static function reports($days)
    {
        $db=Yii::app()->db;
        return array('visits'=>VisitService::summary($days),'orders'=>$db->createCommand('SELECT status,COUNT(*) count,SUM(total) total FROM orders GROUP BY status')->queryAll(),
            'low_stock'=>$db->createCommand("SELECT id,title,sku,stock FROM products WHERE stock<5 AND status='published' ORDER BY stock,id LIMIT 50")->queryAll(),'money_unit'=>'toman');
    }
}
