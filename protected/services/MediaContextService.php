<?php
/** Upload provenance is independent of selecting existing shared media. */
class MediaContextService
{
    private static function definition($type)
    {
        $types=array('product'=>array('Product','adminProduct'),'post'=>array('Post','adminPost'),'catalog'=>array('CompanyCatalog','adminCatalog'));
        if(!isset($types[$type])||!AccessService::canRoute($types[$type][1].'/index'))throw new CHttpException(403,'دسترسی به بخش رسانه مجاز نیست.');
        return $types[$type];
    }
    public static function validate($context)
    {
        if(!is_string($context)||!preg_match('/^(product|post|catalog):([1-9][0-9]*|draft:([a-f0-9]{32}))$/D',$context,$parts))throw new CHttpException(400,'بخش رسانه معتبر نیست.');
        $definition=self::definition($parts[1]);
        if(strpos($parts[2],'draft:')===0){
            $drafts=Yii::app()->session['media_drafts']?:array();$draft=$drafts[$parts[3]]??null;
            if(!$draft||$draft['type']!==$parts[1]||(string)$draft['user']!==(string)Yii::app()->user->id)throw new CHttpException(403,'دسترسی به آپلودهای این فرم مجاز نیست.');
        }elseif(!CActiveRecord::model($definition[0])->exists('id=:id',array(':id'=>$parts[2])))throw new CHttpException(404,'محتوای مربوط به رسانه پیدا نشد.');
        return $context;
    }
    public static function forForm($type,$id=null,$submitted=null)
    {
        self::definition($type);
        if($id)return $type.':'.(int)$id;
        if(is_string($submitted)&&strpos($submitted,$type.':draft:')===0){try{return self::validate($submitted);}catch(CHttpException $e){}}
        $token=bin2hex(random_bytes(16));$drafts=Yii::app()->session['media_drafts']?:array();
        $drafts[$token]=array('type'=>$type,'user'=>Yii::app()->user->id);Yii::app()->session['media_drafts']=$drafts;
        return $type.':draft:'.$token;
    }
    /** Called inside the content transaction. A failed save leaves draft uploads intact. */
    public static function claim($context,$type,$id)
    {
        if($context===''||$context===null)return;
        $context=self::validate($context);$target=$type.':'.(int)$id;
        if($context===$target)return;
        if(strpos($context,$type.':draft:')!==0)throw new CHttpException(422,'آپلودهای این فرم متعلق به محتوای دیگری هستند.');
        Yii::app()->db->createCommand()->update('media',array('upload_context'=>$target),'upload_context=:context',array(':context'=>$context));
    }
}
