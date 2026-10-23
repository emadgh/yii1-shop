<?php
/** Owns catalog persistence and fresh, server-side media validation. */
class CompanyCatalogService
{
    public static function save($model, $attributes, $imageId, $pdfId, $mediaContext='')
    {
        if (!is_array($attributes)) throw new CException('اطلاعات کاتالوگ معتبر نیست.');
        $db=Yii::app()->db;$tx=TransactionScope::begin();$wasNew=$model->isNewRecord;
        try {
            $model->attributes=$attributes;
            $images=MediaService::selected($imageId, true, 1);
            $pdfs=MediaService::selected($pdfId, false, 1);
            if (!$pdfs || $pdfs[0]['mime']!=='application/pdf') throw new CException('یک فایل PDF معتبر انتخاب کنید.');
            $model->image_id=$images?(int)$images[0]['id']:null;
            $model->pdf_id=(int)$pdfs[0]['id'];
            if (!$model->save()) throw new CException('اطلاعات کاتالوگ را اصلاح کنید.');
            MediaContextService::claim($mediaContext,'catalog',$model->id);
            $tx->commit();
        } catch (Exception $exception) {
            if ($tx->active) $tx->rollback();
            if($wasNew){$model->isNewRecord=true;$model->primaryKey=null;}
            throw $exception;
        }
    }
}
