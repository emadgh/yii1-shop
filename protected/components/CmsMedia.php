<?php
/** Single-image form binding shared by category and collection editors. */
class CmsMedia {
 public static function bind($model,$ids){$rows=MediaService::selected($ids,true,1);$model->image=$rows?$rows[0]['filename']:'';}
 public static function ids($filename){return Yii::app()->db->createCommand('SELECT id FROM media WHERE filename=:file')->queryColumn(array(':file'=>$filename));}
}
