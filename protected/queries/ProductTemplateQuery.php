<?php
class ProductTemplateQuery
{
    public static function options(){return CHtml::listData(ProductTemplate::model()->findAll(array('select'=>'id,name','order'=>'name,id')),'id','name');}
    public static function find($id){if(!is_scalar($id)||!ctype_digit((string)$id)||(int)$id<1)throw new CHttpException(422,'شناسه قالب معتبر نیست.');$row=ProductTemplate::model()->findByPk((int)$id);if(!$row)throw new CHttpException(404,'قالب محصول پیدا نشد.');return $row;}
    public static function snapshot($product)
    {
        $fields=$specs=$meta=array();
        foreach(array('category_id','summary','description','price','compare_price','discount_type','discount_value','display_layout','card_layout','seo_description') as $key)$fields[$key]=$product->$key;
        foreach($product->specifications as $row)$specs[]=array('key'=>$row->name,'value'=>$row->value);
        foreach($product->metadata as $row)$meta[]=array('key'=>$row->meta_key,'value'=>$row->meta_value);
        return array('version'=>1,'fields'=>$fields,'tags'=>$product->tagsText(),'specs'=>$specs,'meta'=>$meta);
    }
    public static function editorData($id)
    {
        $template=self::find($id);$data=CJSON::decode($template->payload);
        foreach(array('price','compare_price') as $key)$data['fields'][$key]=Money::input($data['fields'][$key]);
        if($data['fields']['discount_type']==='fixed')$data['fields']['discount_value']=Money::input($data['fields']['discount_value']);
        // A deleted category must not prevent using an otherwise valid snapshot.
        if(!empty($data['fields']['category_id'])&&!Category::model()->exists('id=:id',array(':id'=>$data['fields']['category_id'])))$data['fields']['category_id']='';
        return array('id'=>(int)$template->id,'name'=>$template->name,'data'=>$data);
    }
}
