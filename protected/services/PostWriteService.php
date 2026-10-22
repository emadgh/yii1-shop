<?php
class PostWriteService
{
    public static function save($model,$attributes,$tags,$coverId)
    {
        $tx=TransactionScope::begin();$wasNew=$model->isNewRecord;
        try{
            $model->attributes=$attributes;$rows=MediaService::selected($coverId,true,1);$model->cover_image=$rows?$rows[0]['filename']:'';
            if(!$model->save())throw new CHttpException(422,CJSON::encode($model->errors));
            TagService::sync('post',$model->id,$tags);$tx->commit();
        }catch(Exception $e){if($tx->active)$tx->rollback();if($wasNew){$model->isNewRecord=true;$model->primaryKey=null;}throw $e;}
    }
}
