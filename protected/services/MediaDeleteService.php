<?php
/** Removes only unreferenced media; file removal can be deferred to an outer commit. */
class MediaDeleteService
{
    public static function delete($id,$removeFiles=true)
    {
        $db=Yii::app()->db;$tx=TransactionScope::begin();
        try{
            $row=$db->createCommand('SELECT * FROM media WHERE id=:id'.($db->driverName==='mysql'?' FOR UPDATE':''))->queryRow(true,array(':id'=>$id));
            if(!$row)throw new CHttpException(404,'فایل پیدا نشد.');
            $stem=preg_replace('~-(thumb|medium|large)\.webp$~','',$row['filename']);$pattern='%'.$stem.'%';
            $refs=(int)$db->createCommand('SELECT COUNT(*) FROM product_images WHERE filename=:p')->queryScalar(array(':p'=>$row['filename']))
                +(int)$db->createCommand('SELECT COUNT(*) FROM posts WHERE cover_image=:p OR body LIKE :body OR excerpt LIKE :excerpt')->queryScalar(array(':p'=>$row['filename'],':body'=>$pattern,':excerpt'=>$pattern))
                +(int)$db->createCommand('SELECT COUNT(*) FROM products WHERE description LIKE :description OR summary LIKE :summary')->queryScalar(array(':description'=>$pattern,':summary'=>$pattern));
            foreach(array('categories','collections') as $table)$refs+=(int)$db->createCommand("SELECT COUNT(*) FROM $table WHERE image=:file OR description LIKE :pattern")->queryScalar(array(':file'=>$row['filename'],':pattern'=>$pattern));
            $refs+=(int)$db->createCommand('SELECT COUNT(*) FROM company_catalogs WHERE image_id=:image OR pdf_id=:pdf OR description LIKE :pattern')->queryScalar(array(':image'=>$id,':pdf'=>$id,':pattern'=>$pattern));
            foreach(ThemeService::catalog() as $key=>$theme)if((int)ThemeOptions::get($key)['hero_media']===(int)$id)$refs++;
            if($refs)throw new CHttpException(422,'این رسانه در محتوا استفاده شده است؛ ابتدا اتصال آن را حذف کنید.');
            $db->createCommand()->delete('media','id=:id',array(':id'=>$id));Audit::record('media',$id,'delete');$tx->commit();
        }catch(Exception $e){if($tx->active)$tx->rollback();throw $e;}
        if($removeFiles)MediaService::removeFiles($row['filename'],json_decode($row['variants'],true)?:array());
        return $row;
    }
}
